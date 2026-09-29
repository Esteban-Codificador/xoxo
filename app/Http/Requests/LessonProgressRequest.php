<?php

namespace App\Http\Requests;

use App\Models\Lesson;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Start, complete and uncomplete carry no input: the lesson comes from the
 * route and the learner from the session. Authorization is the whole job.
 */
class LessonProgressRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('progress', $this->lesson());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function lesson(): Lesson
    {
        $lesson = $this->route('lesson');

        return $lesson instanceof Lesson ? $lesson : abort(404);
    }
}
