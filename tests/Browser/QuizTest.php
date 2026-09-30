<?php

use App\Enums\ContentStatus;
use App\Enums\ProgressStatus;
use App\Enums\Role;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\User;

/*
| Quizzes on the real sample curriculum (§35, ADR-036): an editor writes
| the quiz of a lesson in its Quiz tab and publishes it; a learner takes
| it, the server grades it, and passing it at the mastery score masters
| the lesson once it is completed.
*/

beforeEach(function () {
    $this->artisan('content:import')->assertSuccessful();
});

/**
 * Types into a rich text editor of the open question (0: statement,
 * 1: explanation). A new question mounts its editors a moment after it
 * opens: this waits for them and focuses through TipTap, so no key lands
 * in the editor of the question that just closed.
 */
function typeInEditor(mixed $page, int $question, int $index, string $text): void
{
    $card = "li:has(button[aria-label=\"Cerrar la pregunta {$question}\"])";
    $page->script(<<<JS
        async () => {
            for (let tries = 0; tries < 100; tries++) {
                const card = document.querySelector('button[aria-label="Cerrar la pregunta {$question}"]')?.closest('li');
                const editor = card?.querySelectorAll('.tiptap')[{$index}]?.editor;
                if (editor) {
                    editor.commands.focus('end');
                    return;
                }
                await new Promise((resolve) => setTimeout(resolve, 50));
            }
            throw new Error('The editor of question {$question} did not mount.');
        }
        JS);
    $page->typeSlowly("{$card} .tiptap >> nth={$index}", $text, 1);
}

it('writes a quiz, publishes it, and a learner passes it and masters the lesson', function () {
    $this->actingAs(staff(Role::Editor));
    $slug = 'ramas-merge-y-rebase';

    $page = visit("/admin/lessons/{$slug}/quiz")
        ->assertSee('Esta lección aún no tiene quiz')
        ->fill('input[id$="-time"]', '10')
        // A single choice question: it opens as it is added.
        ->click('button:has-text("Añadir pregunta")');
    typeInEditor($page, 1, 0, '¿Qué comando reescribe la historia de una rama sobre otra?');
    $page->fill('input[aria-label="Opción 1"]', 'git merge')
        ->fill('input[aria-label="Opción 2"]', '`git rebase`')
        ->click('input[aria-label="La opción 2 es correcta"]')
        ->click('button[aria-label="Quitar la opción 3"]');
    typeInEditor($page, 1, 1, 'Rebase reaplica los commits sobre otra base; merge los une con un commit nuevo.');

    // A true or false statement.
    $page->select('select[id$="-new-type"]', 'Verdadero o falso')
        ->click('button:has-text("Añadir pregunta")');
    typeInEditor($page, 2, 0, 'Un merge de avance rápido no crea un commit nuevo.');
    typeInEditor($page, 2, 1, 'Solo mueve el puntero de la rama.');

    $page->press('Guardar cambios')
        ->waitForText('Quiz creado como borrador')
        ->click('aside button:has-text("Publicar")')
        ->click('[role=dialog] button:has-text("Publicar")')
        ->waitForText('Quiz publicado')
        ->assertNoJavaScriptErrors();

    $quiz = Quiz::query()->with('questions')->sole();
    expect($quiz->status)->toBe(ContentStatus::Published)
        ->and($quiz->time_limit_seconds)->toBe(600)
        ->and($quiz->questions)->toHaveCount(2)
        ->and($quiz->questions[1]->prompt->plainText())->toBe('Un merge de avance rápido no crea un commit nuevo.')
        ->and($quiz->questions[1]->explanation->plainText())->toBe('Solo mueve el puntero de la rama.')
        ->and($quiz->questions[0]->payload['options'])->toEqual([
            ['text' => 'git merge', 'correct' => false],
            ['text' => '`git rebase`', 'correct' => true],
        ]);

    $learner = User::factory()->create();
    $this->actingAs($learner);

    $lesson = visit("/lessons/{$slug}")
        ->assertSee('Comprueba lo que aprendiste')
        ->click('a:has-text("Ir al quiz")')
        ->assertPathIs("/lessons/{$slug}/quiz")
        ->assertSee('Aún no lo has intentado.')
        ->press('Empezar el quiz')
        ->waitForText('Respondidas: 0 de 2')
        ->assertSee('Tiempo restante')
        ->click('label:has-text("git rebase")')
        ->click('label:has-text("Verdadero")')
        ->assertSee('Respondidas: 2 de 2')
        ->press('Enviar respuestas')
        ->waitForText("Aprobaste con 100\u{a0}%")
        ->assertSee('Respondiste bien todas las preguntas.')
        ->assertSee('la lección queda dominada en cuanto la marques como completada')
        ->assertNoJavaScriptErrors();

    // Completing the lesson with the quiz passed masters it.
    $lesson->click('a:has-text("Volver a la lección")')
        ->press('Marcar como completada')
        ->waitForText('La dominaste')
        ->assertSee('Dominado');

    expect(LessonProgress::query()->whereBelongsTo($learner)->value('status'))->toBe(ProgressStatus::Mastered);
});
