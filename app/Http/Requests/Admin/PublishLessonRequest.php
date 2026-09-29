<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use App\Models\Lesson;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

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

    /**
     * An archived lesson is restored as a draft before it is published
     * again (StatusTransition).
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $lesson = $this->route('lesson');

                if ($lesson instanceof Lesson && $lesson->status === ContentStatus::Archived) {
                    $validator->errors()->add('publish', __('cms.restore_first'));
                }
            },
        ];
    }
}
