<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ChangeUserRoleRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('changeRole', $this->target());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['role' => ['required', Rule::enum(Role::class)]];
    }

    public function target(): User
    {
        $user = $this->route('user');

        return $user instanceof User ? $user : abort(404);
    }

    public function role(): Role
    {
        return Role::from((string) $this->validated('role'));
    }
}
