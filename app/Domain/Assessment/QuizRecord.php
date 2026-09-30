<?php

namespace App\Domain\Assessment;

use App\Domain\Assessment\Actions\ExpireQuizAttempt;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * A learner's history with a quiz: attempts, best score and what is left.
 * Reading it closes an attempt whose time ran out, so pages never show a
 * clock at zero as still running (the time is the server's, ADR-036).
 */
final readonly class QuizRecord
{
    /**
     * @param  Collection<int, QuizAttempt>  $attempts  newest first
     */
    private function __construct(public Quiz $quiz, public Collection $attempts) {}

    public static function of(User $user, Quiz $quiz): self
    {
        $overdue = QuizAttempt::query()->whereBelongsTo($user)->whereBelongsTo($quiz)->whereNull('submitted_at')->first();

        if ($overdue?->isOverdue()) {
            DB::transaction(function () use ($overdue) {
                $locked = QuizAttempt::query()->lockForUpdate()->find($overdue->id);

                if ($locked?->isOverdue()) {
                    app(ExpireQuizAttempt::class)->handle($locked);
                }
            });
        }

        return new self($quiz, QuizAttempt::query()->whereBelongsTo($user)->whereBelongsTo($quiz)->orderByDesc('attempt_number')->get());
    }

    public function open(): ?QuizAttempt
    {
        return $this->attempts->first(fn (QuizAttempt $attempt) => $attempt->isOpen());
    }

    /**
     * @return Collection<int, QuizAttempt>
     */
    public function submitted(): Collection
    {
        return $this->attempts->reject(fn (QuizAttempt $attempt) => $attempt->isOpen())->values();
    }

    public function bestScore(): ?int
    {
        $best = $this->submitted()->max('score');

        return $best === null ? null : (int) $best;
    }

    public function passed(): bool
    {
        return $this->attempts->contains(fn (QuizAttempt $attempt) => $attempt->passed === true);
    }

    public function attemptsLeft(): ?int
    {
        return $this->quiz->attemptsLeft($this->attempts->count());
    }

    /**
     * The right answers show once the learner passed or has no attempts
     * left: a failed attempt explains, it does not hand over the next.
     */
    public function reveals(QuizAttempt $attempt): bool
    {
        return $attempt->passed === true || $this->attemptsLeft() === 0;
    }
}
