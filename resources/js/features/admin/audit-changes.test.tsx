import { render, screen, within } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { AuditChanges } from './audit-changes';

describe('AuditChanges', () => {
    it('shows each field as before and after', () => {
        render(
            <AuditChanges
                changes={[
                    {
                        field: 'role',
                        before: 'STUDENT',
                        after: 'EDITOR',
                        hashed: false,
                    },
                    {
                        field: 'published_at',
                        before: null,
                        after: '2026-09-29 10:00:00',
                        hashed: false,
                    },
                ]}
            />,
        );

        const role = screen.getByRole('row', { name: /role/ });
        expect(
            within(role)
                .getAllByRole('cell')
                .map((cell) => cell.textContent),
        ).toEqual(['STUDENT', 'EDITOR']);
        expect(
            within(
                screen.getByRole('row', { name: /published_at/ }),
            ).getAllByRole('cell')[0],
        ).toHaveTextContent('vacío');
    });

    it('shows long text fields by their digest', () => {
        render(
            <AuditChanges
                changes={[
                    {
                        field: 'body',
                        before: '0123456789abcdef',
                        after: 'fedcba9876543210',
                        hashed: true,
                    },
                ]}
            />,
        );

        expect(
            screen.getByText('Contenido (resumen 0123456789abcdef)'),
        ).toBeVisible();
        expect(
            screen.getByText('Contenido (resumen fedcba9876543210)'),
        ).toBeVisible();
    });
});
