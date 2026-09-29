<?php

use App\Domain\Content\RichContent\RichContent;
use App\Domain\Curriculum\Actions\PublishLesson;
use App\Enums\Difficulty;
use App\Enums\Role;
use App\Models\Lesson;
use Inertia\Testing\AssertableInertia as Assert;

/*
| Version history in the CMS: each published version against the one before
| it, and the working copy against the published version, as text diffs.
*/

beforeEach(function () {
    $this->lesson = publishedLesson(['slug' => 'commits', 'title' => 'Commits']);
});

/**
 * Appends a paragraph to the lesson body and changes the difficulty.
 */
function editLesson(Lesson $lesson, string $paragraph): void
{
    $lesson->body = RichContent::fromDocument([
        'type' => 'doc',
        'content' => [
            ...$lesson->body->doc['content'],
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $paragraph]]],
        ],
    ]);
    $lesson->title = 'Commits y staging';
    $lesson->difficulty = Difficulty::Intermediate;
    $lesson->save();
}

it('shows a version with its changes against the previous one', function () {
    editLesson($this->lesson, 'Un párrafo nuevo sobre staging.');
    app(PublishLesson::class)->handle($this->lesson, 'Añade staging');

    $this->actingAs(staff(Role::Editor))
        ->get('/admin/lessons/commits/versions/2')
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/lessons/version')
            ->where('lesson', ['slug' => 'commits', 'title' => 'Commits y staging'])
            ->where('version.version', 2)
            ->where('version.change_note', 'Añade staging')
            ->where('version.current', true)
            ->where('previous', 1)
            ->where('changes.fields.0.field', 'title')
            ->where('changes.fields.0.rows.0.segments.0.text', 'Commits')
            ->where('changes.fields.0.rows.1.type', 'added')
            ->where('changes.attributes', [['field' => 'difficulty', 'before' => 'BEGINNER', 'after' => 'INTERMEDIATE']])
            ->where('changes.body', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['type'] === 'added' && $row['segments'][0]['text'] === 'Un párrafo nuevo sobre staging.',
            ))
            ->where('content.title', 'Commits y staging')
            ->has('content.body'));
});

it('shows the first version without changes', function () {
    $this->actingAs(staff(Role::Editor))
        ->get('/admin/lessons/commits/versions/1')
        ->assertInertia(fn (Assert $page) => $page
            ->where('version.version', 1)
            ->where('previous', null)
            ->where('changes', null));
});

it('shows the unpublished changes of the working copy', function () {
    $this->actingAs(staff(Role::Editor))
        ->get('/admin/lessons/commits/changes')
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/lessons/changes')
            ->where('published', 1)
            ->where('changes', ['fields' => [], 'attributes' => [], 'body' => []]));

    editLesson($this->lesson, 'Todavía sin publicar.');

    $this->actingAs(staff(Role::Editor))
        ->get('/admin/lessons/commits/changes')
        ->assertInertia(fn (Assert $page) => $page
            ->where('changes.attributes.0.field', 'difficulty')
            ->where('changes.body', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['type'] === 'added' && $row['segments'][0]['text'] === 'Todavía sin publicar.',
            )));
});

it('has nothing to compare before the first publication', function () {
    publishableLesson(['slug' => 'nueva'], $this->lesson->module);

    $this->actingAs(staff(Role::Editor))
        ->get('/admin/lessons/nueva/changes')
        ->assertInertia(fn (Assert $page) => $page->where('published', null)->where('changes', null));
});

it('scopes version numbers to their lesson and keeps others out', function () {
    $other = publishedLesson(['slug' => 'otra'], $this->lesson->module);
    editLesson($other, 'Segunda versión de otra lección.');
    app(PublishLesson::class)->handle($other);
    $instructor = staff(Role::Instructor);

    // "otra" has a version 2; "commits" does not.
    $this->actingAs(staff(Role::Editor))->get('/admin/lessons/otra/versions/2')->assertOk();
    $this->actingAs(staff(Role::Editor))->get('/admin/lessons/commits/versions/2')->assertNotFound();
    $this->actingAs($instructor)->get('/admin/lessons/commits/versions/1')->assertForbidden();
    $this->actingAs($instructor)->get('/admin/lessons/commits/changes')->assertForbidden();
    $this->actingAs(staff(Role::Student))->get('/admin/lessons/commits/changes')->assertForbidden();
});
