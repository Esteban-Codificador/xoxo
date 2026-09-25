import { afterEach, describe, expect, it } from 'vitest';
import { en } from './en';
import { es } from './es';
import type { TranslationKey } from './index';
import { currentLocale, setLocale, t } from './index';

function leaves(node: object, prefix = ''): string[] {
    return Object.entries(node).flatMap(([key, value]) =>
        typeof value === 'string'
            ? [`${prefix}${key}`]
            : leaves(value as object, `${prefix}${key}.`),
    );
}

describe('t()', () => {
    afterEach(() => setLocale('es'));

    it('uses Spanish by default', () => {
        document.documentElement.lang = '';

        expect(currentLocale()).toBe('es');
        expect(t('nav.logOut')).toBe('Cerrar sesión');
    });

    it('follows the lang attribute of the document', () => {
        setLocale('en');

        expect(document.documentElement.lang).toBe('en');
        expect(t('nav.logOut')).toBe('Log out');
    });

    it('falls back to Spanish for unsupported locales', () => {
        setLocale('fr');

        expect(currentLocale()).toBe('es');
    });

    it('interpolates parameters and keeps unknown placeholders', () => {
        expect(t('settings.passkeys.added', { when: 'hace 2 días' })).toBe(
            'Agregada hace 2 días',
        );
        expect(t('settings.passkeys.added')).toBe('Agregada {when}');
    });

    it('returns the key when it does not exist at runtime', () => {
        expect(t('missing.key' as TranslationKey)).toBe('missing.key');
    });

    it('has exactly the same keys in every language', () => {
        expect(leaves(en).sort()).toEqual(leaves(es).sort());
    });

    it('has no empty translations', () => {
        for (const dictionary of [es, en]) {
            for (const key of leaves(dictionary)) {
                const value = key
                    .split('.')
                    .reduce<unknown>(
                        (node, part) => (node as Record<string, unknown>)[part],
                        dictionary,
                    );

                expect(String(value).trim(), key).not.toBe('');
            }
        }
    });
});
