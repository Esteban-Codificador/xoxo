<?php

namespace App\Enums;

enum ContentStatus: string
{
    use HasValues;

    case Draft = 'DRAFT';
    case Review = 'REVIEW';
    case Published = 'PUBLISHED';
    case Archived = 'ARCHIVED';
}
