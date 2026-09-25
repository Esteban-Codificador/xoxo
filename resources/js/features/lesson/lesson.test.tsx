import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { LessonPager } from './lesson-pager';
import type { ResourceLink } from './resource-list';
import { ResourceList } from './resource-list';
import { TableOfContents } from './table-of-contents';

const resource: ResourceLink = {
    title: 'Pro Git',
    url: 'https://git-scm.com/book/en/v2',
    type: 'BOOK',
    provider: 'Git (git-scm.com)',
    description: 'El libro de referencia.',
    language: 'en',
    is_official: true,
    link_status: 'OK',
    note: null,
};

describe('TableOfContents', () => {
    it('links every H2 and H3 of the body', () => {
        render(
            <TableOfContents
                headings={[
                    { id: 'concepto', text: 'Concepto', level: 2 },
                    { id: 'detalle', text: 'Detalle', level: 3 },
                    { id: 'nota', text: 'Nota', level: 4 },
                ]}
            />,
        );

        const nav = screen.getByRole('navigation', { name: 'En esta lección' });
        expect(nav.querySelectorAll('a')).toHaveLength(2);
        expect(screen.getByRole('link', { name: 'Detalle' })).toHaveAttribute(
            'href',
            '#detalle',
        );
    });

    it('stays out of the way for short lessons', () => {
        const { container } = render(
            <TableOfContents
                headings={[{ id: 'unico', text: 'Único', level: 2 }]}
            />,
        );

        expect(container).toBeEmptyDOMElement();
    });
});

describe('ResourceList', () => {
    it('opens resources in a new tab and says what they are', () => {
        render(<ResourceList resources={[resource]} />);

        const link = screen.getByRole('link', { name: /Pro Git/ });
        expect(link).toHaveAttribute('target', '_blank');
        expect(link).toHaveAttribute('rel', 'noopener noreferrer');
        expect(screen.getByText('Libro')).toBeInTheDocument();
        expect(screen.getByText('Inglés')).toBeInTheDocument();
        expect(screen.getByText('Fuente oficial')).toBeInTheDocument();
        expect(screen.queryByText(/no encontró este enlace/)).toBeNull();
    });

    it('prefers the note of the lesson and warns about broken links', () => {
        render(
            <ResourceList
                resources={[
                    {
                        ...resource,
                        note: 'Lee el capítulo 3.',
                        link_status: 'BROKEN',
                    },
                ]}
            />,
        );

        expect(screen.getByText('Lee el capítulo 3.')).toBeInTheDocument();
        expect(screen.queryByText('El libro de referencia.')).toBeNull();
        expect(screen.getByText(/no encontró este enlace/)).toBeInTheDocument();
    });
});

describe('LessonPager', () => {
    it('links the neighbouring lessons', () => {
        render(
            <LessonPager
                previous={{ slug: 'commits', title: 'Commits' }}
                next={{ slug: 'ramas', title: 'Ramas' }}
            />,
        );

        expect(
            screen.getByRole('link', { name: /Anterior.*Commits/ }),
        ).toHaveAttribute('href', '/lessons/commits');
        expect(
            screen.getByRole('link', { name: /Siguiente.*Ramas/ }),
        ).toHaveAttribute('href', '/lessons/ramas');
    });

    it('says when the track ends', () => {
        render(
            <LessonPager
                previous={{ slug: 'commits', title: 'Commits' }}
                next={null}
            />,
        );

        expect(
            screen.getByText('Es la última lección del track.'),
        ).toBeInTheDocument();
    });
});
