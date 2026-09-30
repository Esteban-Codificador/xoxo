<?php

namespace App\Enums;

/**
 * Where a video lives. Only YouTube for now (ADR-035): Vimeo and hosted
 * videos (database.md) are added when something needs them.
 */
enum VideoProvider: string
{
    use HasValues;

    case Youtube = 'YOUTUBE';
}
