import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { Pagination } from './pagination';

describe('Pagination', () => {
    it('shows nothing when everything fits in one page', () => {
        const { container } = render(
            <Pagination
                pagination={{
                    page: 1,
                    pages: 1,
                    total: 3,
                    previous: null,
                    next: null,
                }}
            />,
        );

        expect(container).toBeEmptyDOMElement();
    });

    it('links to the neighbour pages the server sends', () => {
        render(
            <Pagination
                pagination={{
                    page: 1,
                    pages: 2,
                    total: 30,
                    previous: null,
                    next: '/admin/users?role=STUDENT&page=2',
                }}
            />,
        );

        const nav = screen.getByRole('navigation', { name: 'Paginación' });
        expect(nav).toHaveTextContent('Página 1 de 2');
        expect(screen.getByRole('button', { name: 'Anterior' })).toBeDisabled();
        expect(screen.getByRole('link', { name: 'Siguiente' })).toHaveAttribute(
            'href',
            '/admin/users?role=STUDENT&page=2',
        );
    });
});
