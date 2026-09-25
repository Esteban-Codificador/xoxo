<?php

namespace App\Enums;

enum DependencyKind: string
{
    use HasValues;

    case Required = 'REQUIRED';
    case Recommended = 'RECOMMENDED';
}
