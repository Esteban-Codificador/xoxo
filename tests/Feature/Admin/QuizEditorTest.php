<?php

use App\Enums\AuditAction;
use App\Enums\ContentStatus;
use App\Enums\QuestionType;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizQuestion;
use App\Models\User;
use Database\Factories\QuizQuestionFactory;
use Inertia\Testing\AssertableInertia as Assert;

/*
| The quiz tab of a lesson in the CMS (§35, ADR-036): settings and
| questions saved together, each question checked by its type, and the
| quiz published on its own.
*/

beforeEach(function () {
    $this->lesson = publishedLesson(['slug' => 'ramas', 'title' => 'Ramas']);
    $this->editor = staff(Role::Editor);
});

function quizQuestionRow(array $overrides = []): array
{
    return [
        'id' => null,
        'type' => QuestionType::SingleChoice->value,
        'prompt' => QuizQuestionFactory::text('¿Qué comando integra una rama reescribiendo su historia?')->toArray(),
        'payload' => ['options' => [
            ['text' => '`git merge`', 'correct' => false],
            ['text' => '`git rebase`', 'correct' => true],
        ]],
        'explanation' => QuizQuestionFactory::text('Rebase reaplica los commits sobre otra base.')->toArray(),
        'difficulty' => null,
        'points' => 1,
        ...$overrides,
    ];
}

function quizForm(array $questions, array $overrides = []): array
{
    return [
        'title' => 'Quiz de ramas',
        'description' => null,
        'pass_threshold' => 70,
        'time_limit_minutes' => 10,
        'max_attempts' => null,
        'shuffle_questions' => true,
        'questions' => $questions,
        ...$overrides,
    ];
}

it('creates the quiz of a lesson as a draft with its questions', function () {
    $this->actingAs($this->editor)->get('/admin/lessons/ramas/quiz')
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/lessons/quiz')
            ->where('quiz', null)
            ->where('can.save', true));

    $this->put('/admin/lessons/ramas/quiz', quizForm([
        quizQuestionRow(),
        quizQuestionRow(['type' => 'ORDERING', 'payload' => ['items' => ['add', 'commit', 'push']]]),
    ]))->assertSessionHasNoErrors();

    $quiz = Quiz::query()->sole();
    expect($quiz->status)->toBe(ContentStatus::Draft)
        ->and($quiz->time_limit_seconds)->toBe(600)
        ->and($quiz->created_by)->toBe($this->editor->id)
        ->and($quiz->questions->pluck('type')->all())->toBe([QuestionType::SingleChoice, QuestionType::Ordering])
        ->and($quiz->questions[0]->payload['options'][1])->toEqual(['text' => '`git rebase`', 'correct' => true])
        ->and(AuditLog::query()->where('auditable_type', 'quiz')->where('action', AuditAction::Created)->exists())->toBeTrue()
        ->and(AuditLog::query()->where('auditable_type', 'quiz')->where('action', AuditAction::Updated)->latest('id')->value('changes')['after']['questions']['count'])->toBe(2);

    $this->get('/admin/lessons/ramas/quiz')
        ->assertInertia(fn (Assert $page) => $page
            ->where('quiz.time_limit_minutes', 10)
            ->has('quiz.questions', 2)
            ->where('status_actions', ['PUBLISHED', 'ARCHIVED']));
});

it('reports each problem on the field that has it', function () {
    $this->actingAs($this->editor)
        ->put('/admin/lessons/ramas/quiz', quizForm([
            quizQuestionRow(),
            quizQuestionRow(['payload' => ['options' => [['text' => 'a', 'correct' => true], ['text' => 'b', 'correct' => true]]]]),
            quizQuestionRow(['type' => 'MATCHING', 'payload' => ['pairs' => [['left' => 'a', 'right' => 'x'], ['left' => '', 'right' => 'x']]]]),
            quizQuestionRow(['prompt' => QuizQuestionFactory::text(' ')->toArray()]),
        ]))
        ->assertSessionHasErrors([
            'questions.1.payload.options',
            'questions.2.payload.pairs.1.left',
            'questions.2.payload.pairs.1.right',
            'questions.3.prompt',
        ])
        ->assertSessionDoesntHaveErrors(['questions.0.payload.options']);

    expect(Quiz::query()->exists())->toBeFalse();
});

it('keeps the questions that stay, and a removed one takes its answers along', function () {
    $quiz = Quiz::factory()->for($this->lesson)->create();
    [$kept, $removed] = QuizQuestion::factory()->for($quiz)->count(2)->create()->all();
    $attempt = QuizAttempt::query()->create([
        'user_id' => User::factory()->create()->id, 'quiz_id' => $quiz->id, 'attempt_number' => 1, 'questions' => [$kept->id, $removed->id],
        'started_at' => now(), 'submitted_at' => now(), 'score' => 50, 'points_earned' => 1, 'points_total' => 2, 'passed' => false,
    ]);
    foreach ([$kept, $removed] as $question) {
        QuizAttemptAnswer::query()->create(['quiz_attempt_id' => $attempt->id, 'quiz_question_id' => $question->id, 'answer' => null, 'is_correct' => false, 'points_awarded' => 0]);
    }

    $this->actingAs($this->editor)
        ->put('/admin/lessons/ramas/quiz', quizForm([quizQuestionRow(), quizQuestionRow(['id' => $kept->id, 'points' => 3])]))
        ->assertSessionHasNoErrors();

    $questions = $quiz->questions()->get();
    expect($questions)->toHaveCount(2)
        ->and($questions[1]->id)->toBe($kept->id)
        ->and($questions[1]->points)->toBe(3)
        ->and(QuizQuestion::query()->find($removed->id))->toBeNull()
        ->and(QuizAttemptAnswer::query()->pluck('quiz_question_id')->all())->toBe([$kept->id])
        ->and($attempt->refresh()->score)->toBe(50);
});

it('does not adopt a question of another quiz', function () {
    $foreign = QuizQuestion::factory()->create();

    $this->actingAs($this->editor)->put('/admin/lessons/ramas/quiz', quizForm([quizQuestionRow(['id' => $foreign->id])]));

    expect($foreign->refresh()->quiz_id)->not->toBe(Quiz::query()->whereBelongsTo($this->lesson)->value('id'))
        ->and(Quiz::query()->whereBelongsTo($this->lesson)->sole()->questions)->toHaveCount(1);
});

it('publishes a quiz only with questions, and keeps a published one from being emptied', function () {
    $quiz = Quiz::factory()->draft()->for($this->lesson)->create();
    $this->actingAs($this->editor);

    $this->put("/admin/quizzes/{$quiz->id}/status", ['status' => 'PUBLISHED'])->assertSessionHasErrors('status');

    QuizQuestion::factory()->for($quiz)->create();
    $this->put("/admin/quizzes/{$quiz->id}/status", ['status' => 'PUBLISHED'])->assertSessionHasNoErrors();

    expect($quiz->refresh()->status)->toBe(ContentStatus::Published)
        ->and(AuditLog::query()->where('auditable_type', 'quiz')->where('action', AuditAction::Published)->exists())->toBeTrue();

    $this->put('/admin/lessons/ramas/quiz', quizForm([]))->assertSessionHasErrors('questions');
});

it('lets an instructor edit the quiz of their own lesson only, and not while it is in review', function () {
    $instructor = staff(Role::Instructor);
    $this->actingAs($instructor)->put('/admin/lessons/ramas/quiz', quizForm([quizQuestionRow()]))->assertForbidden();

    $own = publishedLesson(['slug' => 'propia', 'created_by' => $instructor->id]);
    $this->put('/admin/lessons/propia/quiz', quizForm([quizQuestionRow()]))->assertSessionHasNoErrors();

    // Publishing is for editors.
    $quiz = Quiz::query()->whereBelongsTo($own)->sole();
    $this->put("/admin/quizzes/{$quiz->id}/status", ['status' => 'PUBLISHED'])->assertForbidden();

    $own->update(['status' => ContentStatus::Review]);
    $this->put('/admin/lessons/propia/quiz', quizForm([quizQuestionRow()]))->assertForbidden();
    $this->get('/admin/lessons/propia/quiz')->assertInertia(fn (Assert $page) => $page->where('can.save', false));
});

it('lists the quizzes with their lesson and results', function () {
    $quiz = Quiz::factory()->for($this->lesson)->create(['title' => 'Quiz de ramas']);
    QuizQuestion::factory()->for($quiz)->count(3)->create();
    foreach ([60, 90] as $number => $score) {
        QuizAttempt::query()->create([
            'user_id' => User::factory()->create()->id, 'quiz_id' => $quiz->id, 'attempt_number' => 1, 'questions' => [],
            'started_at' => now(), 'submitted_at' => now(), 'score' => $score, 'points_earned' => 0, 'points_total' => 3, 'passed' => $score >= 70,
        ]);
    }

    $this->actingAs($this->editor)->get('/admin/quizzes')
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/quizzes/index')
            ->where('quizzes.0.title', 'Quiz de ramas')
            ->where('quizzes.0.lesson.slug', 'ramas')
            ->where('quizzes.0.questions', 3)
            ->where('quizzes.0.attempts', 2)
            ->where('quizzes.0.average_score', 75));

    $this->actingAs(User::factory()->create())->get('/admin/quizzes')->assertForbidden();
});
