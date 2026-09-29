<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

/**
 * The audit log is read-only for everyone (append-only table).
 */
class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::AuditView->value);
    }
}
