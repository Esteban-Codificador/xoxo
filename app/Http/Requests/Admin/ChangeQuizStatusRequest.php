<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use App\Models\Quiz;
use Illuminate\Validation\Validator;

class ChangeQuizStatusRequest extends ChangeStatusRequest
{
    public function subject(): Quiz
    {
        $quiz = $this->route('quiz');

        return $quiz instanceof Quiz ? $quiz : abort(404);
    }

    /**
     * A quiz without questions cannot be taken, so it cannot be published.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('status') === ContentStatus::Published->value && ! $this->subject()->questions()->exists()) {
                $validator->errors()->add('status', __('quizzes.no_questions'));
            }
        }];
    }
}
