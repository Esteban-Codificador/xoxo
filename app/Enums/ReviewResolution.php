<?php

namespace App\Enums;

/**
 * How a lesson review ended (lesson_reviews.resolution).
 */
enum ReviewResolution: string
{
    use HasValues;

    case Published = 'PUBLISHED';
    case Returned = 'RETURNED';
    case Withdrawn = 'WITHDRAWN';
}
