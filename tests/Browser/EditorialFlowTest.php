<?php

use App\Enums\Role;
use App\Models\Lesson;
use App\Models\User;

/*
| The editorial flow of master spec §78 in a real browser: an editor
| corrects a lesson of the real sample curriculum in the CMS (form fields
| and the TipTap body), reviews the diff, publishes it, and a learner reads
| the new version.
*/

beforeEach(function () {
    $this->artisan('content:import')->assertSuccessful();
});

it('edits a lesson, publishes a new version and the learner reads it', function () {
    $editor = User::factory()->create()->assignRole(Role::Editor->value);
    $this->actingAs($editor);

    $page = visit('/admin/lessons')
        ->assertSee('Lecciones')
        ->assertSee('Git: commits, árbol de trabajo y staging')
        ->click('[aria-label="Editar Git: commits, árbol de trabajo y staging"]')
        ->assertPathIs('/admin/lessons/git-commits-arbol-de-trabajo-y-staging/edit')
        ->assertSee('Cumple todos los requisitos.')
        ->assertSee('No hay cambios por publicar.')
        ->assertPresent('[role=toolbar]');

    // A structured field and the rich body, typed with the keyboard.
    $page->fill('summary', 'El modelo mental de Git: árbol de trabajo, staging y repositorio, y commits como instantáneas encadenadas por hash.')
        ->click('.tiptap h2 >> nth=0')
        ->keys('.tiptap', ['End', 'Enter'])
        ->typeSlowly('.tiptap', 'Frase añadida en el CMS.', 5)
        ->assertSee('Tienes cambios sin guardar.')
        ->assertSee('Guarda los cambios antes de publicar.')
        ->press('Guardar cambios')
        ->waitForText('Todo guardado.')
        ->assertSee('La copia de trabajo tiene cambios que los estudiantes todavía no ven.');

    // Saving never changes what learners read.
    $lesson = Lesson::firstWhere('slug', 'git-commits-arbol-de-trabajo-y-staging');
    expect($lesson->hasUnpublishedChanges())->toBeTrue()
        ->and($lesson->publishedVersion->version)->toBe(1);

    // What will change for learners, as a diff of the saved working copy.
    $page->click('a:has-text("Ver cambios sin publicar")')
        ->waitForText('Cambios sin publicar')
        ->assertSee('frente a la versión 1')
        ->assertSee('Resumen')
        ->assertSee('Frase añadida en el CMS.')
        ->click('a:has-text("Volver al editor")')
        ->waitForText('Requisitos para publicar');

    $page->fill('change_note', 'Resumen más preciso y una frase nueva.')
        ->press('Publicar versión 2')
        ->waitForText('Versión 2 publicada')
        ->assertSee('Resumen más preciso y una frase nueva.')
        ->assertSee('Los estudiantes ven exactamente esta copia de trabajo.')
        ->assertNoJavaScriptErrors();

    // The history keeps each version with its changes.
    $page->click('li a:has-text("Versión 2")')
        ->waitForText('Cambios respecto a la versión 1')
        ->assertSee('Frase añadida en el CMS.')
        ->assertSee('Contenido de esta versión')
        ->assertNoJavaScriptErrors();

    // A learner reads version 2.
    $this->actingAs(User::factory()->create());

    visit('/lessons/git-commits-arbol-de-trabajo-y-staging')
        ->assertSee('Frase añadida en el CMS.')
        ->assertSee('commits como instantáneas encadenadas por hash')
        ->assertSee('Versión 2')
        ->assertNoJavaScriptErrors();
});

it('keeps learners out of the CMS', function () {
    $this->actingAs(User::factory()->create());

    visit('/admin/lessons')
        ->assertSee('No tienes acceso a esta página');
});
