import { describe, expect, it } from 'vitest';
import { formatMinutes } from './format';

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
