<?php

namespace App\Http\Requests;

use App\Models\QuizAttempt;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * The answers by question id. Their shape is each question type's
 * business (QuestionTypeHandler::answer): one that does not fit counts as
 * not answered, so a half-filled sheet sent when time runs out still grades.
 */
class SubmitQuizAttemptRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('submit', $this->attempt());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'answers' => ['present', 'array', 'max:100'],
            'answers.*' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    public function answers(): array
    {
        return (array) $this->validated('answers', []);
    }

    public function attempt(): QuizAttempt
    {
        $attempt = $this->route('attempt');

        return $attempt instanceof QuizAttempt ? $attempt : abort(404);
    }
}
