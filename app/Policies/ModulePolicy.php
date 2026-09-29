<?php

namespace App\Policies;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Models\Module;
use App\Models\Track;
use App\Models\User;
use App\Policies\Concerns\AuthorizesContentEdits;
use Illuminate\Auth\Access\Response;

/**
 * Modules have no author of their own: whoever can edit the track can edit
 * its modules.
 */
class ModulePolicy
{
    use AuthorizesContentEdits;

    /** Adding a module is editing its track's structure. */
    public function create(User $user, Track $track): Response
    {
        return $user->can(Permission::ContentCreate->value) ? $this->editRule($user, $track->created_by) : Response::deny();
    }

    public function update(User $user, Module $module): Response
    {
        return $this->editRule($user, $module->track->created_by);
    }

    public function changeStatus(User $user, Module $module, ContentStatus $to): bool
    {
        return $this->statusRule($user, $module->status, $to);
    }
}
