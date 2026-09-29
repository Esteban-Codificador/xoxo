<?php

use App\Enums\Role;
use App\Models\Lesson;

/*
| The review flow (ADR-032) in a real browser on the sample curriculum: an
| instructor edits their lesson and sends it for review, an editor returns
| it with a comment, the instructor sends it again and the editor
| publishes it.
*/

beforeEach(function () {
    $this->artisan('content:import')->assertSuccessful();
});

it('goes from the instructor to the editor and back until it is published', function () {
    $instructor = staff(Role::Instructor);
    $editor = staff(Role::Editor);
    Lesson::query()->where('slug', 'ramas-merge-y-rebase')->update(['created_by' => $instructor->id]);
    $edit = '/admin/lessons/ramas-merge-y-rebase/edit';

    // The instructor saves a change and sends it: the lesson is frozen for them.
    $this->actingAs($instructor);
    visit($edit)
        ->fill('summary', 'Qué es realmente una rama en Git, cómo se integran cambios con merge o rebase y cuándo conviene cada uno sin reescribir la historia compartida.')
        ->press('Guardar cambios')
        ->waitForText('Todo guardado.')
        ->fill('note', 'Reescribí el resumen.')
        ->press('Enviar a revisión')
        ->waitForText('Enviada a revisión')
        ->assertSee('En revisión: no puedes editarla')
        ->assertDontSee('Guardar cambios')
        ->assertNoJavaScriptErrors();

    // The editor finds it in the queue and returns it with a comment.
    $this->actingAs($editor);
    visit('/admin/reviews')
        ->assertSee('Ramas, merge y rebase')
        ->assertSee('Reescribí el resumen.')
        ->click('[aria-label="Revisar Ramas, merge y rebase"]')
        ->assertPathIs($edit)
        ->click('button:has-text("Devolver con comentarios")')
        ->fill('comment', 'Menciona el rebase interactivo en el resumen.')
        ->click('[role=dialog] button:has-text("Devolver con comentarios")')
        ->waitForText('Lección devuelta con tus comentarios.')
        ->assertNoJavaScriptErrors();

    // The instructor reads the comment and sends it again.
    $this->actingAs($instructor);
    visit($edit)
        ->assertSee('Menciona el rebase interactivo en el resumen.')
        ->press('Enviar a revisión')
        ->waitForText('Enviada a revisión');

    // The editor publishes it: the queue is empty again.
    $this->actingAs($editor);
    visit($edit)
        ->fill('change_note', 'Resumen reescrito tras la revisión.')
        ->press('Publicar versión 2')
        ->waitForText('Versión 2 publicada')
        ->navigate('/admin/reviews')
        ->assertSee('No hay lecciones esperando revisión.')
        ->assertNoJavaScriptErrors();
});
