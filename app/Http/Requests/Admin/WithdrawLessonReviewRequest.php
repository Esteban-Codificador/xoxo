<?php

namespace App\Http\Requests\Admin;

use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

/**
 * The author (or a reviewer) takes the lesson out of review to edit it.
 */
class WithdrawLessonReviewRequest extends ResolveLessonReviewRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('withdrawReview', $this->lesson());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
