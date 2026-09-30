<?php

namespace App\Policies;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Models\User;
use App\Models\Video;
use App\Policies\Concerns\AuthorizesContentEdits;
use Illuminate\Auth\Access\Response;

class VideoPolicy
{
    use AuthorizesContentEdits;

    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ContentViewAny->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ContentCreate->value);
    }

    public function update(User $user, Video $model): Response
    {
        return $this->editRule($user, $model->created_by);
    }

    public function changeStatus(User $user, Video $model, ContentStatus $to): bool
    {
        return $this->statusRule($user, $model->status, $to);
    }
}
