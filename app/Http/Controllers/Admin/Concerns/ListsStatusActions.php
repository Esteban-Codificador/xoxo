<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Domain\Curriculum\Publishing\StatusTransition;
use App\Enums\ContentStatus;
use App\Models\ExternalResource;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Roadmap;
use App\Models\Skill;
use App\Models\Track;
use Illuminate\Http\Request;

trait ListsStatusActions
{
    /**
     * Status changes the current user may make on the subject, so the page
     * only shows buttons that the server will accept. Lessons are only
     * archived or restored here; they publish from the publish panel.
     *
     * @return list<string>
     */
    protected function statusActions(Request $request, Roadmap|Track|Module|Lesson|Skill|ExternalResource $subject): array
    {
        $user = $request->user();

        $targets = $subject instanceof Lesson
            ? StatusTransition::lessonTargets($subject->status)
            : StatusTransition::targets($subject->status);

        return array_values(array_map(
            fn (ContentStatus $status) => $status->value,
            array_filter($targets, fn (ContentStatus $to) => $user?->can('changeStatus', [$subject, $to]) ?? false),
        ));
    }
}
