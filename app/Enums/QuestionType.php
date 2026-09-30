<?php

namespace App\Enums;

/**
 * Question types graded automatically (§34–35, ADR-022). Open exercises
 * (code, debugging, architecture) are self-assessed with a rubric and are
 * not question types.
 */
enum QuestionType: string
{
    use HasValues;

    case SingleChoice = 'SINGLE_CHOICE';
    case MultipleChoice = 'MULTIPLE_CHOICE';
    case TrueFalse = 'TRUE_FALSE';
    case Ordering = 'ORDERING';
    case Matching = 'MATCHING';
}
