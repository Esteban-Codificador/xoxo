<?php

namespace App\Enums;

enum ProgressStatus: string
{
    use HasValues;

    case InProgress = 'IN_PROGRESS';
    case Completed = 'COMPLETED';
    case Mastered = 'MASTERED';

    public function countsAsCompleted(): bool
    {
        return $this !== self::InProgress;
    }
}
