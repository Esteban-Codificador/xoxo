import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';
import type { ModuleRow } from './module-list';
import { ModuleList } from './module-list';

const git: ModuleRow = {
    id: 7,
    slug: 'git',
    title: 'Git y colaboración',
    summary: 'Ramas, merge y pull requests.',
    status: 'PUBLISHED',
    lessons_count: 3,
    status_actions: [],
};

describe('ModuleList', () => {
    it('adds a module in a dialog whose slug follows the title', async () => {
        render(
            <ModuleList
                trackId={2}
                modules={[git]}
                canCreate
                canCreateLesson
            />,
        );

        await userEvent.click(
            screen.getByRole('button', { name: 'Añadir módulo' }),
        );
        const dialog = screen.getByRole('dialog', { name: 'Nuevo módulo' });

        await userEvent.type(
            within(dialog).getByLabelText('Título'),
            'Colaboración en GitHub',
        );
        expect(within(dialog).getByLabelText('Slug')).toHaveValue(
            'colaboracion-en-github',
        );

        // Once edited by hand, the slug stops following the title.
        await userEvent.clear(within(dialog).getByLabelText('Slug'));
        await userEvent.type(within(dialog).getByLabelText('Slug'), 'github');
        await userEvent.type(within(dialog).getByLabelText('Título'), '!');
        expect(within(dialog).getByLabelText('Slug')).toHaveValue('github');
    });

    it('links each module to a new lesson in it', () => {
        render(
            <ModuleList
                trackId={2}
                modules={[git]}
                canCreate
                canCreateLesson
            />,
        );

        expect(
            screen.getByRole('link', {
                name: 'Añadir una lección a Git y colaboración',
            }),
        ).toHaveAttribute('href', '/admin/lessons/create?module=7');
    });

    it('offers nothing to create without permission', () => {
        render(<ModuleList trackId={2} modules={[]} />);

        expect(
            screen.getByText('Este track todavía no tiene módulos.'),
        ).toBeVisible();
        expect(
            screen.queryByRole('button', { name: 'Añadir módulo' }),
        ).toBeNull();
    });
});
