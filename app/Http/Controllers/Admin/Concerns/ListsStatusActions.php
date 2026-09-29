<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Domain\Curriculum\Publishing\StatusTransition;
use App\Enums\ContentStatus;
use App\Models\Module;
use App\Models\Track;
use Illuminate\Http\Request;

trait ListsStatusActions
{
    /**
     * Status changes the current user may make on the subject, so the page
     * only shows buttons that the server will accept.
     *
     * @return list<string>
     */
    protected function statusActions(Request $request, Track|Module $subject): array
    {
        $user = $request->user();

        return array_values(array_map(
            fn (ContentStatus $status) => $status->value,
            array_filter(
                StatusTransition::targets($subject->status),
                fn (ContentStatus $to) => $user?->can('changeStatus', [$subject, $to]) ?? false,
            ),
        ));
    }
}
