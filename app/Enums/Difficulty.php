<?php

namespace App\Enums;

enum Difficulty: string
{
    use HasValues;

    case Beginner = 'BEGINNER';
    case Intermediate = 'INTERMEDIATE';
    case Advanced = 'ADVANCED';
    case Expert = 'EXPERT';
}
