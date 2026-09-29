<?php

namespace App\Policies;

use App\Domain\Learning\State\RoadmapStateResolver;
use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Models\Lesson;
use App\Models\User;
use App\Policies\Concerns\AuthorizesContentEdits;
use Illuminate\Auth\Access\Response;

class LessonPolicy
{
    use AuthorizesContentEdits;

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
     * Opens the editor, relations and history. Editors and admins edit any
     * lesson; instructors only their own.
     */
    public function edit(User $user, Lesson $lesson): Response
    {
        return $this->editRule($user, $lesson->created_by);
    }

    /**
     * Saves content or relations. In review the lesson is frozen for its
     * author: what gets published is what was reviewed (ADR-032). Whoever
     * publishes can still fix it.
     */
    public function update(User $user, Lesson $lesson): Response
    {
        $edit = $this->edit($user, $lesson);

        if ($edit->allowed() && $lesson->status === ContentStatus::Review && ! $user->can(Permission::ContentPublish->value)) {
            return Response::deny(__('cms.review.frozen'));
        }

        return $edit;
    }

    public function submitForReview(User $user, Lesson $lesson): Response
    {
        return $user->can(Permission::ContentSubmitReview->value) ? $this->edit($user, $lesson) : Response::deny();
    }

    /** The queue of lessons waiting for review. */
    public function reviewQueue(User $user): bool
    {
        return $user->can(Permission::ContentPublish->value);
    }

    /** Returning with changes requested is the reviewer's call. */
    public function returnFromReview(User $user, Lesson $lesson): bool
    {
        return $user->can(Permission::ContentPublish->value);
    }

    /** Whoever can edit it (its author, a reviewer) can take it out of review. */
    public function withdrawReview(User $user, Lesson $lesson): Response
    {
        return $this->edit($user, $lesson);
    }

    /** Publishing makes the working copy what learners read. */
    public function publish(User $user, Lesson $lesson): bool
    {
        return $user->can(Permission::ContentPublish->value);
    }

    /** Archive or restore (publishing is publish()). */
    public function changeStatus(User $user, Lesson $lesson, ContentStatus $to): bool
    {
        return $this->statusRule($user, $lesson->status, $to);
    }
}
