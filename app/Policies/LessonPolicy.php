<?php

namespace App\Policies;

use App\Domain\Learning\State\RoadmapStateResolver;
use App\Enums\Permission;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LessonPolicy
{
    public function __construct(private readonly RoadmapStateResolver $states) {}

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

    /**
     * Start, complete or uncomplete. ADVISORY roadmaps allow it on LOCKED
     * lessons (the page warns); STRICT ones answer 403 (ADR-009).
     */
    public function progress(User $user, Lesson $lesson): Response
    {
        $visible = $this->view($user, $lesson);

        if ($visible->denied()) {
            return $visible;
        }

        $roadmap = $lesson->loadMissing('module.track.roadmap')->module->track->roadmap;

        return $this->states->resolve($user, $roadmap)->canProgress($lesson)
            ? Response::allow()
            : Response::deny(__('progress.locked'));
    }

    /** The CMS lesson list (admin.access is checked by the route group). */
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ContentViewAny->value);
    }

    /**
     * Editors and admins edit any lesson; instructors only the ones they
     * created (roadmap §4). Imported lessons have no author, so only
     * editors and admins can change them.
     */
    public function update(User $user, Lesson $lesson): Response
    {
        if ($user->can(Permission::ContentUpdateAny->value)) {
            return Response::allow();
        }

        return $user->can(Permission::ContentUpdateOwn->value) && $lesson->created_by === $user->id
            ? Response::allow()
            : Response::deny();
    }

    /** Publishing makes the working copy what learners read. */
    public function publish(User $user, Lesson $lesson): bool
    {
        return $user->can(Permission::ContentPublish->value);
    }
}
