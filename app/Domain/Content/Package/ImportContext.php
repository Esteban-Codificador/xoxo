<?php

namespace App\Domain\Content\Package;

use Illuminate\Database\Eloquent\Model;

/**
 * Package keys resolved to database ids during an import.
 */
final class ImportContext
{
    /** @var array<string, array<string, int>> */
    private array $ids = [];

    public function __construct(public readonly ContentPackage $package, public readonly ImportReport $report) {}

    public function remember(SourceEntity $entity, Model $model): void
    {
        $this->ids[$entity->type->value][$entity->key] = (int) $model->getKey();
    }

    public function id(EntityType $type, ?string $key): ?int
    {
        return $key === null ? null : ($this->ids[$type->value][$key] ?? null);
    }

    /**
     * Resolves referenced keys to ids. Keys that could not be imported (for
     * example, deleted in the CMS) are dropped with a warning.
     *
     * @param  array<string, array<string, mixed>>  $pivotByKey
     * @return array<int, array<string, mixed>>
     */
    public function resolve(SourceEntity $from, EntityType $type, array $pivotByKey): array
    {
        $resolved = [];

        foreach ($pivotByKey as $key => $pivot) {
            $id = $this->id($type, $key);

            if ($id === null) {
                $this->report->warn("{$from->file}: se omite la relación con {$type->value} \"{$key}\" porque no se importó.");

                continue;
            }

            $resolved[$id] = $pivot;
        }

        return $resolved;
    }
}
