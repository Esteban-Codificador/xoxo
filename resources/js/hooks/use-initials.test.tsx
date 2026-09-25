import { renderHook } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { useInitials } from '@/hooks/use-initials';

describe('useInitials', () => {
    const getInitials = renderHook(() => useInitials()).result.current;

    it('uses the first and last name', () => {
        expect(getInitials('Esteban Andrés Ávila')).toBe('EÁ');
    });

    it('keeps accented and multi-byte initials intact', () => {
        expect(getInitials('ángela núñez')).toBe('ÁN');
    });

    it('handles a single name and extra whitespace', () => {
        expect(getInitials('  ada  ')).toBe('A');
        expect(getInitials('   ')).toBe('');
    });
});
