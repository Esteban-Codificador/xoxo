<?php

namespace App\Policies;

use App\Models\Roadmap;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RoadmapPolicy
{
    /** Learners see published roadmaps; drafts answer 404. */
    public function view(User $user, Roadmap $roadmap): Response
    {
        return $roadmap->isPublished() ? Response::allow() : Response::denyAsNotFound();
    }
}
