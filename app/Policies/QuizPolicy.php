<?php

namespace App\Policies;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Models\Quiz;
use App\Models\User;
use App\Policies\Concerns\AuthorizesContentEdits;
use Illuminate\Auth\Access\Response;

/**
 * Learners take published quizzes of lessons they can progress on. In the
 * CMS a quiz belongs to its lesson: whoever may change the lesson (or its
 * relations) may change its quiz (ADR-036).
 */
class QuizPolicy
{
    use AuthorizesContentEdits;

    public function __construct(private readonly LessonPolicy $lessons) {}

    /** The CMS list of quizzes. */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ContentViewAny->value);
    }

    public function update(User $user, Quiz $quiz): Response
    {
        return $this->lessons->update($user, $quiz->lesson);
    }

    public function changeStatus(User $user, Quiz $quiz, ContentStatus $to): bool
    {
        return $this->statusRule($user, $quiz->status, $to);
    }

    /** The quiz page: published, of a lesson learners can see. */
    public function view(User $user, Quiz $quiz): Response
    {
        return Quiz::query()->visibleToLearners()->whereKey($quiz->getKey())->exists()
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Start an attempt: like progressing on its lesson. STRICT roadmaps
     * answer 403 on a LOCKED lesson (ADR-009).
     */
    public function take(User $user, Quiz $quiz): Response
    {
        $visible = $this->view($user, $quiz);

        return $visible->denied() ? $visible : $this->lessons->progress($user, $quiz->lesson);
    }
}
