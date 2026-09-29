<?php

namespace App\Http\Requests\Admin;

use App\Models\Module;
use App\Models\Track;
use App\Rules\Slug;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreModuleRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('create', [Module::class, $this->track()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            // The module's anchor on the track page, unique per track.
            'slug' => [
                'required', 'string', 'max:120', new Slug,
                Rule::unique('modules', 'slug')->where('track_id', $this->track()->id),
            ],
            'summary' => ['required', 'string', 'max:2000'],
        ];
    }

    public function track(): Track
    {
        $track = $this->route('track');

        return $track instanceof Track ? $track : abort(404);
    }
}
