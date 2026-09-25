<?php

namespace App\Domain\Content\Package;

/**
 * One entity read from a content package file: its front matter (or YAML
 * item), its Markdown body and where it sits in the package tree.
 */
final readonly class SourceEntity
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public EntityType $type,
        public string $key,
        public string $file,
        public array $data,
        public ?string $body = null,
        public int $position = 0,
        public ?string $parentKey = null,
    ) {}

    /**
     * Identity of the entity inside its package (content_import_records.key).
     */
    public function recordKey(): string
    {
        return $this->type->value.':'.$this->key;
    }

    public function hash(): string
    {
        return hash('sha256', (string) json_encode(
            [$this->data, $this->body, $this->position, $this->parentKey],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        ));
    }

    public function string(string $field, string $default = ''): string
    {
        $value = $this->data[$field] ?? $default;

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    public function nullableString(string $field): ?string
    {
        $value = $this->string($field);

        return $value === '' ? null : $value;
    }

    public function int(string $field, ?int $default = null): ?int
    {
        $value = $this->data[$field] ?? null;

        return is_int($value) ? $value : $default;
    }

    /**
     * @return list<mixed>
     */
    public function list(string $field): array
    {
        $value = $this->data[$field] ?? [];

        return is_array($value) ? array_values($value) : [];
    }

    public function isPublished(): bool
    {
        return $this->string('status', 'PUBLISHED') === 'PUBLISHED';
    }
}
