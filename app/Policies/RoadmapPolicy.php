<?php

namespace App\Policies;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Models\Roadmap;
use App\Models\User;
use App\Policies\Concerns\AuthorizesContentEdits;
use Illuminate\Auth\Access\Response;

class RoadmapPolicy
{
    use AuthorizesContentEdits;

    /** Learners see published roadmaps; drafts answer 404. */
    public function view(User $user, Roadmap $roadmap): Response
    {
        return $roadmap->isPublished() ? Response::allow() : Response::denyAsNotFound();
    }

    /**
     * Its fields and unlock policy. The roadmap frames every track and its
     * policy applies to every learner, so it is never "own" content: only
     * who edits any content changes it.
     */
    public function update(User $user, Roadmap $roadmap): bool
    {
        return $user->can(Permission::ContentUpdateAny->value);
    }

    /** Publish, unpublish, archive or restore (StatusTransition). */
    public function changeStatus(User $user, Roadmap $roadmap, ContentStatus $to): bool
    {
        return $user->can(Permission::ContentUpdateAny->value) && $this->statusRule($user, $roadmap->status, $to);
    }
}
