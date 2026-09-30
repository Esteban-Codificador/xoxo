<?php

use App\Enums\AuditAction;
use App\Enums\ContentStatus;
use App\Enums\LinkStatus;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Video;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

/*
| Videos of a lesson (§30, ADR-035): attached in the relations tab like
| resources, shown to learners only while published and confirmed by
| YouTube on the last check.
*/

beforeEach(function () {
    $this->lesson = publishedLesson(['slug' => 'redes', 'title' => 'Redes neuronales']);
});

function relationsPayload(array $videos, array $overrides = []): array
{
    return ['skills' => [], 'prerequisites' => [], 'resources' => [], 'videos' => $videos, ...$overrides];
}

it('attaches videos to a lesson in order and audits the change on the lesson', function () {
    [$intro, $deep] = Video::factory()->count(2)->create()->all();

    $this->actingAs(staff(Role::Editor))
        ->get('/admin/lessons/redes/relations')
        ->assertInertia(fn (Assert $page) => $page
            ->where('videos', [])
            ->has('video_options', 2)
            ->where('video_options.0.link_status', 'OK'));

    $this->put('/admin/lessons/redes/relations', relationsPayload([$deep->id, $intro->id]))->assertSessionHasNoErrors();

    expect($this->lesson->videos()->pluck('videos.id')->all())->toBe([$deep->id, $intro->id]);
    $audit = AuditLog::query()->where('auditable_type', 'lesson')->latest('id')->first();
    expect($audit->action)->toBe(AuditAction::Updated)
        ->and($audit->changes['after']['videos'])->toBe([$deep->id, $intro->id]);

    // Reordering keeps what the link says about the video.
    DB::table('video_links')->where('video_id', $intro->id)->update(['note' => 'Mira del minuto 3 al 9.', 'start_seconds' => 180]);
    $this->put('/admin/lessons/redes/relations', relationsPayload([$intro->id, $deep->id]))->assertSessionHasNoErrors();

    expect(DB::table('video_links')->where('video_id', $intro->id)->first())
        ->note->toBe('Mira del minuto 3 al 9.')
        ->start_seconds->toBe(180)
        ->position->toBe(1);
});

it('leaves the videos alone when a client does not send them', function () {
    $video = Video::factory()->create();
    $this->lesson->videos()->attach($video, ['position' => 1]);

    $this->actingAs(staff(Role::Editor))
        ->put('/admin/lessons/redes/relations', ['skills' => [], 'prerequisites' => [], 'resources' => []])
        ->assertSessionHasNoErrors();

    expect($this->lesson->videos()->count())->toBe(1);
});

it('rejects videos that do not exist or are repeated', function () {
    $video = Video::factory()->create();

    $this->actingAs(staff(Role::Editor))
        ->put('/admin/lessons/redes/relations', relationsPayload([$video->id, $video->id, 999_999]))
        ->assertSessionHasErrors(['videos.0', 'videos.2']);
});

it('shows learners the published videos YouTube confirmed, in order, with the facts of the link', function () {
    $shown = Video::factory()->create([
        'external_id' => 'fake-shown1', 'title' => 'La intuición', 'instructor' => '3Blue1Brown',
        'duration_seconds' => 1120, 'thumbnail_url' => 'https://i.ytimg.com/vi/fake-shown1/hqdefault.jpg',
    ]);
    $draft = Video::factory()->create(['status' => ContentStatus::Draft]);
    $gone = Video::factory()->create(['link_status' => LinkStatus::Broken]);
    $unchecked = Video::factory()->unchecked()->create();
    $second = Video::factory()->create(['title' => 'Backpropagation']);
    $this->lesson->videos()->attach($shown, ['position' => 1, 'note' => 'Antes de la lección.', 'start_seconds' => 60]);
    foreach ([$draft, $gone, $unchecked, $second] as $index => $video) {
        $this->lesson->videos()->attach($video, ['position' => $index + 2]);
    }

    $this->actingAs(staff(Role::Student))
        ->get('/lessons/redes')
        ->assertInertia(fn (Assert $page) => $page
            ->has('videos', 2)
            ->where('videos.0', [
                'id' => $shown->id,
                'provider' => 'YOUTUBE',
                'video_id' => 'fake-shown1',
                'title' => 'La intuición',
                'instructor' => '3Blue1Brown',
                'description' => null,
                'duration' => '18:40',
                'language' => 'en',
                'thumbnail_url' => 'https://i.ytimg.com/vi/fake-shown1/hqdefault.jpg',
                'note' => 'Antes de la lección.',
                'start_seconds' => 60,
            ])
            ->where('videos.1.title', 'Backpropagation'));
});
