<?php

namespace App\Http\Requests\Admin;

use App\Enums\DependencyKind;
use App\Models\Skill;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** The full list of a skill's prerequisites; cycles are checked by the action. */
class SyncSkillDependenciesRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->skill());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dependencies' => ['present', 'list', 'max:20'],
            'dependencies.*.skill_id' => ['required', 'integer', 'distinct', Rule::exists('skills', 'id'), Rule::notIn([$this->skill()->id])],
            'dependencies.*.kind' => ['required', Rule::enum(DependencyKind::class)],
            'dependencies.*.min_progress' => ['required', 'integer', 'between:1,100'],
        ];
    }

    /**
     * @return array<int, array{kind: string, min_progress: int}> skill id => edge
     */
    public function dependencies(): array
    {
        /** @var list<array{skill_id: int|string, kind: string, min_progress: int|string}> $rows */
        $rows = $this->validated('dependencies');

        return collect($rows)->mapWithKeys(fn (array $row) => [
            (int) $row['skill_id'] => ['kind' => $row['kind'], 'min_progress' => (int) $row['min_progress']],
        ])->all();
    }

    public function skill(): Skill
    {
        $skill = $this->route('skill');

        return $skill instanceof Skill ? $skill : abort(404);
    }
}
