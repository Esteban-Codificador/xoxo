<?php

namespace App\Policies;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Models\ExternalResource;
use App\Models\User;
use App\Policies\Concerns\AuthorizesContentEdits;
use Illuminate\Auth\Access\Response;

class ExternalResourcePolicy
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

    public function update(User $user, ExternalResource $model): Response
    {
        return $this->editRule($user, $model->created_by);
    }

    public function changeStatus(User $user, ExternalResource $model, ContentStatus $to): bool
    {
        return $this->statusRule($user, $model->status, $to);
    }
}
