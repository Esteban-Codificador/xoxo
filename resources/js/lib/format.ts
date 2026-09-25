import { currentLocale } from '@/i18n';

/** Date and time in the user's language, e.g. "25 sept 2026, 17:13". */
export function formatDateTime(iso: string): string {
    return new Intl.DateTimeFormat(currentLocale(), {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(iso));
}
