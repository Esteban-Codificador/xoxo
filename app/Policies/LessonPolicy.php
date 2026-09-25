<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LessonPolicy
{
    /**
     * One rule for lists and pages: Lesson::visibleToLearners(). Drafts
     * answer 404; staff previews arrive with the CMS (phase 5b).
     */
    public function view(User $user, Lesson $lesson): Response
    {
        return Lesson::query()->visibleToLearners()->whereKey($lesson->getKey())->exists()
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
