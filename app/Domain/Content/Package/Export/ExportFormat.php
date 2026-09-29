<?php

namespace App\Domain\Content\Package\Export;

use App\Domain\Content\Package\EntityType;
use App\Domain\Content\Package\SourceEntity;
use App\Domain\Content\RichContent\InvalidRichContent;
use App\Domain\Content\RichContent\Markdown\MarkdownToRichContent;
use Symfony\Component\Yaml\Yaml;

/**
 * How exported entities are written, and when two versions of an entity
 * mean the same to the importer (so an unchanged file keeps its bytes and
 * its hand-written formatting).
 */
final readonly class ExportFormat
{
    private const int YAML_FLAGS = Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_COMPACT_NESTED_MAPPING;

    public function __construct(private MarkdownToRichContent $markdown) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function markdownFile(array $data, string $body): string
    {
        return "---\n".$this->yaml($data)."---\n".($body === '' ? '' : "\n{$body}");
    }

    /**
     * roadmap.yaml keeps its description (Markdown) as a literal block.
     *
     * @param  array<string, mixed>  $data
     */
    public function roadmapFile(array $data, ?string $description): string
    {
        return $this->yaml([...$data, 'description' => $description]);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function resourcesFile(array $items): string
    {
        return $this->yaml($items);
    }

    /**
     * One top-level field as YAML, without the trailing newline.
     */
    public function field(string $key, mixed $value): string
    {
        return rtrim($this->yaml([$key => $value]), "\n");
    }

    /**
     * Whether two values of a field mean the same to the importer. Markdown
     * fields (the roadmap description) compare as documents.
     */
    public function sameField(string $key, mixed $a, mixed $b, bool $markdown = false): bool
    {
        if ($markdown) {
            return $this->documentHash(trim((string) (is_string($a) ? $a : ''))) === $this->documentHash(trim((string) (is_string($b) ? $b : '')));
        }

        return $this->normalizeField($key, $a) == $this->normalizeField($key, $b);
    }

    /**
     * Whether two Markdown texts convert to the same document.
     */
    public function sameDocument(string $a, string $b): bool
    {
        return $this->documentHash(trim($a)) === $this->documentHash(trim($b));
    }

    /**
     * What the importer reads from an entity: its fields without defaults
     * or empty values, sets in a fixed order, and the body as the document
     * it converts to. Position and parent come from the path, not the
     * content.
     *
     * @return array{0: mixed, 1: string|null}
     */
    public function comparable(SourceEntity $entity): array
    {
        $data = $entity->data;
        unset($data['key']);

        if (($data['status'] ?? 'PUBLISHED') === 'PUBLISHED') {
            unset($data['status']);
        }

        if ($entity->type === EntityType::Skill && ($data['slug'] ?? $entity->key) === $entity->key) {
            unset($data['slug']);
        }

        foreach ($data as $key => $value) {
            $data[$key] = $this->normalizeField((string) $key, $value);
        }

        return [$this->normalize($data), $this->body($entity)];
    }

    /**
     * Skills and dependencies are sets; dates read by YAML become strings.
     */
    private function normalizeField(string $key, mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $value = $this->normalize($value);

        if (in_array($key, ['skills', 'depends_on'], true) && is_array($value)) {
            usort($value, fn (mixed $a, mixed $b) => strcmp((string) json_encode($a), (string) json_encode($b)));
        }

        return $value;
    }

    private function body(SourceEntity $entity): ?string
    {
        $body = trim((string) $entity->body);

        if ($body === '') {
            return null;
        }

        return match ($entity->type) {
            EntityType::Skill => (string) preg_replace('/\s+/', ' ', $body),
            EntityType::Lesson, EntityType::Track, EntityType::Roadmap => $this->documentHash($body),
            default => null,
        };
    }

    private function documentHash(string $markdown): ?string
    {
        if ($markdown === '') {
            return null;
        }

        try {
            return $this->markdown->convert($markdown)->hash();
        } catch (InvalidRichContent) {
            return 'invalid:'.hash('sha256', $markdown);
        }
    }

    private function normalize(mixed $value): mixed
    {
        if (is_string($value)) {
            return trim($value);
        }

        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map($this->normalize(...), $value);
        }

        ksort($value);

        return array_filter(array_map($this->normalize(...), $value), fn (mixed $item) => $item !== null && $item !== '' && $item !== []);
    }

    private function yaml(mixed $data): string
    {
        return Yaml::dump($this->withoutNulls($data), 2, 2, self::YAML_FLAGS);
    }

    private function withoutNulls(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }

        $clean = array_map($this->withoutNulls(...), $data);

        return array_is_list($data) ? $clean : array_filter($clean, fn (mixed $item) => $item !== null);
    }
}
