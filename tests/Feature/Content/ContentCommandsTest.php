<?php

use App\Enums\Role;
use App\Models\Lesson;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\Support\ContentPackageFixture;

it('validates the real package from the command line', function () {
    $this->artisan('content:validate')
        ->expectsOutputToContain('6 lecciones')
        ->expectsOutputToContain('Sin problemas.')
        ->assertSuccessful();
});

it('fails validation with the list of problems', function () {
    $fixture = ContentPackageFixture::valid()->lesson('03-rota', ['key' => 'base.rota', 'slug' => 'rota', 'skills' => [['key' => 'fantasma', 'weight' => 1]]]);

    $this->artisan('content:validate', ['path' => $fixture->path])
        ->expectsOutputToContain('referencia a skill inexistente "fantasma"')
        ->assertFailed();

    $fixture->cleanup();
});

it('supports a dry run from the command line', function () {
    $this->artisan('content:import', ['--dry-run' => true])
        ->expectsOutputToContain('Simulación: no se guardó ningún cambio.')
        ->assertSuccessful();

    expect(Lesson::count())->toBe(0);
});

it('seeds roles, demo users and the initial curriculum', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->firstWhere('email', 'editor@ai-roadmap.test')->hasRole(Role::Editor->value))->toBeTrue()
        ->and(Lesson::query()->visibleToLearners()->count())->toBe(6);
});
