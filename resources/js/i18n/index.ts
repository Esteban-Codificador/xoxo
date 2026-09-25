import { en } from './en';
import { es } from './es';
import type {
    Locale,
    Messages,
    TranslationKey,
    TranslationParams,
} from './types';

export type { Locale, TranslationKey, TranslationParams } from './types';

const dictionaries: Record<Locale, Messages> = { es, en };

/**
 * The locale is the lang attribute of <html>: the server sets it from the
 * user's preference and app.tsx keeps it in sync on every Inertia visit, so
 * screen readers and t() always agree.
 */
export function currentLocale(): Locale {
    if (typeof document === 'undefined') {
        return 'es';
    }

    return document.documentElement.lang === 'en' ? 'en' : 'es';
}

export function setLocale(locale: unknown): void {
    document.documentElement.lang = locale === 'en' ? 'en' : 'es';
}

export function t(key: TranslationKey, params?: TranslationParams): string {
    const value = key
        .split('.')
        .reduce<unknown>(
            (node, part) =>
                node !== null && typeof node === 'object'
                    ? (node as Record<string, unknown>)[part]
                    : undefined,
            dictionaries[currentLocale()],
        );

    const text = typeof value === 'string' ? value : key;

    if (params === undefined) {
        return text;
    }

    return text.replace(/\{(\w+)\}/g, (placeholder, name: string) =>
        name in params ? String(params[name]) : placeholder,
    );
}
