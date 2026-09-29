<?php

namespace App\Domain\Curriculum\Publishing;

use App\Domain\Content\RichContent\RichContent;

/**
 * The body a new lesson starts with: the fixed H2 sections every lesson
 * reads with (docs/content-architecture.md §4). They are structure, not
 * curriculum: the author writes under them and drops those that do not
 * apply, except the ones the contract requires.
 */
final class LessonTemplate
{
    public const array SECTIONS = [
        'Concepto',
        'Cómo funciona',
        'En código',
        'Errores comunes',
        'En AI Engineering',
        LessonReadiness::PRACTICE_HEADING,
    ];

    public static function body(): RichContent
    {
        return RichContent::fromDocument([
            'type' => 'doc',
            'content' => array_map(fn (string $heading) => [
                'type' => 'heading',
                'attrs' => ['level' => 2],
                'content' => [['type' => 'text', 'text' => $heading]],
            ], self::SECTIONS),
        ]);
    }
}
