<?php

namespace App\Http\Requests\Admin;

use App\Enums\UnlockPolicy;
use App\Models\Roadmap;
use App\Rules\RichContentDocument;
use App\Rules\Slug;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateRoadmapRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->roadmap());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            // Part of every learner URL of the roadmap and its tracks.
            'slug' => ['required', 'string', 'max:120', new Slug, Rule::unique('roadmaps', 'slug')->ignore($this->roadmap()->id)],
            'summary' => ['required', 'string', 'max:2000'],
            'description' => ['nullable', 'array', new RichContentDocument],
            'unlock_policy' => ['required', Rule::enum(UnlockPolicy::class)],
        ];
    }

    public function roadmap(): Roadmap
    {
        $roadmap = $this->route('roadmap');

        return $roadmap instanceof Roadmap ? $roadmap : abort(404);
    }
}
