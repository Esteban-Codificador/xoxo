import type { es } from './es';

/** Same shape as the Spanish source, with every leaf widened to string. */
type Widen<T> = {
    readonly [K in keyof T]: T[K] extends string ? string : Widen<T[K]>;
};

export type Messages = Widen<typeof es>;

type Leaves<T, Prefix extends string = ''> = {
    [K in keyof T & string]: T[K] extends string
        ? `${Prefix}${K}`
        : Leaves<T[K], `${Prefix}${K}.`>;
}[keyof T & string];

export type TranslationKey = Leaves<Messages>;

export type Locale = 'es' | 'en';

export type TranslationParams = Record<string, string | number>;
