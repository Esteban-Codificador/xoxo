<?php

namespace App\Http\Requests\Admin;

use App\Domain\Content\Media\ImageProcessor;
use App\Models\MediaAsset;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UploadImageRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('create', MediaAsset::class);
    }

    /**
     * A first filter; UploadImage checks the bytes themselves.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'max:'.(ImageProcessor::MAX_BYTES / 1024), 'mimes:png,jpg,jpeg,webp'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.required' => __('media.required'),
            'image.file' => __('media.failed'),
            'image.uploaded' => __('media.failed'),
            'image.max' => __('media.too_large'),
            'image.mimes' => __('media.types'),
        ];
    }
}
