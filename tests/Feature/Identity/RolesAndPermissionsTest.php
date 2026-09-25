<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('creates the four roles with the documented permission matrix', function (Role $role, array $expected) {
    $granted = RoleModel::findByName($role->value)->permissions->pluck('name')->sort()->values()->all();

    expect($granted)->toBe(collect($expected)->map->value->sort()->values()->all());
})->with([
    'admin' => [Role::Admin, Permission::cases()],
    'editor' => [Role::Editor, Permission::grantedTo(Role::Editor)],
    'instructor' => [Role::Instructor, Permission::grantedTo(Role::Instructor)],
    'student' => [Role::Student, []],
]);

it('keeps publishing out of the instructor role', function () {
    $instructor = User::factory()->create()->assignRole(Role::Instructor->value);

    expect($instructor->can(Permission::ContentSubmitReview->value))->toBeTrue()
        ->and($instructor->can(Permission::ContentPublish->value))->toBeFalse()
        ->and($instructor->can(Permission::UsersManage->value))->toBeFalse();
});

it('is idempotent', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(RoleModel::count())->toBe(4)
        ->and(Spatie\Permission\Models\Permission::count())->toBe(count(Permission::cases()));
});

it('gives new registrations the student role', function () {
    $this->post(route('register.store'), [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(User::firstWhere('email', 'ada@example.test')->hasRole(Role::Student->value))->toBeTrue();
});
