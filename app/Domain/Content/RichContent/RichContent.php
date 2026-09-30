<?php

namespace App\Domain\Content\RichContent;

use JsonSerializable;

/**
 * Structured rich content: a ProseMirror document wrapped in a versioned
 * envelope {version, doc} (ADR-023). Instances are always valid against
 * RichContentSchema.
 */
final readonly class RichContent implements JsonSerializable
{
    public const int CURRENT_VERSION = 1;

    /**
     * @param  array<string, mixed>  $doc
     */
    private function __construct(public array $doc) {}

    public static function empty(): self
    {
        return new self(['type' => 'doc', 'content' => []]);
    }

    /**
     * @throws InvalidRichContent
     */
    public static function fromArray(mixed $envelope): self
    {
        $errors = (new RichContentValidator)->errors($envelope);

        if ($errors !== []) {
            throw new InvalidRichContent($errors);
        }

        /** @var array{version: int, doc: array<string, mixed>} $envelope */
        return new self($envelope['doc']);
    }

    /**
     * @param  array<string, mixed>  $doc
     *
     * @throws InvalidRichContent
     */
    public static function fromDocument(array $doc): self
    {
        return self::fromArray(['version' => self::CURRENT_VERSION, 'doc' => $doc]);
    }

    /**
     * @return array{version: int, doc: array<string, mixed>}
     */
    public function toArray(): array
    {
        return ['version' => self::CURRENT_VERSION, 'doc' => $this->doc];
    }

    /**
     * @return array{version: int, doc: array<string, mixed>}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function isEmpty(): bool
    {
        return trim($this->plainText()) === '';
    }

    /**
     * Text used for search and length checks. Code, formulas and the
     * alternative text of images are kept; diagrams and videos carry no
     * prose and are skipped.
     */
    public function plainText(): string
    {
        return trim((string) preg_replace("/\n{3,}/", "\n\n", self::textOf($this->doc)));
    }

    /**
     * Stable hash of the document. Keys are sorted first because PostgreSQL
     * jsonb does not preserve key order.
     */
    public function hash(): string
    {
        return hash('sha256', (string) json_encode(self::canonical($this->toArray()), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Plain text under a top-level heading (compared without case), up to
     * the next heading of the same or a higher level; null when there is
     * no such heading.
     */
    public function sectionText(string $heading, int $level = 2): ?string
    {
        $wanted = mb_strtolower(trim($heading));
        $inside = false;
        $found = false;
        $text = '';

        foreach ($this->doc['content'] ?? [] as $node) {
            if (! is_array($node)) {
                continue;
            }

            $nodeLevel = ($node['type'] ?? null) === 'heading' ? (int) ($node['attrs']['level'] ?? 0) : null;

            if ($nodeLevel !== null && $nodeLevel <= $level) {
                if ($inside) {
                    break;
                }

                if ($nodeLevel === $level && mb_strtolower(trim(self::textOf($node))) === $wanted) {
                    $inside = $found = true;
                }

                continue;
            }

            if ($inside) {
                $text .= self::textOf($node)."\n\n";
            }
        }

        return $found ? trim((string) preg_replace("/\n{3,}/", "\n\n", $text)) : null;
    }

    /**
     * Plain text of every heading at the given level, in document order.
     *
     * @return list<string>
     */
    public function headings(int $level): array
    {
        $headings = [];

        foreach ($this->doc['content'] ?? [] as $node) {
            if (is_array($node) && ($node['type'] ?? null) === 'heading' && ($node['attrs']['level'] ?? null) === $level) {
                $headings[] = trim(self::textOf($node));
            }
        }

        return $headings;
    }

    /**
     * Ids of the media_assets the document shows, in document order.
     *
     * @return list<int>
     */
    public function mediaIds(): array
    {
        return self::mediaIdsOf($this->doc);
    }

    /**
     * The same for a document that may not be valid yet (a request).
     *
     * @return list<int>
     */
    public static function mediaIdsOf(mixed $node): array
    {
        if (! is_array($node)) {
            return [];
        }

        $ids = ($node['type'] ?? null) === 'image' && is_int($node['attrs']['mediaId'] ?? null) ? [$node['attrs']['mediaId']] : [];

        foreach (is_array($node['content'] ?? null) ? $node['content'] : [] as $child) {
            array_push($ids, ...self::mediaIdsOf($child));
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<mixed>  $node
     */
    private static function textOf(array $node): string
    {
        $type = $node['type'] ?? null;

        return match ($type) {
            'text' => (string) ($node['text'] ?? ''),
            'hardBreak' => "\n",
            'inlineMath' => (string) ($node['attrs']['latex'] ?? ''),
            'blockMath' => ($node['attrs']['latex'] ?? '')."\n\n",
            'image' => ($node['attrs']['alt'] ?? '')."\n\n",
            'diagram', 'video', 'horizontalRule' => '',
            'paragraph', 'heading', 'codeBlock' => self::childrenText($node)."\n\n",
            'listItem', 'tableRow' => rtrim(self::childrenText($node))."\n",
            'tableHeader', 'tableCell' => rtrim(self::childrenText($node)).' ',
            'bulletList', 'orderedList', 'table' => self::childrenText($node)."\n",
            default => self::childrenText($node),
        };
    }

    /**
     * @param  array<mixed>  $node
     */
    private static function childrenText(array $node): string
    {
        $text = '';

        foreach ($node['content'] ?? [] as $child) {
            if (is_array($child)) {
                $text .= self::textOf($child);
            }
        }

        return $text;
    }

    private static function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(self::canonical(...), $value);
    }
}
