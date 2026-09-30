<?php

namespace App\Http\Requests;

use App\Models\Lesson;
use App\Models\Quiz;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/** Starting carries no input: the quiz is the lesson's, the learner the session's. */
class StartQuizAttemptRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('take', $this->quiz());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function quiz(): Quiz
    {
        $lesson = $this->route('lesson');

        return ($lesson instanceof Lesson ? $lesson->quiz : null) ?? abort(404);
    }
}
