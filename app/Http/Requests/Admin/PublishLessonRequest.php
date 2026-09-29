<?php

namespace App\Http\Requests\Admin;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class PublishLessonRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('publish', $this->route('lesson'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'change_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
