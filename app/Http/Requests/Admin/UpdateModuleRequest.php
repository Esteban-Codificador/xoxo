<?php

namespace App\Http\Requests\Admin;

use App\Models\Module;
use App\Rules\Slug;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateModuleRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->module());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $module = $this->module();

        return [
            'title' => ['required', 'string', 'max:200'],
            // The slug is the module's anchor on the track page, unique per track.
            'slug' => [
                'required', 'string', 'max:120', new Slug,
                Rule::unique('modules', 'slug')->where('track_id', $module->track_id)->ignore($module->id),
            ],
            'summary' => ['required', 'string', 'max:2000'],
        ];
    }

    public function module(): Module
    {
        $module = $this->route('module');

        return $module instanceof Module ? $module : abort(404);
    }
}
