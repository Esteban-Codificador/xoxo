<?php

namespace App\Domain\Content\Package;

/**
 * Entity kinds of a content package. Values match the morph map aliases.
 */
enum EntityType: string
{
    case Roadmap = 'roadmap';
    case Skill = 'skill';
    case Resource = 'resource';
    case Track = 'track';
    case Module = 'module';
    case Lesson = 'lesson';
}
