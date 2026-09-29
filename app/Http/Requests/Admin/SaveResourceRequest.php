<?php

namespace App\Http\Requests\Admin;

use App\Enums\Difficulty;
use App\Enums\ResourceType;
use App\Models\ExternalResource;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Creates (no route model) or updates a resource. Same rules as the
 * content package (PackageValidator): https only, one resource per URL.
 */
class SaveResourceRequest extends FormRequest
{
    public function authorize(): Response
    {
        $resource = $this->resource();

        return $resource === null
            ? Gate::inspect('create', ExternalResource::class)
            : Gate::inspect('update', $resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'url' => ['required', 'string', 'max:2048', 'url:https', Rule::unique('resources', 'url')->ignore($this->resource()?->id)],
            'type' => ['required', Rule::enum(ResourceType::class)],
            'provider' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
            'difficulty' => ['nullable', Rule::enum(Difficulty::class)],
            'language' => ['required', Rule::in(['es', 'en'])],
            'is_official' => ['required', 'boolean'],
        ];
    }

    public function resource(): ?ExternalResource
    {
        $resource = $this->route('resource');

        return $resource instanceof ExternalResource ? $resource : null;
    }
}
