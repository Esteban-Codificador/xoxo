<?php

namespace App\Http\Requests\Admin;

use App\Domain\Curriculum\Publishing\StatusTransition;
use App\Enums\ContentStatus;
use App\Models\ExternalResource;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Skill;
use App\Models\Track;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Publish, unpublish, archive or restore a track, module or lesson. The policy
 * checks the permission for the requested change; the rules check that the
 * change exists from the current status.
 */
abstract class ChangeStatusRequest extends FormRequest
{
    abstract public function subject(): Track|Module|Lesson|Skill|ExternalResource;

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
        $targets = array_map(fn (ContentStatus $status) => $status->value, $this->targets());

        return [
            'status' => ['required', 'string', Rule::in($targets)],
        ];
    }

    /**
     * @return list<ContentStatus>
     */
    protected function targets(): array
    {
        return StatusTransition::targets($this->subject()->status);
    }

    public function target(): ContentStatus
    {
        return ContentStatus::from((string) $this->validated('status'));
    }
}
