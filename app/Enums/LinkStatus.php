<?php

namespace App\Enums;

enum LinkStatus: string
{
    use HasValues;

    case Unchecked = 'UNCHECKED';
    case Ok = 'OK';
    case Redirected = 'REDIRECTED';
    case Broken = 'BROKEN';
}
