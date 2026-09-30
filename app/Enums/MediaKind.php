<?php

namespace App\Enums;

enum MediaKind: string
{
    use HasValues;

    case Image = 'IMAGE';
    case Audio = 'AUDIO';
    case Video = 'VIDEO';
    case File = 'FILE';
}
