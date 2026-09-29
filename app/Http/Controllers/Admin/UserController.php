<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Identity\Actions\ChangeUserRole;
use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeUserRoleRequest;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Accounts and their role. People register themselves; an admin gives
 * them the role they need here.
 */
class UserController extends Controller
{
    private const int PER_PAGE = 25;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $search = trim((string) $request->query('q', ''));
        $role = Role::tryFrom((string) $request->query('role', ''));

        $users = User::query()
            ->with('roles:id,name')
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->whereLike('name', "%{$search}%", caseSensitive: false)
                ->orWhereLike('email', "%{$search}%", caseSensitive: false)
                ->orWhereLike('username', "%{$search}%", caseSensitive: false)))
            ->when($role !== null, fn (Builder $query) => $query->role($role->value))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('admin/users/index', [
            'users' => collect($users->items())->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => Role::primaryOf($user->getRoleNames())?->value,
                'verified' => $user->email_verified_at !== null,
                'last_active_at' => $user->last_active_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
            ])->values()->all(),
            'pagination' => [
                'page' => $users->currentPage(),
                'pages' => $users->lastPage(),
                'total' => $users->total(),
                'previous' => $users->previousPageUrl(),
                'next' => $users->nextPageUrl(),
            ],
            'filters' => ['q' => $search, 'role' => $role?->value],
        ]);
    }

    public function edit(Request $request, User $user): Response
    {
        Gate::authorize('view', $user);

        $changeRole = Gate::inspect('changeRole', $user);

        return Inertia::render('admin/users/edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'role' => Role::primaryOf($user->getRoleNames())?->value,
                'verified' => $user->email_verified_at !== null,
                'two_factor' => $user->two_factor_confirmed_at !== null,
                'last_active_at' => $user->last_active_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
                'lessons_authored' => Lesson::query()->where('created_by', $user->id)->count(),
            ],
            'roles' => Role::values(),
            'can' => [
                'change_role' => $changeRole->allowed(),
                'view_audit' => $request->user()?->can(Permission::AuditView->value) ?? false,
            ],
            // Why the role cannot be changed here (their own account).
            'role_locked' => $changeRole->allowed() ? null : $changeRole->message(),
        ]);
    }

    public function updateRole(ChangeUserRoleRequest $request, User $user, ChangeUserRole $action): RedirectResponse
    {
        $role = $request->role();
        $action->handle($user, $role);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('users.role_changed', [
            'user' => $user->name,
            'role' => __("users.roles.{$role->value}"),
        ])]);

        return back();
    }
}
