<?php

namespace App\Domain\Content\Package;

enum ImportOutcome: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Unchanged = 'unchanged';
    /** The entity was edited in the CMS after the last import; the package does not overwrite it. */
    case SkippedModified = 'skipped_modified';
    /** The entity was deleted in the CMS; the package does not bring it back. */
    case SkippedDeleted = 'skipped_deleted';
}
