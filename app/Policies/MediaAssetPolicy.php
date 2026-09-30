<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class MediaAssetPolicy
{
    /** Whoever writes content can add images to it. */
    public function create(User $user): bool
    {
        return $user->can(Permission::ContentCreate->value);
    }
}
