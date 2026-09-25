<?php

namespace App\Enums;

enum ProfileVisibility: string
{
    use HasValues;

    case Private = 'PRIVATE';
    case Public = 'PUBLIC';
}
