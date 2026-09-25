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
     * Text used for search, length checks and version diffs. Code and
     * formulas are kept; diagrams and videos carry no prose and are skipped.
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
