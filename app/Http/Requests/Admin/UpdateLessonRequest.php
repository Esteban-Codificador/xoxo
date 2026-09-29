<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentType;
use App\Enums\Difficulty;
use App\Models\Lesson;
use App\Rules\RichContentDocument;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Saves the working copy. Drafts may be incomplete: the publishing
 * contract (LessonReadiness) is checked when publishing, not here.
 */
class UpdateLessonRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('lesson'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'summary' => ['required', 'string', 'max:2000'],
            'why_it_matters' => ['required', 'string', 'max:2000'],
            'learning_objectives' => ['present', 'list', 'max:12'],
            'learning_objectives.*' => ['required', 'string', 'max:300'],
            'content_type' => ['required', Rule::enum(ContentType::class)->only(ContentType::forLessons())],
            'difficulty' => ['required', Rule::enum(Difficulty::class)],
            'estimated_minutes' => ['required', 'integer', 'between:1,600'],
            'body' => ['required', 'array', new RichContentDocument],
        ];
    }

    public function lesson(): Lesson
    {
        $lesson = $this->route('lesson');

        return $lesson instanceof Lesson ? $lesson : abort(404);
    }
}
