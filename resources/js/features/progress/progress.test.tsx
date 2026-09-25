import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { ProgressBar } from '@/features/progress/progress-bar';
import { StateBadge } from '@/features/progress/state-badge';
import { NodeState } from '@/types/enums';

describe('StateBadge', () => {
    it.each([
        [NodeState.Locked, 'Bloqueado'],
        [NodeState.Available, 'Disponible'],
        [NodeState.InProgress, 'En curso'],
        [NodeState.Completed, 'Completado'],
        [NodeState.Mastered, 'Dominado'],
    ])('shows %s as text, not only as color', (state, label) => {
        const { container } = render(<StateBadge state={state} />);

        expect(screen.getByText(label)).toBeInTheDocument();
        // The icon is decorative: the label carries the meaning.
        expect(container.querySelector('svg')).toHaveAttribute(
            'aria-hidden',
            'true',
        );
    });
});

describe('ProgressBar', () => {
    it('exposes its value to assistive technology', () => {
        render(<ProgressBar value={45} />);

        const bar = screen.getByRole('progressbar', { name: 'Progreso: 45 %' });
        expect(bar).toHaveAttribute('aria-valuenow', '45');
        expect(bar).toHaveAttribute('aria-valuemin', '0');
        expect(bar).toHaveAttribute('aria-valuemax', '100');
    });

    it.each([
        [-10, '0'],
        [133.3, '100'],
        [66.6, '67'],
    ])('clamps and rounds %s to %s', (value, expected) => {
        render(<ProgressBar value={value} />);

        expect(screen.getByRole('progressbar')).toHaveAttribute(
            'aria-valuenow',
            expected,
        );
    });

    it('uses the fill color of the state and can show the value', () => {
        render(
            <ProgressBar
                value={80}
                state="COMPLETED"
                showValue
                label="Python"
            />,
        );

        const bar = screen.getByRole('progressbar', { name: 'Python' });
        const fill = bar.firstElementChild as HTMLElement;

        expect(fill).toHaveClass('bg-state-completed');
        expect(fill.style.width).toBe('80%');
        expect(screen.getByText('80 %')).toBeInTheDocument();
    });
});
