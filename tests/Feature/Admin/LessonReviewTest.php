<?php

use App\Enums\AuditAction;
use App\Enums\ContentStatus;
use App\Enums\ReviewResolution;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Lesson;
use App\Models\LessonReview;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

/*
| Review flow of lessons (ADR-032): an instructor sends their lesson for
| review, it is frozen for them meanwhile, and an editor publishes it or
| returns it with a comment.
*/

beforeEach(function () {
    $this->instructor = staff(Role::Instructor);
    $this->editor = staff(Role::Editor);
    $this->lesson = publishableLesson(['slug' => 'propia', 'title' => 'Lección propia', 'created_by' => $this->instructor->id]);
});

function submit(User $user, Lesson $lesson, array $data = []): TestResponse
{
    return test()->actingAs($user)->post("/admin/lessons/{$lesson->slug}/review", $data);
}

it('sends a lesson for review and freezes it for its author', function () {
    submit($this->instructor, $this->lesson, ['note' => 'Revisa la sección de práctica.'])->assertSessionHasNoErrors();

    $review = LessonReview::sole();
    expect($this->lesson->refresh()->status)->toBe(ContentStatus::Review)
        ->and($review->submitted_by)->toBe($this->instructor->id)
        ->and($review->note)->toBe('Revisa la sección de práctica.')
        ->and($review->previous_status)->toBe(ContentStatus::Draft)
        ->and(AuditLog::query()->where('action', AuditAction::Submitted)->where('auditable_id', $this->lesson->id)->exists())->toBeTrue();

    // The author can open it but not change it; an editor still can.
    $this->actingAs($this->instructor)->get('/admin/lessons/propia/edit')
        ->assertInertia(fn (Assert $page) => $page->where('can.save', false)->where('can.withdraw', true)->where('review.open.note', 'Revisa la sección de práctica.'));
    $this->actingAs($this->instructor)->put('/admin/lessons/propia', lessonForm($this->lesson, ['title' => 'Otro']))->assertForbidden();
    $this->actingAs($this->instructor)->put('/admin/lessons/propia/relations', ['skills' => [], 'prerequisites' => [], 'resources' => []])->assertForbidden();
    $this->actingAs($this->editor)->put('/admin/lessons/propia', lessonForm($this->lesson, ['title' => 'Título corregido']))->assertSessionHasNoErrors();
});

it('lists open reviews for editors only', function () {
    submit($this->instructor, $this->lesson, ['note' => 'Lista para publicar.']);

    $this->actingAs($this->editor)->get('/admin/reviews')->assertInertia(fn (Assert $page) => $page
        ->component('admin/reviews/index')
        ->has('reviews', 1)
        ->where('reviews.0.lesson.slug', 'propia')
        ->where('reviews.0.submitted_by', $this->instructor->name)
        ->where('reviews.0.note', 'Lista para publicar.')
        ->where('reviews.0.edited_since', false));

    $this->actingAs($this->editor)->get('/admin')->assertInertia(fn (Assert $page) => $page->where('pendingReviews', 1));
    $this->actingAs($this->instructor)->get('/admin/reviews')->assertForbidden();
    $this->actingAs($this->instructor)->get('/admin')->assertInertia(fn (Assert $page) => $page->where('pendingReviews', null));
});

it('publishes from review and closes the review with the version', function () {
    submit($this->instructor, $this->lesson);

    $this->actingAs($this->editor)->post('/admin/lessons/propia/publish', ['change_note' => 'Revisada'])->assertSessionHasNoErrors();

    $review = LessonReview::sole();
    expect($this->lesson->refresh()->status)->toBe(ContentStatus::Published)
        ->and($review->resolution)->toBe(ReviewResolution::Published)
        ->and($review->resolved_by)->toBe($this->editor->id)
        ->and($review->lesson_version_id)->toBe($this->lesson->published_version_id);
});

it('returns a lesson with a comment the author sees until sending it again', function () {
    submit($this->instructor, $this->lesson);

    $this->actingAs($this->editor)->post('/admin/lessons/propia/review/return', ['comment' => ''])->assertSessionHasErrors('comment');
    $this->actingAs($this->editor)->post('/admin/lessons/propia/review/return', ['comment' => 'Falta un ejemplo en la práctica.'])->assertSessionHasNoErrors();

    expect($this->lesson->refresh()->status)->toBe(ContentStatus::Draft)
        ->and(LessonReview::sole()->resolution)->toBe(ReviewResolution::Returned)
        ->and(AuditLog::query()->where('action', AuditAction::Returned)->exists())->toBeTrue();

    $this->actingAs($this->instructor)->get('/admin/lessons/propia/edit')->assertInertia(fn (Assert $page) => $page
        ->where('can.save', true)
        ->where('review.open', null)
        ->where('review.returned.comment', 'Falta un ejemplo en la práctica.')
        ->where('review.returned.by', $this->editor->name));

    // Edited and sent again: the comment gives way to the new review.
    $this->actingAs($this->instructor)->put('/admin/lessons/propia', lessonForm($this->lesson, ['title' => 'Con ejemplo']))->assertSessionHasNoErrors();
    submit($this->instructor, $this->lesson->refresh())->assertSessionHasNoErrors();
    $this->actingAs($this->instructor)->get('/admin/lessons/propia/edit')
        ->assertInertia(fn (Assert $page) => $page->where('review.returned', null)->has('review.open'));
});

it('goes back to the status it had: a published lesson stays published', function () {
    $published = publishedLesson(['slug' => 'publicada', 'created_by' => $this->instructor->id]);
    $this->actingAs($this->instructor)->put('/admin/lessons/publicada', lessonForm($published, ['title' => 'Cambio pendiente']));
    submit($this->instructor, $published->refresh())->assertSessionHasNoErrors();

    // Learners keep reading the published version meanwhile.
    expect(Lesson::query()->visibleToLearners()->whereKey($published->id)->exists())->toBeTrue();

    $this->actingAs($this->instructor)->delete('/admin/lessons/publicada/review')->assertSessionHasNoErrors();

    expect($published->refresh()->status)->toBe(ContentStatus::Published)
        ->and(LessonReview::sole()->resolution)->toBe(ReviewResolution::Withdrawn);
});

it('only sends what can be reviewed', function () {
    // Created before anyone logs in: RecordsAuthors would make it the instructor's.
    $other = publishableLesson(['slug' => 'ajena']);

    // Missing skills: not ready. Parents unpublished do not block (not the author's call).
    $this->lesson->skills()->detach();
    submit($this->instructor, $this->lesson)->assertSessionHasErrors(['review' => 'Todavía no cumple los requisitos para publicarse. Revisa la lista.']);

    $published = publishedLesson(['slug' => 'sin-cambios', 'created_by' => $this->instructor->id]);
    submit($this->instructor, $published)->assertSessionHasErrors(['review' => 'No hay cambios sin publicar que revisar.']);

    $draft = publishableLesson(['slug' => 'modulo-borrador', 'created_by' => $this->instructor->id]);
    $draft->module->update(['status' => ContentStatus::Draft]);
    submit($this->instructor, $draft)->assertSessionHasNoErrors();
    submit($this->instructor, $draft)->assertSessionHasErrors(['review' => 'La lección ya está en revisión.']);

    // Only the author's own lessons, and learners never.
    submit($this->instructor, $other)->assertForbidden();
    submit(staff(Role::Student), $this->lesson)->assertForbidden();
});

it('keeps returning, withdrawing and archiving in the right hands', function () {
    submit($this->instructor, $this->lesson);

    $this->actingAs($this->instructor)->post('/admin/lessons/propia/review/return', ['comment' => 'x'])->assertForbidden();
    $this->actingAs(staff(Role::Instructor))->delete('/admin/lessons/propia/review')->assertForbidden();

    // Archiving waits for the review to end.
    $this->actingAs($this->editor)->put('/admin/lessons/propia/status', ['status' => 'ARCHIVED'])->assertSessionHasErrors('status');

    // Nothing to resolve once it is out of review.
    $this->actingAs($this->instructor)->delete('/admin/lessons/propia/review')->assertSessionHasNoErrors();
    $this->actingAs($this->instructor)->delete('/admin/lessons/propia/review')->assertSessionHasErrors(['review' => 'La lección no está en revisión.']);
});
