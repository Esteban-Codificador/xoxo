<?php

namespace App\Enums;

enum AuditAction: string
{
    use HasValues;

    case Created = 'CREATED';
    case Updated = 'UPDATED';
    case Deleted = 'DELETED';
    case Submitted = 'SUBMITTED';
    case Published = 'PUBLISHED';
    case Unpublished = 'UNPUBLISHED';
    case Archived = 'ARCHIVED';
    case Restored = 'RESTORED';
    case RoleAssigned = 'ROLE_ASSIGNED';
    case RoleRevoked = 'ROLE_REVOKED';
    case Imported = 'IMPORTED';
}
