<?php

use App\Domain\Identity\Actions\ChangeUserRole;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\ExternalResource;
use App\Models\Lesson;
use App\Models\Track;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

/*
| The audit log in the CMS (step 5, §43): every change, newest first, with
| its field changes and a link to what changed, filterable.
*/

it('lists the log newest first with field changes and links', function () {
    $admin = staff(Role::Admin);
    $student = staff(Role::Student);

    $this->actingAs($admin);
    $track = Track::factory()->create(['title' => 'Fundamentos']);
    $track->update(['title' => 'Fundamentos de programación']);
    app(ChangeUserRole::class)->handle($student, Role::Editor);

    $this->get('/admin/audit')
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/audit/index')
            // The track factory also creates its roadmap.
            ->where('pagination.total', 4)
            ->where('entries.0.action', 'ROLE_ASSIGNED')
            ->where('entries.0.entity', 'user')
            ->where('entries.0.label', $student->name)
            ->where('entries.0.user', $admin->name)
            ->where('entries.0.href', "/admin/users/{$student->id}/edit")
            ->where('entries.0.changes', [['field' => 'role', 'before' => 'STUDENT', 'after' => 'EDITOR', 'hashed' => false]])
            ->where('entries.1.action', 'UPDATED')
            ->where('entries.1.label', 'Fundamentos de programación')
            ->where('entries.1.href', "/admin/tracks/{$track->id}/edit")
            ->where('entries.1.changes', [['field' => 'title', 'before' => 'Fundamentos', 'after' => 'Fundamentos de programación', 'hashed' => false]])
            ->where('entries.2.action', 'CREATED')
            ->where('entries.3.entity', 'roadmap')
            ->where('options.entities', ['roadmap', 'track', 'user'])
            ->where('options.users', [['id' => $admin->id, 'name' => $admin->name]]));
});

it('shows long text fields as a digest', function () {
    $this->actingAs(staff(Role::Editor));
    $resource = ExternalResource::factory()->create(['description' => 'Primera versión.']);
    $resource->update(['description' => 'Segunda versión.']);

    $this->get('/admin/audit?action=UPDATED')
        ->assertInertia(fn (Assert $page) => $page
            ->where('entries.0.changes.0.field', 'description')
            ->where('entries.0.changes.0.hashed', true)
            ->where('entries.0.changes.0.before', substr(hash('sha256', 'Primera versión.'), 0, 16))
            ->where('entries.0.changes.0.after', substr(hash('sha256', 'Segunda versión.'), 0, 16)));
});

it('keeps entries of what no longer exists, without a link', function () {
    $this->actingAs(staff(Role::Editor));
    $resource = ExternalResource::factory()->create();
    DB::table('resources')->where('id', $resource->id)->delete();

    $this->get('/admin/audit')
        ->assertInertia(fn (Assert $page) => $page
            ->where('entries.0.entity', 'resource')
            ->where('entries.0.label', null)
            ->where('entries.0.href', null));
});

it('filters by action, type, person and dates', function () {
    $editor = staff(Role::Editor);
    $instructor = staff(Role::Instructor);

    $this->actingAs($editor);
    $this->travelTo('2026-09-01 10:00');
    Track::factory()->create();
    $this->travelTo('2026-09-10 10:00');
    Lesson::factory()->create()->update(['title' => 'Cambiado']);

    $this->actingAs($instructor);
    $this->travelTo('2026-09-20 10:00');
    ExternalResource::factory()->create();
    $this->travelBack();

    $this->actingAs($editor);
    // Creating the lesson also creates its module, track and roadmap.
    $total = AuditLog::query()->count();

    $this->get('/admin/audit?action=UPDATED')
        ->assertInertia(fn (Assert $page) => $page->has('entries', 1)->where('entries.0.entity', 'lesson')->where('filters.action', 'UPDATED'));
    $this->get('/admin/audit?entity=resource')
        ->assertInertia(fn (Assert $page) => $page->has('entries', 1)->where('filters.entity', 'resource'));
    $this->get("/admin/audit?user={$instructor->id}")
        ->assertInertia(fn (Assert $page) => $page->has('entries', 1)->where('entries.0.user', $instructor->name));
    $this->get('/admin/audit?from=2026-09-10&to=2026-09-10')
        ->assertInertia(fn (Assert $page) => $page
            ->where('pagination.total', AuditLog::query()->whereDate('created_at', '2026-09-10')->count())
            ->where('filters.from', '2026-09-10')
            ->where('filters.to', '2026-09-10'));
    $this->get('/admin/audit?to=2026-09-01')
        ->assertInertia(fn (Assert $page) => $page->has('entries', 2)->where('entries.0.entity', 'track')->where('entries.1.entity', 'roadmap'));

    // Values that are not filters are ignored rather than failing.
    $this->get('/admin/audit?action=HACKED&entity=users;drop&from=ayer&to=2026-02-31')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('pagination.total', $total)
            ->where('filters', ['action' => null, 'entity' => null, 'user' => null, 'from' => null, 'to' => null]));
});

it('offers the filtered person even before they did anything', function () {
    $admin = staff(Role::Admin);
    $newcomer = staff(Role::Student);

    $this->actingAs($admin)->get("/admin/audit?user={$newcomer->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->has('entries', 0)
            ->where('options.users', [['id' => $newcomer->id, 'name' => $newcomer->name]]));
});

it('paginates the log', function () {
    $this->actingAs(staff(Role::Editor));
    Track::factory()->count(30)->create();

    $this->get('/admin/audit?entity=track&page=2')
        ->assertInertia(fn (Assert $page) => $page
            ->has('entries', 5)
            ->where('pagination.total', 30)
            ->where('pagination.page', 2)
            ->where('pagination.previous', fn (string $url) => str_contains($url, 'entity=track')));
});

it('opens the log to editors, linking accounts only for admins', function () {
    $admin = staff(Role::Admin);
    $student = staff(Role::Student);
    $this->actingAs($admin);
    app(ChangeUserRole::class)->handle($student, Role::Instructor);

    $this->actingAs(staff(Role::Editor))->get('/admin/audit')
        ->assertInertia(fn (Assert $page) => $page
            ->where('entries.0.entity', 'user')
            ->where('entries.0.href', null));

    $this->actingAs(staff(Role::Instructor))->get('/admin/audit')->assertForbidden();
    $this->actingAs($student)->get('/admin/audit')->assertForbidden();
});
