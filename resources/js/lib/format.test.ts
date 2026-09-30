import { describe, expect, it } from 'vitest';
import { formatMinutes, formatRelativeDay } from './format';

describe('formatMinutes', () => {
    it.each([
        [0, '0 min'],
        [45, '45 min'],
        [60, '1 h'],
        [90, '1 h 30 min'],
        [150, '2 h 30 min'],
    ])('%i minutes → %s', (minutes, expected) => {
        expect(formatMinutes(minutes)).toBe(expected);
    });
});

describe('formatRelativeDay', () => {
    const now = new Date('2026-09-30T12:00:00');

    it.each([
        ['2026-09-30T08:00:00', 'hoy'],
        ['2026-09-29T23:30:00', 'ayer'],
        ['2026-09-27T12:00:00', 'hace 3 días'],
        ['2026-09-16T12:00:00', 'hace 2 semanas'],
        ['2026-06-30T12:00:00', 'hace 3 meses'],
        ['2024-09-30T12:00:00', 'hace 2 años'],
    ])('%s → %s', (iso, expected) => {
        expect(formatRelativeDay(iso, now)).toBe(expected);
    });

    it('never says a date is in the future', () => {
        expect(formatRelativeDay('2026-10-02T12:00:00', now)).toBe('hoy');
    });
});
