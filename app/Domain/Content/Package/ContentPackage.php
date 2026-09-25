<?php

namespace App\Domain\Content\Package;

final class ContentPackage
{
    /** @var array<string, array<string, SourceEntity>> */
    private array $entities = [];

    /** @var list<PackageIssue> */
    private array $issues = [];

    public function __construct(public readonly string $name, public readonly string $path) {}

    public function add(SourceEntity $entity): void
    {
        if (isset($this->entities[$entity->type->value][$entity->key])) {
            $first = $this->entities[$entity->type->value][$entity->key];
            $this->addIssue($entity->file, "clave duplicada \"{$entity->key}\" (ya usada en {$first->file}).");

            return;
        }

        $this->entities[$entity->type->value][$entity->key] = $entity;
    }

    public function addIssue(string $file, string $message): void
    {
        $this->issues[] = new PackageIssue($file, $message);
    }

    /**
     * Structural problems found while reading the files.
     *
     * @return list<PackageIssue>
     */
    public function readIssues(): array
    {
        return $this->issues;
    }

    /**
     * @return array<string, SourceEntity>
     */
    public function all(EntityType $type): array
    {
        return $this->entities[$type->value] ?? [];
    }

    public function find(EntityType $type, string $key): ?SourceEntity
    {
        return $this->entities[$type->value][$key] ?? null;
    }

    public function roadmap(): ?SourceEntity
    {
        $roadmaps = $this->all(EntityType::Roadmap);

        return $roadmaps === [] ? null : reset($roadmaps);
    }

    public function count(EntityType $type): int
    {
        return count($this->all($type));
    }
}
