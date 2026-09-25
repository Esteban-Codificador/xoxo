<?php

namespace App\Domain\Content\Package;

final class ImportReport
{
    /** @var array<string, array<string, int>> type => outcome => count */
    private array $counts = [];

    /** @var list<string> */
    private array $warnings = [];

    public function __construct(public readonly bool $dryRun) {}

    public function record(SourceEntity $entity, ImportOutcome $outcome): void
    {
        $this->counts[$entity->type->value][$outcome->value] = ($this->counts[$entity->type->value][$outcome->value] ?? 0) + 1;

        $warning = match ($outcome) {
            ImportOutcome::SkippedModified => 'modificado en el CMS desde la última importación; no se sobrescribe (usa --force para forzarlo).',
            ImportOutcome::SkippedDeleted => 'eliminado en el CMS; no se vuelve a crear (usa --force para forzarlo).',
            default => null,
        };

        if ($warning !== null) {
            $this->warnings[] = "{$entity->file} [{$entity->recordKey()}]: {$warning}";
        }
    }

    public function warn(string $message): void
    {
        $this->warnings[] = $message;
    }

    public function count(EntityType $type, ImportOutcome $outcome): int
    {
        return $this->counts[$type->value][$outcome->value] ?? 0;
    }

    /**
     * @return array<string, array<string, int>>
     */
    public function counts(): array
    {
        return $this->counts;
    }

    /**
     * @return list<string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }
}
