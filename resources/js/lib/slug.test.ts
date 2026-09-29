import { describe, expect, it } from 'vitest';
import { slugify } from './slug';

describe('slugify', () => {
    it('turns a Spanish title into the slug shape the server accepts', () => {
        expect(slugify('Git: commits, árbol de trabajo y staging')).toBe(
            'git-commits-arbol-de-trabajo-y-staging',
        );
        expect(slugify('¿Qué es AI Engineering?')).toBe(
            'que-es-ai-engineering',
        );
        expect(slugify('  Ñandú  2.ª edición ')).toBe('nandu-2-a-edicion');
    });

    it('never ends in a dash when cut to the maximum length', () => {
        expect(slugify('uno dos tres', 4)).toBe('uno');
        expect(slugify('---')).toBe('');
    });
});
