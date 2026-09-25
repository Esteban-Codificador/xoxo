<?php

namespace App\Domain\Content\RichContent;

/**
 * Allowlist of nodes, marks and attributes accepted in a RichContent
 * document (ADR-023). The TipTap editor must be configured with exactly
 * these node and mark names.
 */
final class RichContentSchema
{
    public const int MAX_BYTES = 524_288;

    public const int MAX_DEPTH = 12;

    public const int MAX_LATEX_LENGTH = 2_000;

    public const int MAX_DIAGRAM_LENGTH = 10_000;

    public const array CALLOUT_VARIANTS = ['note', 'tip', 'important', 'warning', 'caution'];

    public const array DIAGRAM_KINDS = ['mermaid'];

    public const array VIDEO_PROVIDERS = ['youtube'];

    public const array ORDERED_LIST_TYPES = ['1', 'a', 'A', 'i', 'I'];

    public const string CODE_LANGUAGE_PATTERN = '/^[a-z0-9+#-]{1,20}$/';

    public const string YOUTUBE_ID_PATTERN = '/^[A-Za-z0-9_-]{11}$/';

    /** Block nodes allowed wherever block content is expected. */
    public const array BLOCK_NODES = [
        'paragraph', 'heading', 'bulletList', 'orderedList', 'blockquote', 'codeBlock',
        'horizontalRule', 'table', 'callout', 'blockMath', 'diagram', 'video',
    ];

    public const array INLINE_NODES = ['text', 'hardBreak', 'inlineMath'];

    public const array MARKS = ['bold', 'italic', 'strike', 'code', 'link'];

    /**
     * Allowed children per node: 'block', 'inline', 'text', a list of node
     * types, or null for leaf nodes.
     *
     * @return array<string, string|list<string>|null>
     */
    public static function children(): array
    {
        return [
            'doc' => 'block',
            'paragraph' => 'inline',
            'heading' => 'inline',
            'bulletList' => ['listItem'],
            'orderedList' => ['listItem'],
            'listItem' => 'block',
            'blockquote' => 'block',
            'callout' => 'block',
            'codeBlock' => 'text',
            'horizontalRule' => null,
            'table' => ['tableRow'],
            'tableRow' => ['tableHeader', 'tableCell'],
            'tableHeader' => 'block',
            'tableCell' => 'block',
            'blockMath' => null,
            'inlineMath' => null,
            'diagram' => null,
            'video' => null,
            'hardBreak' => null,
            'text' => null,
        ];
    }

    /**
     * Allowed attributes per node or mark: name => [required, validator].
     * Validators return an error message or null.
     *
     * @return array<string, array<string, array{0: bool, 1: callable(mixed): ?string}>>
     */
    public static function attributes(): array
    {
        $cellAttributes = [
            'colspan' => [false, self::intBetween(1, 50)],
            'rowspan' => [false, self::intBetween(1, 50)],
            'colwidth' => [false, self::nullableIntList()],
        ];

        return [
            'heading' => ['level' => [true, self::intBetween(2, 4)]],
            'orderedList' => [
                'start' => [false, self::intBetween(0, 10_000)],
                'type' => [false, self::nullableOneOf(self::ORDERED_LIST_TYPES)],
            ],
            'codeBlock' => ['language' => [false, self::nullablePattern(self::CODE_LANGUAGE_PATTERN)]],
            'tableHeader' => $cellAttributes,
            'tableCell' => $cellAttributes,
            'callout' => ['variant' => [true, self::oneOf(self::CALLOUT_VARIANTS)]],
            'blockMath' => ['latex' => [true, self::stringUpTo(self::MAX_LATEX_LENGTH)]],
            'inlineMath' => ['latex' => [true, self::stringUpTo(self::MAX_LATEX_LENGTH)]],
            'diagram' => [
                'kind' => [true, self::oneOf(self::DIAGRAM_KINDS)],
                'source' => [true, self::stringUpTo(self::MAX_DIAGRAM_LENGTH)],
            ],
            'video' => [
                'provider' => [true, self::oneOf(self::VIDEO_PROVIDERS)],
                'videoId' => [true, self::pattern(self::YOUTUBE_ID_PATTERN)],
            ],
            'link' => [
                'href' => [true, self::safeHref(...)],
                'target' => [false, self::nullableOneOf(['_blank'])],
                'rel' => [false, self::nullableStringUpTo(100)],
                'class' => [false, self::nullableStringUpTo(100)],
            ],
        ];
    }

    public static function safeHref(mixed $value): ?string
    {
        if (! is_string($value) || $value === '' || strlen($value) > 2048) {
            return 'enlace vacío o demasiado largo';
        }

        if (preg_match('/[\x00-\x20]/', $value) === 1) {
            return 'el enlace contiene espacios o caracteres de control';
        }

        if (str_starts_with($value, '/') && ! str_starts_with($value, '//')) {
            return null;
        }

        if (str_starts_with($value, '#')) {
            return null;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        return in_array($scheme, ['https', 'http', 'mailto'], true)
            ? null
            : 'esquema de enlace no permitido (solo https, http, mailto o rutas relativas)';
    }

    /** @return callable(mixed): ?string */
    private static function intBetween(int $min, int $max): callable
    {
        return fn (mixed $value): ?string => is_int($value) && $value >= $min && $value <= $max
            ? null
            : "debe ser un entero entre {$min} y {$max}";
    }

    /**
     * @param  list<string>  $allowed
     * @return callable(mixed): ?string
     */
    private static function oneOf(array $allowed): callable
    {
        return fn (mixed $value): ?string => in_array($value, $allowed, true)
            ? null
            : 'debe ser uno de: '.implode(', ', $allowed);
    }

    /**
     * @param  list<string>  $allowed
     * @return callable(mixed): ?string
     */
    private static function nullableOneOf(array $allowed): callable
    {
        $check = self::oneOf($allowed);

        return fn (mixed $value): ?string => $value === null ? null : $check($value);
    }

    /** @return callable(mixed): ?string */
    private static function pattern(string $pattern): callable
    {
        return fn (mixed $value): ?string => is_string($value) && preg_match($pattern, $value) === 1
            ? null
            : 'formato inválido';
    }

    /** @return callable(mixed): ?string */
    private static function nullablePattern(string $pattern): callable
    {
        $check = self::pattern($pattern);

        return fn (mixed $value): ?string => $value === null || $value === '' ? null : $check($value);
    }

    /** @return callable(mixed): ?string */
    private static function stringUpTo(int $max): callable
    {
        return fn (mixed $value): ?string => is_string($value) && trim($value) !== '' && mb_strlen($value) <= $max
            ? null
            : "debe ser un texto no vacío de hasta {$max} caracteres";
    }

    /** @return callable(mixed): ?string */
    private static function nullableStringUpTo(int $max): callable
    {
        return fn (mixed $value): ?string => $value === null || (is_string($value) && mb_strlen($value) <= $max)
            ? null
            : "debe ser un texto de hasta {$max} caracteres";
    }

    /** @return callable(mixed): ?string */
    private static function nullableIntList(): callable
    {
        return fn (mixed $value): ?string => $value === null
            || (is_array($value) && array_is_list($value) && array_filter($value, fn ($v) => ! is_int($v)) === [])
            ? null
            : 'debe ser una lista de enteros';
    }
}
