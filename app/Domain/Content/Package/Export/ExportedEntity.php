<?php

namespace App\Domain\Content\Package\Export;

use App\Domain\Content\Package\EntityType;
use Illuminate\Database\Eloquent\Model;

/**
 * One database row as it will be written to the package: its key, the file
 * it lives in, its front matter (or YAML item) in output order and its
 * Markdown body.
 */
final readonly class ExportedEntity
{
    /**
     * @param  array<string, mixed>  $data  Null values are left out of the file.
     * @param  int  $order  Place inside a shared file (resources).
     */
    public function __construct(
        public EntityType $type,
        public string $key,
        public Model $model,
        public string $file,
        public array $data,
        public ?string $body = null,
        public int $order = 0,
    ) {}
}
