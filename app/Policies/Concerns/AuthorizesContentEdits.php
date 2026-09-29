<?php

namespace App\Policies\Concerns;

use App\Domain\Curriculum\Publishing\StatusTransition;
use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Editing rules shared by the curriculum policies (roadmap §4): editors and
 * admins change anything; instructors only what they created. Imported
 * content has no author, so only editors and admins can change it.
 */
trait AuthorizesContentEdits
{
    protected function editRule(User $user, ?int $authorId): Response
    {
        if ($user->can(Permission::ContentUpdateAny->value)) {
            return Response::allow();
        }

        return $user->can(Permission::ContentUpdateOwn->value) && $authorId !== null && $authorId === $user->id
            ? Response::allow()
            : Response::deny();
    }

    /**
     * Permission only: whether the transition exists at all is a validation
     * question (ChangeStatusRequest answers 422, not 403).
     */
    protected function statusRule(User $user, ContentStatus $from, ContentStatus $to): bool
    {
        return $user->can(StatusTransition::permission($from, $to)->value);
    }
}
