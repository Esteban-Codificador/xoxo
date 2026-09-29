<?php

use App\Enums\Role;
use App\Models\Track;
use App\Models\User;

/*
| Tracks in the CMS on the real sample curriculum: tracks are not versioned,
| so a saved change is what the learner reads; the prerequisite graph
| refuses cycles with a readable path.
*/

beforeEach(function () {
    $this->artisan('content:import')->assertSuccessful();
});

it('edits a track, refuses a prerequisite cycle and the learner sees the change', function () {
    $this->actingAs(staff(Role::Editor));
    $orientation = Track::firstWhere('slug', 'orientacion');

    $page = visit('/admin/tracks')
        ->assertSee('Punto de partida')
        ->click('[aria-label="Editar Fundamentos de computación"]')
        ->assertSee('Datos del track')
        ->fill('summary', 'Programación, estructuras de datos, algoritmos y Git: la base de todo el roadmap.')
        ->press('Guardar cambios')
        ->waitForText('Track guardado')
        ->assertSee('Todo guardado.');

    // Orientation is the starting point: requiring Fundamentos would close a cycle.
    $page->navigate("/admin/tracks/{$orientation->id}/edit")
        ->assertSee('Sin prerrequisitos: es un punto de partida.')
        ->select('select[id$="-add"]', 'Fundamentos de computación')
        ->press('Añadir')
        ->press('Guardar prerrequisitos')
        ->waitForText('Ese cambio crearía un ciclo de prerrequisitos')
        ->assertNoJavaScriptErrors();

    expect($orientation->prerequisites()->count())->toBe(0);

    $this->actingAs(User::factory()->create());

    visit('/roadmaps/ai-engineer/tracks/fundamentos-computacion')
        ->assertSee('la base de todo el roadmap')
        ->assertNoJavaScriptErrors();
});
