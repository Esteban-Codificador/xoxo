<?php

namespace App\Http\Requests\Admin;

use App\Enums\Difficulty;
use App\Models\Skill;
use App\Rules\Slug;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Creates (no route model) or updates a skill. */
class SaveSkillRequest extends FormRequest
{
    public function authorize(): Response
    {
        $skill = $this->skill();

        return $skill === null
            ? Gate::inspect('create', Skill::class)
            : Gate::inspect('update', $skill);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:120', new Slug, Rule::unique('skills', 'slug')->ignore($this->skill()?->id)],
            'description' => ['required', 'string', 'max:2000'],
            'difficulty' => ['required', Rule::enum(Difficulty::class)],
        ];
    }

    public function skill(): ?Skill
    {
        $skill = $this->route('skill');

        return $skill instanceof Skill ? $skill : null;
    }
}
