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

/**
 * How long ago a date was, in whole calendar days of the user's clock:
 * "hoy", "ayer", "hace 3 días", "hace 2 semanas", "hace 4 meses".
 */
export function formatRelativeDay(iso: string, now: Date = new Date()): string {
    const day = (date: Date) =>
        Date.UTC(date.getFullYear(), date.getMonth(), date.getDate());
    const days = Math.max(
        0,
        Math.round((day(now) - day(new Date(iso))) / 86_400_000),
    );
    const relative = new Intl.RelativeTimeFormat(currentLocale(), {
        numeric: 'auto',
    });

    if (days < 7) {
        return relative.format(-days, 'day');
    }

    if (days < 30) {
        return relative.format(-Math.floor(days / 7), 'week');
    }

    return days < 365
        ? relative.format(-Math.floor(days / 30), 'month')
        : relative.format(-Math.floor(days / 365), 'year');
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
