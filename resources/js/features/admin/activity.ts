import { t } from '@/i18n';
import { es } from '@/i18n/es';
import type { AuditAction } from '@/types/enums';

export type ActivityEntry = {
    id: number;
    action: AuditAction;
    entity: string;
    label: string | null;
    user: string | null;
    created_at: string;
};

// Both dictionaries share their keys (checked in i18n.test.ts).
type Subject = keyof typeof es.admin.subject;

function isSubject(entity: string): entity is Subject {
    return Object.hasOwn(es.admin.subject, entity);
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
