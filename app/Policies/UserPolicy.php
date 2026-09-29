<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Accounts in the CMS (roadmap §4): only admins see and manage them.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::UsersView->value);
    }

    public function view(User $user, User $model): bool
    {
        return $user->can(Permission::UsersView->value);
    }

    /** Nobody changes their own role: an admin could lock themselves out. */
    public function changeRole(User $user, User $model): Response
    {
        if (! $user->can(Permission::RolesAssign->value)) {
            return Response::deny();
        }

        return $user->is($model) ? Response::deny(__('users.own_role')) : Response::allow();
    }
}
