<?php

use App\Domain\Content\Videos\YouTubeOEmbed;
use App\Enums\AuditAction;
use App\Enums\ContentStatus;
use App\Enums\LinkStatus;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Video;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

/*
| The video catalog (§32, ADR-035): YouTube videos by ID, confirmed with
| oEmbed when added and every night. Learners only see available ones.
*/

beforeEach(fn () => Http::preventStrayRequests());

/** oEmbed answering for one video ID. */
function fakeOEmbed(string $id, int $status = 200, array $body = []): void
{
    Http::fake([
        'www.youtube.com/oembed?*'.$id.'*' => Http::response($status === 200 ? [
            'title' => 'But what is a neural network?',
            'author_name' => '3Blue1Brown',
            'thumbnail_url' => "https://i.ytimg.com/vi/{$id}/hqdefault.jpg",
            ...$body,
        ] : 'Not Found', $status),
    ]);
}

function videoForm(array $overrides = []): array
{
    return [
        'url' => 'https://www.youtube.com/watch?v=fake-abcdef',
        'title' => 'Qué es una red neuronal',
        'instructor' => '3Blue1Brown',
        'description' => 'La intuición antes de las fórmulas.',
        'duration' => '18:40',
        'difficulty' => 'BEGINNER',
        'language' => 'en',
        ...$overrides,
    ];
}

it('asks oEmbed and tells available, missing, private and inconclusive apart', function () {
    Http::fake([
        '*fake-000200*' => Http::response(['title' => 'Un video', 'author_name' => 'Canal', 'thumbnail_url' => 'https://i.ytimg.com/vi/fake-000200/hqdefault.jpg']),
        '*fake-000404*' => Http::response('Not Found', 404),
        '*fake-000401*' => Http::response('Unauthorized', 401),
        '*fake-000500*' => Http::response('Error', 500),
        '*fake-evilth*' => Http::response(['title' => 'Otro', 'thumbnail_url' => 'https://evil.example/x.jpg']),
        '*fake-timeou*' => fn () => throw new ConnectionException('timeout'),
    ]);
    $oembed = new YouTubeOEmbed;

    $ok = $oembed->lookup('fake-000200');
    expect($ok->status)->toBe(LinkStatus::Ok)
        ->and([$ok->title, $ok->author, $ok->thumbnailUrl])->toBe(['Un video', 'Canal', 'https://i.ytimg.com/vi/fake-000200/hqdefault.jpg'])
        ->and($oembed->lookup('fake-000404')->status)->toBe(LinkStatus::Broken)
        ->and($oembed->lookup('fake-000404')->reason)->toBe('el video no existe')
        ->and($oembed->lookup('fake-000401')->reason)->toBe('el video es privado o no permite insertarlo')
        ->and($oembed->lookup('fake-000500')->status)->toBeNull()
        ->and($oembed->lookup('fake-timeou')->status)->toBeNull()
        // A thumbnail is only taken from YouTube's image host.
        ->and($oembed->lookup('fake-evilth')->thumbnailUrl)->toBeNull();

    // The request goes to YouTube's oEmbed with the watch URL.
    Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://www.youtube.com/oembed?')
        && $request['url'] === 'https://www.youtube.com/watch?v=fake-000200' && $request['format'] === 'json');
});

it('adds a video as a draft, confirmed by oEmbed right after saving', function () {
    fakeOEmbed('fake-abcdef');

    $this->actingAs(staff(Role::Instructor))
        ->post('/admin/videos', videoForm(['url' => 'https://youtu.be/fake-abcdef?si=x']))
        ->assertSessionHasNoErrors()
        ->assertRedirect()
        ->assertInertiaFlash('toast.message', 'Video añadido como borrador. Se está comprobando en YouTube.');

    $video = Video::query()->sole();

    expect($video->external_id)->toBe('fake-abcdef')
        ->and($video->url())->toBe('https://www.youtube.com/watch?v=fake-abcdef')
        ->and($video->status)->toBe(ContentStatus::Draft)
        ->and($video->duration_seconds)->toBe(1120)
        ->and($video->link_status)->toBe(LinkStatus::Ok)
        ->and($video->thumbnail_url)->toBe('https://i.ytimg.com/vi/fake-abcdef/hqdefault.jpg')
        // The editor's title stays: oEmbed only fills the form.
        ->and($video->title)->toBe('Qué es una red neuronal')
        ->and(AuditLog::query()->where('auditable_type', 'video')->sole()->action)->toBe(AuditAction::Created);
});

it('marks a video YouTube does not have as unavailable', function () {
    fakeOEmbed('fake-abcdef', 404);

    $this->actingAs(staff(Role::Editor))->post('/admin/videos', videoForm());

    expect(Video::query()->sole()->link_status)->toBe(LinkStatus::Broken)
        ->and(Video::query()->sole()->last_http_status)->toBe(404);
});

it('validates the link, the duration and one entry per video', function () {
    fakeOEmbed('fake-abcdef');
    $this->actingAs(staff(Role::Editor));
    $this->post('/admin/videos', videoForm());

    $this->post('/admin/videos', videoForm(['url' => 'fake-abcdef']))
        ->assertSessionHasErrors(['url' => 'Ese video ya está en el catálogo.']);
    $this->post('/admin/videos', videoForm(['url' => 'https://vimeo.com/123']))
        ->assertSessionHasErrors(['url' => 'Pega un enlace de YouTube (youtube.com/watch?v=…, youtu.be/…) o el ID de 11 caracteres del video.']);
    $this->post('/admin/videos', videoForm(['url' => 'fake-ghijkl', 'duration' => '90 min']))
        ->assertSessionHasErrors(['duration' => 'Escribe la duración como minutos:segundos (12:34) u horas:minutos:segundos (1:02:03).']);

    expect(Video::count())->toBe(1);
});

it('checks a video again when its ID changes, not when its data does', function () {
    fakeOEmbed('fake-abcdef');
    $editor = staff(Role::Editor);
    $this->actingAs($editor)->post('/admin/videos', videoForm());
    $video = Video::query()->sole();

    $this->put("/admin/videos/{$video->id}", videoForm(['title' => 'Redes neuronales, la intuición', 'duration' => '']))
        ->assertSessionHasNoErrors();
    expect($video->fresh()->title)->toBe('Redes neuronales, la intuición')
        ->and($video->fresh()->duration_seconds)->toBeNull();
    Http::assertSentCount(1);

    Http::fake(['*' => Http::response('Not Found', 404)]);
    $this->put("/admin/videos/{$video->id}", videoForm(['url' => 'fake-ghijkl']))->assertSessionHasNoErrors();

    expect($video->fresh()->external_id)->toBe('fake-ghijkl')
        ->and($video->fresh()->link_status)->toBe(LinkStatus::Broken)
        ->and($video->fresh()->thumbnail_url)->toBeNull();
});

it('looks a link up before saving', function () {
    fakeOEmbed('fake-abcdef');
    $this->actingAs(staff(Role::Instructor));

    $this->getJson('/admin/videos/lookup?url='.urlencode('https://www.youtube.com/watch?v=fake-abcdef'))
        ->assertOk()
        ->assertExactJson([
            'video_id' => 'fake-abcdef',
            'url' => 'https://www.youtube.com/watch?v=fake-abcdef',
            'available' => true,
            'reason' => null,
            'title' => 'But what is a neural network?',
            'instructor' => '3Blue1Brown',
            'thumbnail_url' => 'https://i.ytimg.com/vi/fake-abcdef/hqdefault.jpg',
            'existing' => null,
        ]);

    $this->getJson('/admin/videos/lookup?url=nope')->assertUnprocessable()->assertJsonValidationErrors('url');
});

it('says when a looked-up video cannot be used or is already in the catalog', function () {
    $video = Video::factory()->create(['external_id' => 'fake-000401']);
    Http::fake(['*' => Http::response('Unauthorized', 401)]);

    $this->actingAs(staff(Role::Editor))
        ->getJson('/admin/videos/lookup?url=fake-000401')
        ->assertJson([
            'available' => false,
            'reason' => 'el video es privado o no permite insertarlo',
            'existing' => ['id' => $video->id, 'title' => $video->title, 'href' => "/admin/videos/{$video->id}/edit"],
        ]);
});

it('lists the catalog, searchable and filterable by availability', function () {
    Video::factory()->create(['title' => 'Attention is all you need, explicado', 'instructor' => 'Yannic']);
    Video::factory()->create(['title' => 'Git en 100 segundos', 'link_status' => LinkStatus::Broken]);

    $this->actingAs(staff(Role::Editor));
    $this->get('/admin/videos')->assertInertia(fn (Assert $page) => $page
        ->component('admin/videos/index')
        ->has('videos', 2)
        ->where('videos.0.title', 'Attention is all you need, explicado')
        ->where('can.create', true));
    $this->get('/admin/videos?q=yannic')->assertInertia(fn (Assert $page) => $page->has('videos', 1));
    $this->get('/admin/videos?link=BROKEN')->assertInertia(fn (Assert $page) => $page
        ->has('videos', 1)->where('videos.0.title', 'Git en 100 segundos'));
});

it('publishes a video and checks it on request', function () {
    $video = Video::factory()->create(['status' => ContentStatus::Draft, 'link_status' => LinkStatus::Unchecked]);
    fakeOEmbed($video->external_id);
    $this->actingAs(staff(Role::Editor));

    $this->put("/admin/videos/{$video->id}/status", ['status' => 'PUBLISHED'])
        ->assertInertiaFlash('toast.message', 'Video publicado: aparece en las lecciones que lo usan mientras YouTube lo tenga disponible.');
    $this->post("/admin/videos/{$video->id}/verify")->assertRedirect();

    expect($video->fresh()->status)->toBe(ContentStatus::Published)
        ->and($video->fresh()->link_status)->toBe(LinkStatus::Ok)
        ->and($video->fresh()->isVisibleToLearners())->toBeTrue();
});

it('keeps the catalog to content authors, and instructors to their own videos', function () {
    $video = Video::factory()->create();
    $instructor = staff(Role::Instructor);

    $this->actingAs(staff(Role::Student))->get('/admin/videos')->assertForbidden();
    $this->actingAs(staff(Role::Student))->getJson('/admin/videos/lookup?url=fake-abcdef')->assertForbidden();

    $this->actingAs($instructor)->get("/admin/videos/{$video->id}/edit")->assertForbidden();
    $this->actingAs($instructor)->put("/admin/videos/{$video->id}", videoForm())->assertForbidden();
});

it('checks every video every night and leaves inconclusive ones alone', function () {
    $available = Video::factory()->unchecked()->create(['external_id' => 'fake-000200']);
    $gone = Video::factory()->create(['external_id' => 'fake-000404']);
    $unknown = Video::factory()->create(['external_id' => 'fake-000500']);
    $archived = Video::factory()->unchecked()->create(['external_id' => 'fake-arch01', 'status' => ContentStatus::Archived]);
    Http::fake([
        '*fake-000200*' => Http::response(['title' => 'Un video', 'thumbnail_url' => 'https://i.ytimg.com/vi/fake-000200/hqdefault.jpg']),
        '*fake-000404*' => Http::response('Not Found', 404),
        '*fake-000500*' => Http::response('Error', 503),
    ]);

    $this->artisan('content:verify-videos')
        ->expectsOutputToContain('3 video(s) comprobados: 1 no disponible(s), 1 no concluyente(s).')
        ->assertSuccessful();

    expect($available->fresh()->link_status)->toBe(LinkStatus::Ok)
        ->and($gone->fresh()->link_status)->toBe(LinkStatus::Broken)
        ->and($unknown->fresh()->link_status)->toBe(LinkStatus::Ok)
        ->and($archived->fresh()->link_status)->toBe(LinkStatus::Unchecked)
        // A check is not an editorial change.
        ->and(AuditLog::query()->where('auditable_type', 'video')->where('action', 'UPDATED')->exists())->toBeFalse();
});
