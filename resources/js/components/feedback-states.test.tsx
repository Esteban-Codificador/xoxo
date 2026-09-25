import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Inbox } from 'lucide-react';
import { describe, expect, it, vi } from 'vitest';
import { EmptyState } from '@/components/empty-state';
import { ErrorState } from '@/components/error-state';
import { PageHeader } from '@/components/page-header';
import { CardGridSkeleton } from '@/components/skeletons';
import {
    ContentStatusBadge,
    LinkStatusBadge,
} from '@/features/publishing/status-badges';

describe('EmptyState', () => {
    it('explains the empty screen and offers the next action', () => {
        render(
            <EmptyState
                icon={Inbox}
                title="Sin tracks"
                description="Publica el primero"
                action={<button type="button">Crear</button>}
            />,
        );

        expect(screen.getByText('Sin tracks')).toBeInTheDocument();
        expect(screen.getByText('Publica el primero')).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Crear' }),
        ).toBeInTheDocument();
    });
});

describe('ErrorState', () => {
    it('is announced as an alert with a default message', () => {
        render(<ErrorState />);

        expect(screen.getByRole('alert')).toHaveTextContent(
            'No se pudo cargar esta sección.',
        );
        expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });

    it('offers a retry that calls the handler', async () => {
        const onRetry = vi.fn();
        render(<ErrorState onRetry={onRetry} />);

        await userEvent.click(
            screen.getByRole('button', { name: 'Reintentar' }),
        );

        expect(onRetry).toHaveBeenCalledOnce();
    });
});

describe('PageHeader', () => {
    it('renders a single h1 with its description and actions', () => {
        render(
            <PageHeader
                title="Resumen"
                description="Estado del currículo"
                actions={<button type="button">Acción</button>}
            />,
        );

        expect(
            screen.getByRole('heading', { level: 1, name: 'Resumen' }),
        ).toBeInTheDocument();
        expect(screen.getByText('Estado del currículo')).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Acción' }),
        ).toBeInTheDocument();
    });
});

describe('status badges', () => {
    it('translates publishing and link statuses', () => {
        render(
            <>
                <ContentStatusBadge status="REVIEW" />
                <LinkStatusBadge status="BROKEN" />
            </>,
        );

        expect(screen.getByText('En revisión')).toBeInTheDocument();
        expect(screen.getByText('Roto')).toBeInTheDocument();
    });
});

describe('CardGridSkeleton', () => {
    it('marks itself busy and announces loading once', () => {
        const { container } = render(<CardGridSkeleton count={2} />);

        expect(container.firstElementChild).toHaveAttribute(
            'aria-busy',
            'true',
        );
        expect(screen.getAllByText('Cargando…')).toHaveLength(1);
    });
});
