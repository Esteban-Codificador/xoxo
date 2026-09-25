<?php

namespace App\Enums;

/**
 * State of a lesson, track or skill for a given learner. Never stored:
 * LOCKED and AVAILABLE are computed from dependencies; the rest mirror
 * lesson_progress (ADR-007).
 */
enum NodeState: string
{
    use HasValues;

    case Locked = 'LOCKED';
    case Available = 'AVAILABLE';
    case InProgress = 'IN_PROGRESS';
    case Completed = 'COMPLETED';
    case Mastered = 'MASTERED';
}
