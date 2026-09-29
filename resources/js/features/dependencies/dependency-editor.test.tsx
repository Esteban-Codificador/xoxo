import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { describe, expect, it, vi } from 'vitest';
import type { DependencyOption, DependencyRow } from './dependency-editor';
import { DependencyEditor } from './dependency-editor';

const options: DependencyOption[] = [
    { id: 1, title: 'Base', published: true },
    { id: 2, title: 'Intermedio', published: true },
    { id: 3, title: 'Borrador', published: false },
];

function Harness({
    initial,
    withMinProgress = true,
    onChange = vi.fn(),
}: {
    initial: DependencyRow[];
    withMinProgress?: boolean;
    onChange?: (rows: DependencyRow[]) => void;
}) {
    const [value, setValue] = useState(initial);

    return (
        <DependencyEditor
            options={options}
            value={value}
            withMinProgress={withMinProgress}
            errorFor={(index) => (index === 0 ? 'Crearía un ciclo' : undefined)}
            onChange={(rows) => {
                setValue(rows);
                onChange(rows);
            }}
        />
    );
}

describe('DependencyEditor', () => {
    it('says when there are no prerequisites and offers every option', () => {
        render(<Harness initial={[]} />);

        expect(
            screen.getByText('Sin prerrequisitos: es un punto de partida.'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('option', { name: 'Borrador (sin publicar)' }),
        ).toBeInTheDocument();
    });

    it('adds a prerequisite as required at 100 % and hides it from the list', async () => {
        const onChange = vi.fn();
        render(<Harness initial={[]} onChange={onChange} />);

        await userEvent.selectOptions(
            screen.getByLabelText('Añadir prerrequisito'),
            'Intermedio',
        );
        await userEvent.click(screen.getByRole('button', { name: 'Añadir' }));

        expect(onChange).toHaveBeenLastCalledWith([
            { id: 2, kind: 'REQUIRED', min_progress: 100 },
        ]);
        expect(screen.queryByRole('option', { name: 'Intermedio' })).toBeNull();
    });

    it('changes the kind and the minimum progress, removes rows and shows errors', async () => {
        const onChange = vi.fn();
        render(
            <Harness
                initial={[{ id: 1, kind: 'REQUIRED', min_progress: 100 }]}
                onChange={onChange}
            />,
        );

        expect(screen.getByText('Crearía un ciclo')).toBeInTheDocument();

        await userEvent.selectOptions(
            screen.getByLabelText('Tipo de dependencia con Base'),
            'RECOMMENDED',
        );
        const progress = screen.getByLabelText('Progreso mínimo de Base (%)');
        await userEvent.clear(progress);
        await userEvent.type(progress, '60');

        expect(onChange).toHaveBeenLastCalledWith([
            { id: 1, kind: 'RECOMMENDED', min_progress: 60 },
        ]);

        await userEvent.click(
            screen.getByRole('button', { name: 'Quitar Base' }),
        );
        expect(onChange).toHaveBeenLastCalledWith([]);
    });

    it('omits the minimum progress for graphs that do not use it', () => {
        render(
            <Harness
                initial={[{ id: 1, kind: 'REQUIRED', min_progress: 100 }]}
                withMinProgress={false}
            />,
        );

        expect(
            screen.queryByLabelText('Progreso mínimo de Base (%)'),
        ).toBeNull();
    });
});
