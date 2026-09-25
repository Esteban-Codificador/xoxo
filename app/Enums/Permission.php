<?php

namespace App\Enums;

enum Permission: string
{
    use HasValues;

    case AdminAccess = 'admin.access';
    case ContentViewAny = 'content.view_any';
    case ContentCreate = 'content.create';
    case ContentUpdateAny = 'content.update_any';
    case ContentUpdateOwn = 'content.update_own';
    case ContentSubmitReview = 'content.submit_review';
    case ContentPublish = 'content.publish';
    case ContentArchive = 'content.archive';
    case ContentDelete = 'content.delete';
    case UsersView = 'users.view';
    case UsersManage = 'users.manage';
    case RolesAssign = 'roles.assign';
    case AuditView = 'audit.view';
    case AnalyticsView = 'analytics.view';

    /**
     * Permission matrix from docs/roadmap.md §4.
     *
     * @return list<self>
     */
    public static function grantedTo(Role $role): array
    {
        return match ($role) {
            Role::Admin => self::cases(),
            Role::Editor => [
                self::AdminAccess, self::ContentViewAny, self::ContentCreate, self::ContentUpdateAny,
                self::ContentUpdateOwn, self::ContentSubmitReview, self::ContentPublish, self::ContentArchive,
                self::AuditView, self::AnalyticsView,
            ],
            Role::Instructor => [
                self::AdminAccess, self::ContentViewAny, self::ContentCreate, self::ContentUpdateOwn,
                self::ContentSubmitReview, self::AnalyticsView,
            ],
            Role::Student => [],
        };
    }
}
