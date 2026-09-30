<?php

namespace App\Policies;

use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/** An attempt is its learner's alone; to anyone else it does not exist. */
class QuizAttemptPolicy
{
    public function view(User $user, QuizAttempt $attempt): Response
    {
        return $attempt->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }

    public function submit(User $user, QuizAttempt $attempt): Response
    {
        return $this->view($user, $attempt);
    }
}
