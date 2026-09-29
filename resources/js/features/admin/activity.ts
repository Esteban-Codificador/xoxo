import { t } from '@/i18n';
import { es } from '@/i18n/es';
import type { AuditAction } from '@/types/enums';

export type ActivityEntry = {
    id: number;
    action: AuditAction;
    entity: string;
    label: string | null;
    /** Where the entity is edited, if it exists and the viewer may open it. */
    href: string | null;
    user: string | null;
    created_at: string;
};

export type AuditChange = {
    field: string;
    before: string | null;
    after: string | null;
    /** Bodies and descriptions are logged as a digest, not as text. */
    hashed: boolean;
};

export type AuditEntry = ActivityEntry & { changes: AuditChange[] };

// Both dictionaries share their keys (checked in i18n.test.ts).
type Subject = keyof typeof es.admin.subject;
type Entity = keyof typeof es.admin.entities;

function isSubject(entity: string): entity is Subject {
    return Object.hasOwn(es.admin.subject, entity);
}

function isEntity(entity: string): entity is Entity {
    return Object.hasOwn(es.admin.entities, entity);
}

/** "lesson" → "Lecciones"; unknown types show as they are stored. */
export function entityLabel(entity: string): string {
    return isEntity(entity) ? t(`admin.entities.${entity}`) : entity;
}

/** "Sistema publicó la lección «Ramas, merge y rebase»". */
export function activitySentence(entry: ActivityEntry): string {
    const noun = isSubject(entry.entity)
        ? t(`admin.subject.${entry.entity}`)
        : entry.entity;

    return t(`admin.activity.${entry.action}`, {
        user: entry.user ?? t('admin.system'),
        subject: entry.label === null ? noun : `${noun} «${entry.label}»`,
        label: entry.label ?? noun,
    });
}
