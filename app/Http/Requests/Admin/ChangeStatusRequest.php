<?php

namespace App\Http\Requests\Admin;

use App\Domain\Curriculum\Publishing\StatusTransition;
use App\Enums\ContentStatus;
use App\Models\Module;
use App\Models\Track;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Publish, unpublish, archive or restore a track or a module. The policy
 * checks the permission for the requested change; the rules check that the
 * change exists from the current status.
 */
abstract class ChangeStatusRequest extends FormRequest
{
    abstract public function subject(): Track|Module;

    public function authorize(): Response|bool
    {
        $to = ContentStatus::tryFrom((string) $this->input('status'));

        // An unknown status is a validation error, not a permission one.
        return $to === null || Gate::inspect('changeStatus', [$this->subject(), $to])->allowed();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $targets = array_map(fn (ContentStatus $status) => $status->value, StatusTransition::targets($this->subject()->status));

        return [
            'status' => ['required', 'string', Rule::in($targets)],
        ];
    }

    public function target(): ContentStatus
    {
        return ContentStatus::from((string) $this->validated('status'));
    }
}
