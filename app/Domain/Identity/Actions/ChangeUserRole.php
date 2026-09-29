<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Audit\AuditLogger;
use App\Enums\AuditAction;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as RoleModel;

/**
 * Gives a user exactly one role. Taking a staff role away (back to
 * STUDENT) is audited as ROLE_REVOKED, anything else as ROLE_ASSIGNED,
 * with the role before and after. Permissions apply from the next request.
 *
 * The platform always keeps an ADMIN: without one, nobody can give roles.
 */
final readonly class ChangeUserRole
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @throws ValidationException when it would take the role from the last ADMIN
     */
    public function handle(User $user, Role $role): void
    {
        $before = Role::primaryOf($user->getRoleNames());

        if ($before === $role && $user->roles()->count() === 1) {
            return;
        }

        DB::transaction(function () use ($user, $role, $before): void {
            if ($before === Role::Admin && $role !== Role::Admin) {
                $this->keepAnAdmin($user);
            }

            $user->syncRoles([$role->value]);

            $this->audit->record(
                $role === Role::Student && $before?->isStaff() ? AuditAction::RoleRevoked : AuditAction::RoleAssigned,
                $user,
                ['role' => $before?->value],
                ['role' => $role->value],
            );
        });
    }

    /**
     * Changes that take ADMIN away queue on the ADMIN role row, so two
     * admins demoting each other at the same time cannot both succeed: the
     * second one counts after the first commits.
     */
    private function keepAnAdmin(User $user): void
    {
        RoleModel::query()->where('name', Role::Admin->value)->lockForUpdate()->first();

        if (User::role(Role::Admin->value)->whereKeyNot($user->id)->doesntExist()) {
            throw ValidationException::withMessages(['role' => __('users.last_admin')]);
        }
    }
}
