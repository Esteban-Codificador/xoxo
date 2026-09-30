<?php

use App\Enums\Role;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;

/*
| The second E2E flow of master spec §78, end to end on the real sample
| curriculum: an editor logs in, creates a lesson, writes it until it meets
| the publishing contract, publishes it, and a learner reads it. The exit
| criterion of phase 5 ("an editor can create and publish a lesson that
| learners see right away").
*/

beforeEach(function () {
    $this->artisan('content:import')->assertSuccessful();
});

/** About 400 characters: one section of the body, typed like an author. */
function sectionText(string $topic): string
{
    return str_repeat("{$topic} guarda los cambios del árbol de trabajo en una pila y deja el directorio limpio para cambiar de rama. ", 4);
}

it('logs in, creates a lesson, publishes it and a learner reads it', function () {
    $editor = staff(Role::Editor);
    $module = Module::firstWhere('slug', 'git-y-colaboracion');

    $page = visit('/login')
        ->fill('email', $editor->email)
        ->fill('password', 'password')
        ->press('Iniciar sesión')
        ->waitForText('Administración')
        ->click('a:has-text("Administración")')
        ->click('[data-sidebar=menu-button]:has-text("Lecciones")')
        ->click('a:has-text("Nueva lección")')
        ->assertPathIs('/admin/lessons/create')
        ->select('module_id', (string) $module->id)
        ->fill('title', 'Git stash')
        ->fill('summary', 'Guardar cambios a medias con git stash para cambiar de rama sin hacer un commit incompleto.')
        ->fill('why_it_matters', 'En equipo interrumpes tu trabajo a menudo: stash te deja atender lo urgente sin perder lo que llevabas.')
        ->press('Crear lección')
        ->waitForText('Lección creada como borrador')
        ->assertPathIs('/admin/lessons/git-stash/edit');

    // Two objectives and text under four sections of the template, practice included.
    $page->click('button:has-text("Añadir objetivo")')
        ->click('button:has-text("Añadir objetivo")')
        ->fill('input[id$="-objective-0"]', 'Guardar y recuperar cambios con git stash')
        ->fill('input[id$="-objective-1"]', 'Elegir entre stash y un commit temporal');

    $write = function (int $heading, string $topic) use ($page): void {
        $page->click(".tiptap h2 >> nth={$heading}")
            ->keys('.tiptap', ['End', 'Enter'])
            ->typeSlowly('.tiptap', sectionText($topic), 1);
    };

    foreach ([0 => 'Git stash', 1 => 'La pila de stash', 3 => 'Un stash olvidado'] as $heading => $topic) {
        $write($heading, $topic);
    }

    // The template's empty "Práctica" heading is not practice.
    $page->press('Guardar cambios')
        ->waitForText('Todo guardado.')
        ->assertSee('Falta la práctica');

    $write(5, 'En tu repositorio, git stash');
    $page->press('Guardar cambios')
        ->waitForText('Todo guardado.')
        ->assertDontSee('Falta la práctica');

    // The last requirement: a skill, in the relations tab.
    $page->click('nav a:has-text("Relaciones")')
        ->select('section:has-text("Skills que desarrolla") select[id$="-add"]', 'Git')
        ->click('button:has-text("Añadir skill")')
        ->press('Guardar cambios')
        ->waitForText('Relaciones guardadas')
        ->click('nav a:has-text("Contenido")')
        ->waitForText('Cumple todos los requisitos.')
        ->press('Publicar versión 1')
        ->waitForText('Versión 1 publicada')
        ->assertNoJavaScriptErrors();

    $lesson = Lesson::firstWhere('slug', 'git-stash');
    expect($lesson->publishedVersion?->version)->toBe(1)
        ->and($lesson->module_id)->toBe($module->id);

    // A learner finds it at the end of its module and reads it.
    $this->actingAs(User::factory()->create());

    visit('/roadmaps/ai-engineer/tracks/'.$module->track->slug)
        ->click('a:has-text("Git stash")')
        ->assertPathIs('/lessons/git-stash')
        ->assertSee('Git stash')
        ->assertSee('Guardar y recuperar cambios con git stash')
        ->assertSee('En tu repositorio, git stash guarda los cambios')
        ->assertNoJavaScriptErrors();
});
