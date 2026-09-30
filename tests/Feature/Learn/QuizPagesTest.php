<?php

use App\Enums\ContentStatus;
use App\Enums\UnlockPolicy;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
| The learner's side of a quiz (§35, ADR-036): the card on the lesson, the
| quiz page, taking an attempt and its review. The answer never reaches
| the page before grading.
*/

beforeEach(function () {
    $this->lesson = publishedLesson(['slug' => 'ramas', 'title' => 'Ramas']);
    $this->quiz = Quiz::factory()->for($this->lesson)->create(['title' => 'Quiz de ramas']);
    QuizQuestion::factory()->for($this->quiz)->create(['position' => 1]);
    QuizQuestion::factory()->for($this->quiz)->ordering()->create(['position' => 2]);
    QuizQuestion::factory()->for($this->quiz)->matching()->create(['position' => 3]);
    $this->user = User::factory()->create();
});

it('shows the published quiz on its lesson and hides a draft one', function () {
    $this->actingAs($this->user)->get('/lessons/ramas')
        ->assertInertia(fn (Assert $page) => $page
            ->where('quiz.title', 'Quiz de ramas')
            ->where('quiz.questions', 3)
            ->where('quiz.pass_threshold', 70)
            ->where('quiz.mastery_threshold', 90)
            ->where('quiz.best_score', null));

    $this->quiz->update(['status' => ContentStatus::Draft]);

    $this->get('/lessons/ramas')->assertInertia(fn (Assert $page) => $page->where('quiz', null));
    $this->get('/lessons/ramas/quiz')->assertNotFound();
    $this->post('/lessons/ramas/quiz/attempts')->assertNotFound();
});

it('answers 404 for a lesson without a quiz', function () {
    publishedLesson(['slug' => 'sin-quiz']);

    $this->actingAs($this->user)->get('/lessons/sin-quiz/quiz')->assertNotFound();
});

it('starts an attempt and shows its questions without the answers', function () {
    $this->actingAs($this->user)->get('/lessons/ramas/quiz')
        ->assertInertia(fn (Assert $page) => $page
            ->component('quizzes/show')
            ->where('open_attempt', null)
            ->where('attempts', [])
            ->where('blocked', null));

    $response = $this->post('/lessons/ramas/quiz/attempts');
    $attempt = QuizAttempt::query()->sole();
    $response->assertRedirect("/quiz-attempts/{$attempt->id}");

    $page = $this->get("/quiz-attempts/{$attempt->id}");
    $page->assertInertia(fn (Assert $page) => $page
        ->component('quizzes/attempt')
        ->where('attempt.open', true)
        ->has('questions', 3)
        ->has('questions.0.content.options.0', fn (Assert $option) => $option->hasAll(['id', 'text'])->missing('correct'))
        ->missing('questions.0.solution')
        ->missing('questions.0.explanation')
        ->has('questions.1.content.items', 3)
        ->has('questions.2.content.right', 3));

    // Nothing in the page says what is right: no flags, no solutions.
    expect(json_encode($page->viewData('page')['props']))->not->toContain('"correct"')->not->toContain('solution');

    // The quiz page offers to resume it rather than start another.
    $this->get('/lessons/ramas/quiz')->assertInertia(fn (Assert $page) => $page->where('open_attempt.id', $attempt->id));
});

it('grades the answers sent and shows the review, keeping the right answers until the learner passes', function () {
    $this->actingAs($this->user)->post('/lessons/ramas/quiz/attempts');
    $attempt = QuizAttempt::query()->sole();

    $this->put("/quiz-attempts/{$attempt->id}", ['answers' => [
        $this->quiz->questions[0]->id => ['choice' => 'nada'],
    ]])->assertRedirect("/quiz-attempts/{$attempt->id}");

    $this->get("/quiz-attempts/{$attempt->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('attempt.open', false)
            ->where('attempt.score', 0)
            ->where('attempt.passed', false)
            ->where('revealed', false)
            ->where('can_retry', true)
            ->where('questions.0.correct', false)
            ->where('questions.0.answer', null)
            ->where('questions.0.solution', null)
            ->has('questions.0.explanation'));
});

it('reveals the right answers once no attempts are left', function () {
    $this->quiz->update(['max_attempts' => 1]);
    $this->actingAs($this->user)->post('/lessons/ramas/quiz/attempts');
    $attempt = QuizAttempt::query()->sole();
    $this->put("/quiz-attempts/{$attempt->id}", ['answers' => []]);

    $this->get("/quiz-attempts/{$attempt->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('revealed', true)
            ->where('can_retry', false)
            ->where('attempts_left', 0)
            ->has('questions.1.solution.order', 3));

    $this->post('/lessons/ramas/quiz/attempts')->assertSessionHasErrors('quiz');
});

it('keeps an attempt private to its learner', function () {
    $this->actingAs($this->user)->post('/lessons/ramas/quiz/attempts');
    $attempt = QuizAttempt::query()->sole();

    $this->actingAs(User::factory()->create())->get("/quiz-attempts/{$attempt->id}")->assertNotFound();
    $this->put("/quiz-attempts/{$attempt->id}", ['answers' => []])->assertNotFound();

    expect($attempt->refresh()->isOpen())->toBeTrue();
});

it('does not let a learner start the quiz of a locked lesson on a STRICT roadmap', function () {
    $this->lesson->module->track->roadmap->update(['unlock_policy' => UnlockPolicy::Strict]);
    $first = publishedLesson(['slug' => 'primero', 'position' => 0], $this->lesson->module);
    $this->lesson->prerequisites()->attach($first, ['kind' => 'REQUIRED']);

    $this->actingAs($this->user)->get('/lessons/ramas/quiz')
        ->assertInertia(fn (Assert $page) => $page->where('blocked', __('progress.locked')));
    $this->post('/lessons/ramas/quiz/attempts')->assertForbidden();
});

it('shows a timed attempt with the seconds left on the server clock', function () {
    $this->quiz->update(['time_limit_seconds' => 600]);
    $this->actingAs($this->user)->post('/lessons/ramas/quiz/attempts');
    $attempt = QuizAttempt::query()->sole();
    $this->travel(100)->seconds();

    $this->get("/quiz-attempts/{$attempt->id}")->assertInertia(fn (Assert $page) => $page->where('attempt.seconds_left', 500));

    // Out of time and never sent: the next visit finds it graded as timed out.
    $this->travel(20)->minutes();
    $this->get("/quiz-attempts/{$attempt->id}")->assertInertia(fn (Assert $page) => $page
        ->where('attempt.open', false)
        ->where('attempt.timed_out', true));
});

it('limits how fast attempts are started and sent', function () {
    $this->actingAs($this->user);

    foreach (range(1, 20) as $ignored) {
        $this->post('/lessons/ramas/quiz/attempts');
    }

    $this->post('/lessons/ramas/quiz/attempts')->assertTooManyRequests();
});
