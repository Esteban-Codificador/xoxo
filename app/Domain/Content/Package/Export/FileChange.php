<?php

namespace App\Domain\Content\Package\Export;

final readonly class FileChange
{
    /**
     * @param  string|null  $contents  Null for a deleted file.
     */
    public function __construct(
        public string $path,
        public FileChangeKind $kind,
        public ?string $contents,
    ) {}
}
