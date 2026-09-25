<?php

namespace App\Enums;

enum ActivityType: string
{
    use HasValues;

    case LessonStarted = 'LESSON_STARTED';
    case LessonCompleted = 'LESSON_COMPLETED';
    case LessonMastered = 'LESSON_MASTERED';
    case ExerciseSolved = 'EXERCISE_SOLVED';
    case QuizPassed = 'QUIZ_PASSED';
    case QuizFailed = 'QUIZ_FAILED';
    case LabCompleted = 'LAB_COMPLETED';
    case MilestoneCompleted = 'MILESTONE_COMPLETED';
    case ProjectCompleted = 'PROJECT_COMPLETED';
    case SkillMastered = 'SKILL_MASTERED';
    case TrackCompleted = 'TRACK_COMPLETED';
    case BadgeEarned = 'BADGE_EARNED';
    case CertificateIssued = 'CERTIFICATE_ISSUED';
}
