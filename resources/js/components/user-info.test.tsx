import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { UserInfo } from '@/components/user-info';
import type { User } from '@/types';

const user: User = {
    id: 1,
    name: 'Ada Lovelace',
    email: 'ada@example.test',
    email_verified_at: null,
    created_at: '2026-09-25T00:00:00Z',
    updated_at: '2026-09-25T00:00:00Z',
};

describe('UserInfo', () => {
    it('shows the name and the initials fallback', () => {
        render(<UserInfo user={user} />);

        expect(screen.getByText('Ada Lovelace')).toBeInTheDocument();
        expect(screen.getByText('AL')).toBeInTheDocument();
        expect(screen.queryByText('ada@example.test')).not.toBeInTheDocument();
    });

    it('shows the email only when requested', () => {
        render(<UserInfo user={user} showEmail />);

        expect(screen.getByText('ada@example.test')).toBeInTheDocument();
    });
});
