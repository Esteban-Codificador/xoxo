import { currentLocale, t } from '@/i18n';

/** Date and time in the user's language, e.g. "25 sept 2026, 17:13". */
export function formatDateTime(iso: string): string {
    return new Intl.DateTimeFormat(currentLocale(), {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(iso));
}

/** Date in the user's language, e.g. "25 de septiembre de 2026". */
export function formatDate(iso: string): string {
    return new Intl.DateTimeFormat(currentLocale(), {
        dateStyle: 'long',
    }).format(new Date(iso));
}

/** 45 → "45 min"; 90 → "1 h 30 min"; 120 → "2 h". */
export function formatMinutes(total: number): string {
    const hours = Math.floor(total / 60);
    const minutes = total % 60;

    if (hours === 0) {
        return t('common.minutes', { count: minutes });
    }

    return minutes === 0
        ? t('common.hours', { count: hours })
        : `${t('common.hours', { count: hours })} ${t('common.minutes', { count: minutes })}`;
}
