<?php

namespace App\Domain\Assessment\QuestionTypes;

use App\Enums\QuestionType;

final class QuestionTypes
{
    public static function for(QuestionType $type): QuestionTypeHandler
    {
        return match ($type) {
            QuestionType::SingleChoice => new SingleChoice,
            QuestionType::MultipleChoice => new MultipleChoice,
            QuestionType::TrueFalse => new TrueFalse,
            QuestionType::Ordering => new Ordering,
            QuestionType::Matching => new Matching,
        };
    }
}
