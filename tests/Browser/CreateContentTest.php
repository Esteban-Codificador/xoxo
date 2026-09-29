<?php

use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\Lesson;
use App\Models\Track;

/*
| Creating curriculum in a real browser (step 4d): a new track, a module in
| it and a lesson in that module, which opens in the editor with the body
| template. Everything is a draft learners do not see.
*/

beforeEach(function () {
    $this->artisan('content:import')->assertSuccessful();
});

it('creates a track, a module and a lesson that opens in the editor', function () {
    $this->actingAs(staff(Role::Editor));

    $page = visit('/admin/tracks')
        ->click('a:has-text("Nuevo track")')
        ->assertPathIs('/admin/tracks/create')
        ->fill('title', 'Python para IA')
        ->assertValue('[name=slug]', 'python-para-ia')
        ->fill('summary', 'Python como lenguaje de trabajo del roadmap.')
        ->fill('why_it_matters', 'Todo el ecosistema de IA se escribe en Python.')
        ->press('Crear track')
        ->waitForText('Track creado como borrador');

    $track = Track::firstWhere('slug', 'python-para-ia');
    expect($track->status)->toBe(ContentStatus::Draft);
    $page->assertPathIs("/admin/tracks/{$track->id}/edit");

    // A module, from the track's module list.
    $page->click('button:has-text("Añadir módulo")')
        ->fill('[role=dialog] [name=title]', 'Sintaxis básica')
        ->fill('[role=dialog] [name=summary]', 'Variables, tipos y control de flujo.')
        ->click('[role=dialog] button[type=submit]')
        ->waitForText('Módulo «Sintaxis básica» creado como borrador')
        ->assertSee('#sintaxis-basica');

    // A lesson in it: the module comes preselected.
    $page->click('[aria-label="Añadir una lección a Sintaxis básica"]')
        ->assertPathIs('/admin/lessons/create')
        ->fill('title', 'Variables y tipos')
        ->fill('summary', 'Qué guarda una variable y cómo cambia su tipo.')
        ->fill('why_it_matters', 'Todo programa empieza por sus datos.')
        ->press('Crear lección')
        ->waitForText('Lección creada como borrador')
        ->assertPathIs('/admin/lessons/variables-y-tipos/edit')
        ->assertSee('Cómo funciona')
        ->assertSee('Errores comunes')
        ->assertNoJavaScriptErrors();

    $lesson = Lesson::firstWhere('slug', 'variables-y-tipos');
    expect($lesson->module->slug)->toBe('sintaxis-basica')
        ->and($lesson->status)->toBe(ContentStatus::Draft);
});
