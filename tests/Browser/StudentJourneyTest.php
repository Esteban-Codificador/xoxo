<?php

use App\Enums\ProgressStatus;
use App\Models\LessonProgress;
use App\Models\User;

/*
| The learner flow of master spec §78 in a real browser, on the real sample
| curriculum (content/ai-engineer), not on factory data.
|
| Run with scripts/test-browser.sh (it also stops the Playwright server
| that Pest leaves running).
*/

beforeEach(function () {
    $this->artisan('content:import')->assertSuccessful();
});

it('registers a new learner and asks to verify the email', function () {
    visit('/register')
        ->assertSee('Crea tu cuenta')
        ->fill('name', 'Ada Lovelace')
        ->fill('email', 'ada@example.test')
        ->fill('password', 'contraseña-larga-123')
        ->fill('password_confirmation', 'contraseña-larga-123')
        ->press('Crear cuenta')
        ->assertSee('Verifica tu correo')
        ->assertNoJavaScriptErrors();

    expect(User::firstWhere('email', 'ada@example.test')->hasRole('STUDENT'))->toBeTrue();
});

it('studies a lesson, completes it and sees progress and unlocks change', function () {
    $user = User::factory()->create(['email' => 'estudiante@example.test']);

    // Log in through the real form.
    $page = visit('/login')
        ->fill('email', 'estudiante@example.test')
        ->fill('password', 'password')
        ->press('Iniciar sesión')
        ->assertPathIs('/dashboard')
        ->assertSee('Tu ruta de aprendizaje')
        ->assertSee('Empieza por aquí')
        ->assertSee('Qué es AI Engineering');

    // The third lesson requires the first: it is locked, and says why.
    $page->navigate('/lessons/ciclo-de-vida-de-un-sistema-de-ia')
        ->assertSee('Antes de esta lección conviene completar')
        ->assertSee('Puedes seguir igual');

    // Start where the dashboard points.
    $page->navigate('/dashboard')
        ->click('Empezar')
        ->assertPathIs('/lessons/que-es-ai-engineering')
        ->assertSee('Por qué importa')
        ->assertSee('Al terminar podrás')
        ->waitForText('En curso')
        ->press('Marcar como completada')
        ->waitForText('Completaste esta lección')
        ->assertSee('Completado');

    $progress = LessonProgress::query()->whereBelongsTo($user)->sole();
    expect($progress->status)->toBe(ProgressStatus::Completed)
        ->and($progress->completed_version_id)->not->toBeNull();

    // The lesson that required it no longer warns.
    $page->navigate('/lessons/ciclo-de-vida-de-un-sistema-de-ia')
        ->assertDontSee('Antes de esta lección conviene completar');

    // The dashboard recommends the next lesson of the same track, and says why.
    $page->navigate('/dashboard')
        ->assertSee('Recomendado para ti')
        ->assertSee('Siguiente lección')
        ->assertSee('Es la siguiente lección disponible de Orientación: el rol de AI Engineer.')
        ->assertDontSee('Empieza por aquí');

    // Its skill grew by the weight of that lesson: 2 of the 6 of its lessons.
    $page->navigate('/skills')
        ->assertSee('Completadas: 0 de 3')
        ->assertSee('1 de 3 lecciones completadas')
        ->click('[aria-label="Ver Ciclo de vida de sistemas de IA"]')
        ->assertPathIs('/skills/ai-lifecycle')
        ->assertSee('33 %')
        ->assertSee('Lecciones que la desarrollan')
        ->assertSee('Qué es AI Engineering');

    // The track shows the progress and suggests the next lesson.
    $page->navigate('/roadmaps/ai-engineer/tracks/orientacion')
        ->assertSee('1 de 3 lecciones')
        ->assertSee('Continuar:');

    // Unmarking by mistake is reversible.
    $page->navigate('/lessons/que-es-ai-engineering')
        ->press('Desmarcar')
        ->waitForText('Marcar como completada')
        ->assertNoJavaScriptErrors();

    expect($progress->refresh()->status)->toBe(ProgressStatus::InProgress);
});

it('keeps drafts and unknown lessons out of reach', function () {
    $this->actingAs(User::factory()->create());

    visit('/lessons/no-existe')
        ->assertSee('No encontramos esta página');
});
