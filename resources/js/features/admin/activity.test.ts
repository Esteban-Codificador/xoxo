import { afterEach, describe, expect, it } from 'vitest';
import { activitySentence } from './activity';

const entry = {
    id: 1,
    action: 'PUBLISHED' as const,
    entity: 'lesson',
    label: 'Ramas, merge y rebase',
    user: null,
    created_at: '2026-09-25T17:09:00+00:00',
};

afterEach(() => {
    document.documentElement.lang = 'es';
});

describe('activitySentence', () => {
    it('builds a grammatical sentence in Spanish', () => {
        expect(activitySentence(entry)).toBe(
            'Sistema publicó la lección «Ramas, merge y rebase»',
        );
        expect(
            activitySentence({
                ...entry,
                action: 'IMPORTED',
                entity: 'resource',
                user: 'Ana',
            }),
        ).toBe('Ana importó el recurso «Ramas, merge y rebase»');
    });

    it('uses the target name for role changes', () => {
        expect(
            activitySentence({
                ...entry,
                action: 'ROLE_ASSIGNED',
                entity: 'user',
                label: 'Ada',
                user: 'Admin',
            }),
        ).toBe('Admin asignó un rol a Ada');
    });

    it('follows the word order of English', () => {
        document.documentElement.lang = 'en';

        expect(
            activitySentence({ ...entry, action: 'SUBMITTED', user: 'Ana' }),
        ).toBe('Ana submitted the lesson «Ramas, merge y rebase» for review');
    });

    it('survives unknown entities and deleted subjects', () => {
        expect(
            activitySentence({ ...entry, entity: 'badge', label: null }),
        ).toBe('Sistema publicó badge');
    });
});
