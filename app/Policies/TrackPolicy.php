<?php

namespace App\Policies;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Models\Track;
use App\Models\User;
use App\Policies\Concerns\AuthorizesContentEdits;
use Illuminate\Auth\Access\Response;

class TrackPolicy
{
    use AuthorizesContentEdits;

    /**
     * Learners see published tracks of published roadmaps. Anything else
     * answers 404 so drafts do not reveal that they exist.
     */
    public function view(User $user, Track $track): Response
    {
        return $track->isPublished() && $track->roadmap->isPublished()
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /** The CMS track list (admin.access is checked by the route group). */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ContentViewAny->value);
    }

    /** A new track is its creator's (RecordsAuthors). */
    public function create(User $user): bool
    {
        return $user->can(Permission::ContentCreate->value);
    }

    /** Fields, prerequisites and module order. */
    public function update(User $user, Track $track): Response
    {
        return $this->editRule($user, $track->created_by);
    }

    /** Publish, unpublish, archive or restore (StatusTransition). */
    public function changeStatus(User $user, Track $track, ContentStatus $to): bool
    {
        return $this->statusRule($user, $track->status, $to);
    }
}
