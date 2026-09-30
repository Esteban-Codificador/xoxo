<?php

namespace App\Domain\Curriculum\Publishing;

use App\Domain\Content\Diff\TextDiff;
use App\Domain\Content\Media\MediaNames;
use App\Domain\Content\RichContent\Markdown\RichContentToMarkdown;
use App\Domain\Content\RichContent\RichContent;

/**
 * What changes between two snapshots of a lesson's versioned fields
 * (Lesson::VERSIONED_FIELDS): text fields and the body as line diffs, the
 * short attributes as before and after. Only changed fields are listed.
 *
 * @phpstan-import-type Row from TextDiff
 *
 * @phpstan-type Changes array{
 *     fields: list<array{field: string, rows: list<Row>}>,
 *     attributes: list<array{field: string, before: string|int, after: string|int}>,
 *     body: list<Row>,
 * }
 */
final readonly class LessonChanges
{
    private const array TEXT_FIELDS = ['title', 'summary', 'why_it_matters', 'learning_objectives'];

    private const array ATTRIBUTES = ['content_type', 'difficulty', 'estimated_minutes'];

    public function __construct(
        private RichContentToMarkdown $markdown,
        private MediaNames $media,
    ) {}

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return Changes
     */
    public function between(array $before, array $after): array
    {
        $fields = [];

        foreach (self::TEXT_FIELDS as $field) {
            $rows = TextDiff::lines($this->text($before[$field] ?? ''), $this->text($after[$field] ?? ''));

            if ($rows !== []) {
                $fields[] = ['field' => $field, 'rows' => $rows];
            }
        }

        $attributes = [];

        foreach (self::ATTRIBUTES as $field) {
            if (($before[$field] ?? null) !== ($after[$field] ?? null)) {
                $attributes[] = ['field' => $field, 'before' => $this->scalar($before[$field] ?? ''), 'after' => $this->scalar($after[$field] ?? '')];
            }
        }

        return [
            'fields' => $fields,
            'attributes' => $attributes,
            'body' => TextDiff::lines($this->body($before['body'] ?? null), $this->body($after['body'] ?? null)),
        ];
    }

    /** Objectives one per line, so an edited objective reads as one changed line. */
    private function text(mixed $value): string
    {
        return is_array($value)
            ? implode("\n", array_map(fn (mixed $item) => is_string($item) ? $item : '', $value))
            : (is_string($value) ? $value : '');
    }

    private function scalar(mixed $value): string|int
    {
        return is_int($value) || is_string($value) ? $value : '';
    }

    private function body(mixed $body): string
    {
        // Images read as in the package: an image swapped for another is a changed line.
        return $this->markdown->convert(
            $body instanceof RichContent ? $body : RichContent::fromArray($body ?? RichContent::empty()->toArray()),
            $this->media->pathOf(...),
        );
    }
}
