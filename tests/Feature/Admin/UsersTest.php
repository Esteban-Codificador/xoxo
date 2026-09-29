<?php

use App\Domain\Identity\Actions\ChangeUserRole;
use App\Enums\AuditAction;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

/*
| Accounts and roles in the CMS (step 5): only admins see them and give
| roles; every change is audited and the platform always keeps an admin.
*/

it('lists accounts with their role, searchable and filterable by role', function () {
    $admin = staff(Role::Admin);
    staff(Role::Editor)->update(['name' => 'Ana Editora', 'email' => 'ana@example.com']);
    staff(Role::Student)->update(['name' => 'Beto Estudiante']);
    User::factory()->create(['name' => 'Carla Sin Rol']);

    $this->actingAs($admin)->get('/admin/users')
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/index')
            ->has('users', 4)
            ->where('pagination.total', 4)
            ->where('users.0.name', 'Ana Editora')
            ->where('users.0.role', 'EDITOR')
            ->where('users.2.name', 'Carla Sin Rol')
            ->where('users.2.role', null));

    // Search ignores case and matches name, email or username.
    $this->actingAs($admin)->get('/admin/users?q=ANA@EXAMPLE')
        ->assertInertia(fn (Assert $page) => $page->has('users', 1)->where('users.0.name', 'Ana Editora')->where('filters.q', 'ANA@EXAMPLE'));

    $this->actingAs($admin)->get('/admin/users?role=STUDENT')
        ->assertInertia(fn (Assert $page) => $page->has('users', 1)->where('users.0.name', 'Beto Estudiante')->where('filters.role', 'STUDENT'));

    // An unknown role is ignored rather than failing.
    $this->actingAs($admin)->get('/admin/users?role=ROOT')
        ->assertInertia(fn (Assert $page) => $page->has('users', 4)->where('filters.role', null));
});

it('paginates accounts keeping the filters', function () {
    $admin = staff(Role::Admin);
    User::factory()->count(30)->create()->each->assignRole(Role::Student->value);

    $this->actingAs($admin)->get('/admin/users?role=STUDENT&page=2')
        ->assertInertia(fn (Assert $page) => $page
            ->has('users', 5)
            ->where('pagination.page', 2)
            ->where('pagination.pages', 2)
            ->where('pagination.total', 30)
            ->where('pagination.next', null)
            ->where('pagination.previous', fn (string $url) => str_contains($url, 'role=STUDENT') && str_contains($url, 'page=1')));
});

it('keeps accounts to admins', function (Role $role) {
    $user = staff($role);
    $other = staff(Role::Student);

    $this->actingAs($user)->get('/admin/users')->assertForbidden();
    $this->actingAs($user)->get("/admin/users/{$other->id}/edit")->assertForbidden();
    $this->actingAs($user)->put("/admin/users/{$other->id}/role", ['role' => 'ADMIN'])->assertForbidden();

    expect($other->fresh()->getRoleNames()->all())->toBe(['STUDENT']);
})->with([Role::Editor, Role::Instructor, Role::Student]);

it('sends guests to log in', function () {
    $this->get('/admin/users')->assertRedirect('/login');
});

it('shows an account with what the admin can do with it', function () {
    $admin = staff(Role::Admin);
    $editor = staff(Role::Editor);

    $this->actingAs($admin)->get("/admin/users/{$editor->id}/edit")
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/edit')
            ->where('user.id', $editor->id)
            ->where('user.role', 'EDITOR')
            ->where('user.lessons_authored', 0)
            ->where('roles', ['ADMIN', 'EDITOR', 'INSTRUCTOR', 'STUDENT'])
            ->where('can.change_role', true)
            ->where('can.view_audit', true)
            ->where('role_locked', null));
});

it('changes a role and audits it as assigned or revoked', function () {
    $admin = staff(Role::Admin);
    $user = staff(Role::Student);

    $this->actingAs($admin)
        ->put("/admin/users/{$user->id}/role", ['role' => 'EDITOR'])
        ->assertSessionHasNoErrors()
        ->assertRedirect()
        ->assertInertiaFlash('toast.message', "Rol de {$user->name} actualizado: Editor.");

    expect($user->fresh()->getRoleNames()->all())->toBe(['EDITOR']);
    $assigned = AuditLog::query()->where('auditable_type', 'user')->where('auditable_id', $user->id)->sole();
    expect($assigned->action)->toBe(AuditAction::RoleAssigned)
        ->and($assigned->user_id)->toBe($admin->id)
        ->and($assigned->changes)->toEqual(['before' => ['role' => 'STUDENT'], 'after' => ['role' => 'EDITOR']]);

    // Back to STUDENT takes the staff role away.
    $this->actingAs($admin)->put("/admin/users/{$user->id}/role", ['role' => 'STUDENT'])->assertSessionHasNoErrors();
    expect(AuditLog::query()->where('auditable_id', $user->id)->latest('id')->first()->action)->toBe(AuditAction::RoleRevoked);

    // The same role again changes nothing and logs nothing.
    $this->actingAs($admin)->put("/admin/users/{$user->id}/role", ['role' => 'STUDENT'])->assertSessionHasNoErrors();
    expect(AuditLog::query()->where('auditable_id', $user->id)->count())->toBe(2);
});

it('leaves a single role to accounts that had several', function () {
    $user = User::factory()->create()->assignRole(Role::Student->value, Role::Instructor->value);

    app(ChangeUserRole::class)->handle($user, Role::Instructor);

    expect($user->fresh()->getRoleNames()->all())->toBe(['INSTRUCTOR']);
});

it('rejects unknown roles', function () {
    $user = staff(Role::Student);

    $this->actingAs(staff(Role::Admin))
        ->put("/admin/users/{$user->id}/role", ['role' => 'ROOT'])
        ->assertSessionHasErrors('role');
});

it('does not let anyone change their own role', function () {
    $admin = staff(Role::Admin);

    $this->actingAs($admin)->get("/admin/users/{$admin->id}/edit")
        ->assertInertia(fn (Assert $page) => $page
            ->where('can.change_role', false)
            ->where('role_locked', 'No puedes cambiar tu propio rol: pídeselo a otra persona con rol Admin.'));

    $this->actingAs($admin)->put("/admin/users/{$admin->id}/role", ['role' => 'STUDENT'])->assertForbidden();
    expect($admin->fresh()->hasRole('ADMIN'))->toBeTrue();
});

it('always keeps an admin', function () {
    $first = staff(Role::Admin);
    $second = staff(Role::Admin);

    // With two, one admin can demote the other.
    $this->actingAs($first)->put("/admin/users/{$second->id}/role", ['role' => 'EDITOR'])->assertSessionHasNoErrors();

    // The last one keeps the role, whoever asks (a command, a race between
    // two admins demoting each other).
    expect(fn () => app(ChangeUserRole::class)->handle($first, Role::Editor))
        ->toThrow(ValidationException::class, 'Es la única cuenta con rol Admin');
    expect($first->fresh()->hasRole('ADMIN'))->toBeTrue()
        ->and(AuditLog::query()->where('auditable_id', $first->id)->where('auditable_type', 'user')->exists())->toBeFalse();
});

it('shows users and audit in the admin menu only to who may open them', function () {
    // The dashboard sends its own "can": the shared menu flags must survive it.
    $this->actingAs(staff(Role::Admin))->get('/admin')
        ->assertInertia(fn (Assert $page) => $page->where('access.users', true)->where('access.audit', true)->where('can.view_audit', true));
    $this->actingAs(staff(Role::Editor))->get('/admin/resources')
        ->assertInertia(fn (Assert $page) => $page->where('access.users', false)->where('access.audit', true)->has('can.create'));
    $this->actingAs(staff(Role::Instructor))->get('/admin')
        ->assertInertia(fn (Assert $page) => $page->where('access.admin', true)->where('access.users', false)->where('access.audit', false));
});
