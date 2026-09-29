<?php

use App\Enums\ContentStatus;
use App\Enums\LinkStatus;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\ExternalResource;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return array<string, mixed>
 */
function resourceForm(array $overrides = []): array
{
    return [
        'title' => 'Documentación de Git',
        'url' => 'https://docs.example.test/git',
        'type' => 'DOCUMENTATION',
        'provider' => 'Git',
        'description' => 'Referencia oficial de los comandos.',
        'difficulty' => null,
        'language' => 'en',
        'is_official' => true,
        ...$overrides,
    ];
}

beforeEach(function () {
    // Link checks never reach the network in tests.
    Http::fake(['docs.example.test/*' => Http::response('', 200), '*' => Http::response('', 404)]);
});

it('keeps learners out and lets instructors create but edit only their own resources', function () {
    // Created before anyone logs in: RecordsAuthors would make it the instructor's.
    $other = ExternalResource::factory()->create();
    $this->actingAs(staff(Role::Student))->get('/admin/resources')->assertForbidden();

    $instructor = staff(Role::Instructor);
    $this->actingAs($instructor)->post('/admin/resources', resourceForm())->assertSessionHasNoErrors();

    $own = ExternalResource::firstWhere('url', 'https://docs.example.test/git');
    expect($own->created_by)->toBe($instructor->id)->and($own->status)->toBe(ContentStatus::Draft);

    $this->actingAs($instructor)->get("/admin/resources/{$own->id}/edit")->assertOk();
    $this->actingAs($instructor)->put("/admin/resources/{$other->id}", resourceForm(['url' => $other->url]))->assertForbidden();

    // Publishing is an editor's job.
    $this->actingAs($instructor)->put("/admin/resources/{$own->id}/status", ['status' => 'PUBLISHED'])->assertForbidden();
});

it('creates a resource as a draft and verifies its link off the request', function () {
    $this->actingAs(staff(Role::Editor))
        ->post('/admin/resources', resourceForm())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $resource = ExternalResource::firstWhere('url', 'https://docs.example.test/git');

    // The queue runs synchronously in tests: the check already ran.
    expect($resource->link_status)->toBe(LinkStatus::Ok)
        ->and($resource->last_http_status)->toBe(200)
        ->and($resource->last_checked_at)->not->toBeNull();

    Http::assertSent(fn ($request) => $request->url() === 'https://docs.example.test/git');
});

it('validates https, uniqueness and the resource fields', function () {
    $existing = ExternalResource::factory()->create();

    $this->actingAs(staff(Role::Editor))
        ->post('/admin/resources', resourceForm([
            'url' => 'http://inseguro.example.test',
            'type' => 'PODCAST',
            'language' => 'fr',
            'title' => '',
        ]))
        ->assertSessionHasErrors(['url', 'type', 'language', 'title']);

    $this->actingAs(staff(Role::Editor))
        ->post('/admin/resources', resourceForm(['url' => $existing->url]))
        ->assertSessionHasErrors(['url' => 'El valor de URL ya está en uso.']);
});

it('forgets the previous check when the URL changes and checks the new one', function () {
    $resource = ExternalResource::factory()->create([
        'url' => 'https://docs.example.test/antes',
        'link_status' => LinkStatus::Ok,
        'last_http_status' => 200,
        'last_checked_at' => now()->subDay(),
    ]);

    $this->actingAs(staff(Role::Editor))
        ->put("/admin/resources/{$resource->id}", resourceForm(['url' => 'https://gone.example.test/pagina']))
        ->assertSessionHasNoErrors();

    expect($resource->refresh()->link_status)->toBe(LinkStatus::Broken)
        ->and($resource->last_http_status)->toBe(404);

    // Other edits keep the result and do not check again.
    Http::fake();
    $this->actingAs(staff(Role::Editor))
        ->put("/admin/resources/{$resource->id}", resourceForm(['url' => 'https://gone.example.test/pagina', 'title' => 'Otro título']))
        ->assertSessionHasNoErrors();
    expect($resource->refresh()->link_status)->toBe(LinkStatus::Broken);
    Http::assertNothingSent();
});

it('verifies a link on demand without writing to the editorial audit', function () {
    $resource = ExternalResource::factory()->create(['url' => 'https://docs.example.test/manual']);
    $before = AuditLog::count();

    $this->actingAs(staff(Role::Editor))
        ->post("/admin/resources/{$resource->id}/verify")
        ->assertSessionHasNoErrors();

    expect($resource->refresh()->link_status)->toBe(LinkStatus::Ok)
        ->and(AuditLog::count())->toBe($before);
});

it('publishes and archives resources, and learners only see published ones', function () {
    $lesson = publishedLesson(['slug' => 'con-recurso']);
    $resource = ExternalResource::factory()->create(['title' => 'Guía oficial', 'status' => ContentStatus::Draft]);
    $lesson->resources()->attach($resource, ['position' => 1]);
    $learner = User::factory()->create();

    $this->actingAs($learner)->get('/lessons/con-recurso')->assertInertia(fn (Assert $page) => $page->has('resources', 0));

    $this->actingAs(staff(Role::Editor))->put("/admin/resources/{$resource->id}/status", ['status' => 'PUBLISHED'])->assertSessionHasNoErrors();
    $this->actingAs($learner)->get('/lessons/con-recurso')->assertInertia(fn (Assert $page) => $page->where('resources.0.title', 'Guía oficial'));

    $this->actingAs(staff(Role::Editor))->put("/admin/resources/{$resource->id}/status", ['status' => 'ARCHIVED'])->assertSessionHasNoErrors();
    $this->actingAs($learner)->get('/lessons/con-recurso')->assertInertia(fn (Assert $page) => $page->has('resources', 0));
});

it('lists resources with their link status, usage and filters', function () {
    $lesson = publishedLesson();
    $broken = ExternalResource::factory()->create(['title' => 'Roto', 'link_status' => LinkStatus::Broken]);
    ExternalResource::factory()->create(['title' => 'Sano', 'provider' => 'Python', 'link_status' => LinkStatus::Ok]);
    $lesson->resources()->attach($broken, ['position' => 1]);

    $this->actingAs(staff(Role::Editor))
        ->get('/admin/resources?link=BROKEN')
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/resources/index')
            ->has('resources', 1)
            ->where('resources.0.title', 'Roto')
            ->where('resources.0.lessons_count', 1)
            ->where('filters.link', 'BROKEN')
            ->where('can.create', true));

    $this->actingAs(staff(Role::Editor))
        ->get('/admin/resources?q=pyth')
        ->assertInertia(fn (Assert $page) => $page->has('resources', 1)->where('resources.0.title', 'Sano'));
});

it('opens the editor with the lessons that use the resource', function () {
    $lesson = publishedLesson(['title' => 'Commits']);
    $resource = ExternalResource::factory()->create();
    $lesson->resources()->attach($resource, ['position' => 1]);

    $this->actingAs(staff(Role::Editor))
        ->get("/admin/resources/{$resource->id}/edit")
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/resources/form')
            ->where('resource.url', $resource->url)
            ->where('lessons', [['slug' => $lesson->slug, 'title' => 'Commits']])
            ->where('status_actions', ['DRAFT', 'ARCHIVED']));
});
