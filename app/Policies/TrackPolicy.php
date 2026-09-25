<?php

namespace App\Policies;

use App\Models\Track;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TrackPolicy
{
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
}
