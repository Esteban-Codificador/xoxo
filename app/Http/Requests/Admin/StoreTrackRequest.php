<?php

namespace App\Http\Requests\Admin;

use App\Enums\Difficulty;
use App\Models\Roadmap;
use App\Models\Track;
use App\Rules\Slug;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * A new track: the fields a track needs to exist. Description,
 * prerequisites and modules come later, from its editor.
 */
class StoreTrackRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('create', Track::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'roadmap_id' => ['required', 'integer', Rule::exists('roadmaps', 'id')],
            'title' => ['required', 'string', 'max:200'],
            // Unique inside its roadmap: it is part of the URL.
            'slug' => [
                'required', 'string', 'max:120', new Slug,
                Rule::unique('tracks', 'slug')->where('roadmap_id', $this->integer('roadmap_id')),
            ],
            'summary' => ['required', 'string', 'max:2000'],
            'why_it_matters' => ['required', 'string', 'max:2000'],
            'difficulty' => ['required', Rule::enum(Difficulty::class)],
            'estimated_hours' => ['nullable', 'integer', 'between:1,1000'],
        ];
    }

    public function roadmap(): Roadmap
    {
        return Roadmap::query()->findOrFail($this->integer('roadmap_id'));
    }
}
