<?php

namespace App\Enums;

enum ResourceType: string
{
    use HasValues;

    case Documentation = 'DOCUMENTATION';
    case Article = 'ARTICLE';
    case Tutorial = 'TUTORIAL';
    case Course = 'COURSE';
    case Book = 'BOOK';
    case Paper = 'PAPER';
    case Repository = 'REPOSITORY';
    case Tool = 'TOOL';
}
