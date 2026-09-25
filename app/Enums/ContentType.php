<?php

namespace App\Enums;

enum ContentType: string
{
    use HasValues;

    case Concept = 'CONCEPT';
    case Tutorial = 'TUTORIAL';
    case Exercise = 'EXERCISE';
    case Lab = 'LAB';
    case Project = 'PROJECT';
    case Quiz = 'QUIZ';
    case Reading = 'READING';
    case Video = 'VIDEO';
    case Documentation = 'DOCUMENTATION';
    case Challenge = 'CHALLENGE';

    /**
     * Content types a lesson can have. Exercises, labs, projects and
     * quizzes are entities of their own.
     *
     * @return list<self>
     */
    public static function forLessons(): array
    {
        return [self::Concept, self::Tutorial, self::Reading, self::Video, self::Documentation, self::Challenge];
    }

    /**
     * @return list<string>
     */
    public static function lessonValues(): array
    {
        return array_map(fn (self $case): string => $case->value, self::forLessons());
    }
}
