<?php

use App\Enums\AuditAction;
use App\Enums\LinkStatus;
use App\Enums\Role;
use App\Models\ExternalResource;
use App\Models\Track;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function userWithRole(Role $role): User
{
    return User::factory()->create()->assignRole($role->value);
}

it('sends guests to the login page', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

it('forbids students with the Inertia error page', function () {
    $this->actingAs(userWithRole(Role::Student))
        ->get(route('admin.dashboard'))
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page
            ->component('errors/error')
            ->where('status', 403));
});

it('opens the overview to staff roles', function (Role $role) {
    $this->actingAs(userWithRole($role))
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/dashboard'));
})->with([Role::Admin, Role::Editor, Role::Instructor]);

it('counts content by status, links by status and shows recent activity', function () {
    $editor = userWithRole(Role::Editor);
    $this->actingAs($editor);

    Track::factory()->published()->create(['title' => 'Fundamentos']);
    Track::factory()->count(2)->create();
    ExternalResource::factory()->create(['link_status' => LinkStatus::Broken]);
    ExternalResource::factory()->count(5)->create(['link_status' => LinkStatus::Ok]);

    $this->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->has('content', 6)
            ->where('content.1', [
                'entity' => 'track',
                'counts' => ['DRAFT' => 2, 'REVIEW' => 0, 'PUBLISHED' => 1, 'ARCHIVED' => 0],
                'total' => 3,
            ])
            ->where('links', ['UNCHECKED' => 0, 'OK' => 5, 'REDIRECTED' => 0, 'BROKEN' => 1])
            // 3 roadmaps + 3 tracks + 6 resources were audited; only the latest 10 are shown.
            ->has('activity', 10)
            ->where('activity.0.user', $editor->name)
            ->where('activity.0.action', AuditAction::Created->value)
            ->where('activity.0.entity', 'resource'));
});

it('shares whether the user can open the admin area', function (Role $role, bool $expected) {
    $this->actingAs(userWithRole($role))
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('can.accessAdmin', $expected));
})->with([
    'student' => [Role::Student, false],
    'instructor' => [Role::Instructor, true],
    'admin' => [Role::Admin, true],
]);
