<?php

namespace App\Http\Requests\Admin;

use App\Enums\Difficulty;
use App\Models\Track;
use App\Rules\RichContentDocument;
use App\Rules\Slug;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateTrackRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->track());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $track = $this->track();

        return [
            'title' => ['required', 'string', 'max:200'],
            // The slug is unique inside its roadmap (it is part of the URL).
            'slug' => [
                'required', 'string', 'max:120', new Slug,
                Rule::unique('tracks', 'slug')->where('roadmap_id', $track->roadmap_id)->ignore($track->id),
            ],
            'summary' => ['required', 'string', 'max:2000'],
            'why_it_matters' => ['required', 'string', 'max:2000'],
            'description' => ['nullable', 'array', new RichContentDocument],
            'difficulty' => ['required', Rule::enum(Difficulty::class)],
            'estimated_hours' => ['nullable', 'integer', 'between:1,1000'],
        ];
    }

    public function track(): Track
    {
        $track = $this->route('track');

        return $track instanceof Track ? $track : abort(404);
    }
}
