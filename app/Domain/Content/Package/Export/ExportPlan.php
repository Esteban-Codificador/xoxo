<?php

namespace App\Domain\Content\Package\Export;

use App\Domain\Content\Package\ContentPackage;

/**
 * Everything an export would do, computed without touching the target:
 * the files to write or delete, the package they add up to (already
 * validated), and what stops it (conflicts) or deserves a word (warnings).
 */
final readonly class ExportPlan
{
    /**
     * @param  list<FileChange>  $changes
     * @param  list<string>  $warnings
     * @param  list<string>  $conflicts
     * @param  array<string, array<string, ExportedEntity>>  $entities  By type and key.
     * @param  list<string>  $forgottenKeys  Record keys of entities no longer in the package.
     */
    public function __construct(
        public string $path,
        public string $package,
        public bool $copy,
        public array $changes,
        public ContentPackage $result,
        public array $entities,
        public array $warnings,
        public array $conflicts,
        public array $forgottenKeys,
    ) {}

    public function count(FileChangeKind $kind): int
    {
        return count(array_filter($this->changes, fn (FileChange $change) => $change->kind === $kind));
    }

    /**
     * @return list<FileChange>
     */
    public function pending(): array
    {
        return array_values(array_filter($this->changes, fn (FileChange $change) => $change->kind !== FileChangeKind::Unchanged));
    }
}
