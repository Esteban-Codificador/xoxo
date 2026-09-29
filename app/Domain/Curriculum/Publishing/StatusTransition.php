<?php

namespace App\Domain\Curriculum\Publishing;

use App\Enums\AuditAction;
use App\Enums\ContentStatus;
use App\Enums\Permission;

/**
 * Status changes the CMS can make directly on tracks and modules (§42).
 * REVIEW belongs to the review flow (phase 5b, step 4) and an archived item
 * goes back to draft before it can be published again.
 */
final class StatusTransition
{
    /**
     * @return list<ContentStatus>
     */
    public static function targets(ContentStatus $from): array
    {
        return match ($from) {
            ContentStatus::Draft => [ContentStatus::Published, ContentStatus::Archived],
            ContentStatus::Review => [ContentStatus::Published, ContentStatus::Draft, ContentStatus::Archived],
            ContentStatus::Published => [ContentStatus::Draft, ContentStatus::Archived],
            ContentStatus::Archived => [ContentStatus::Draft],
        };
    }

    public static function allowed(ContentStatus $from, ContentStatus $to): bool
    {
        return in_array($to, self::targets($from), true);
    }

    /** Archiving and restoring need content.archive; the rest, content.publish. */
    public static function permission(ContentStatus $from, ContentStatus $to): Permission
    {
        return $to === ContentStatus::Archived || $from === ContentStatus::Archived
            ? Permission::ContentArchive
            : Permission::ContentPublish;
    }

    public static function auditAction(ContentStatus $from, ContentStatus $to): AuditAction
    {
        return match (true) {
            $to === ContentStatus::Published => AuditAction::Published,
            $to === ContentStatus::Archived => AuditAction::Archived,
            $from === ContentStatus::Archived => AuditAction::Restored,
            $from === ContentStatus::Published => AuditAction::Unpublished,
            default => AuditAction::Updated,
        };
    }
}
