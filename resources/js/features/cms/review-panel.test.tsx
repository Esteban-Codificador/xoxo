import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';
import type { ReviewPermissions, ReviewState } from './review-panel';
import { ReviewPanel } from './review-panel';

const noReview: ReviewState = {
    open: null,
    returned: null,
    submit_blocker: null,
};

const author: ReviewPermissions = {
    submit: true,
    return: false,
    withdraw: true,
};

const editor: ReviewPermissions = {
    submit: false,
    return: true,
    withdraw: true,
};

function panel(
    review: ReviewState,
    can: ReviewPermissions,
    { dirty = false, published = false } = {},
) {
    return render(
        <ReviewPanel
            lessonSlug="commits"
            lessonTitle="Commits"
            hasPublishedVersion={published}
            review={review}
            can={can}
            dirty={dirty}
        />,
    );
}

describe('ReviewPanel', () => {
    it('lets the author send a saved, ready lesson for review', () => {
        panel(noReview, author);

        expect(
            screen.getByRole('button', { name: 'Enviar a revisión' }),
        ).toBeEnabled();
        expect(screen.getByLabelText('Nota para el revisor')).toBeVisible();
    });

    it('explains why it cannot be sent yet', () => {
        const { rerender } = panel(noReview, author, { dirty: true });

        expect(
            screen.getByRole('button', { name: 'Enviar a revisión' }),
        ).toBeDisabled();
        expect(
            screen.getByText('Guarda los cambios antes de enviarla.'),
        ).toBeVisible();

        rerender(
            <ReviewPanel
                lessonSlug="commits"
                lessonTitle="Commits"
                hasPublishedVersion={false}
                review={{ ...noReview, submit_blocker: 'not_ready' }}
                can={author}
                dirty={false}
            />,
        );
        expect(
            screen.getByText(
                'Todavía no cumple los requisitos para publicarse. Revisa la lista.',
            ),
        ).toBeVisible();
    });

    it('shows the open review and lets a reviewer return it with a comment', async () => {
        panel(
            {
                ...noReview,
                open: {
                    submitted_by: 'Ana Instructora',
                    submitted_at: '2026-09-29T10:00:00Z',
                    note: 'Revisa la práctica.',
                    edited_since: false,
                },
            },
            editor,
            { published: true },
        );

        expect(screen.getByText('En revisión')).toBeVisible();
        expect(screen.getByText(/Enviada por Ana Instructora/)).toBeVisible();
        expect(screen.getByText('Revisa la práctica.')).toBeVisible();
        expect(
            screen.getByRole('link', { name: 'Ver los cambios' }),
        ).toHaveAttribute('href', '/admin/lessons/commits/changes');
        expect(
            screen.queryByRole('button', { name: 'Enviar a revisión' }),
        ).toBeNull();

        await userEvent.click(
            screen.getByRole('button', { name: 'Devolver con comentarios' }),
        );
        const dialog = screen.getByRole('dialog', {
            name: 'Devolver «Commits»',
        });
        const send = within(dialog).getByRole('button', {
            name: 'Devolver con comentarios',
        });
        expect(send).toBeDisabled();

        await userEvent.type(
            within(dialog).getByLabelText('Qué debe cambiar'),
            'Falta un ejemplo.',
        );
        expect(send).toBeEnabled();
    });

    it('shows the author the comment of the last return', () => {
        panel(
            {
                ...noReview,
                returned: {
                    by: 'Eva Editora',
                    at: '2026-09-29T12:00:00Z',
                    comment: 'Falta un ejemplo en la práctica.',
                },
            },
            author,
        );

        expect(screen.getByText(/Devuelta por Eva Editora/)).toBeVisible();
        expect(
            screen.getByText('Falta un ejemplo en la práctica.'),
        ).toBeVisible();
    });

    it('renders nothing for a reviewer when there is no review', () => {
        const { container } = panel(noReview, editor);

        expect(container).toBeEmptyDOMElement();
    });
});
