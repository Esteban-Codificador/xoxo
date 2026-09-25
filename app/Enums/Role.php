<?php

namespace App\Enums;

enum Role: string
{
    use HasValues;

    case Admin = 'ADMIN';
    case Editor = 'EDITOR';
    case Instructor = 'INSTRUCTOR';
    case Student = 'STUDENT';
}
