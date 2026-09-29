<?php

namespace App\Domain\Content\Package\Export;

enum FileChangeKind: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Unchanged = 'unchanged';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'nuevo',
            self::Updated => 'modificado',
            self::Deleted => 'eliminado',
            self::Unchanged => 'sin cambios',
        };
    }
}
