import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { describe, expect, it, vi } from 'vitest';
import type { ResourceOption } from './resources-editor';
import { ResourcesEditor } from './resources-editor';
import type { SkillRow } from './skills-editor';
import { SkillsEditor } from './skills-editor';

function Skills({ onChange }: { onChange: (rows: SkillRow[]) => void }) {
    const [value, setValue] = useState<SkillRow[]>([{ id: 1, weight: 2 }]);

    return (
        <SkillsEditor
            options={[
                { id: 1, title: 'Git', published: true },
                { id: 2, title: 'Python', published: false },
            ]}
            value={value}
            onChange={(rows) => {
                setValue(rows);
                onChange(rows);
            }}
        />
    );
}

const resource = (id: number, title: string): ResourceOption => ({
    id,
    title,
    provider: 'Proveedor',
    type: 'DOCUMENTATION',
    url: `https://docs.example.test/${id}`,
    is_official: true,
    link_status: 'OK',
    published: true,
});

function Resources({ onChange }: { onChange: (ids: number[]) => void }) {
    const [value, setValue] = useState([1, 2]);

    return (
        <ResourcesEditor
            options={[
                resource(1, 'Pro Git'),
                resource(2, 'Git docs'),
                resource(3, 'GitHub docs'),
            ]}
            value={value}
            onChange={(ids) => {
                setValue(ids);
                onChange(ids);
            }}
        />
    );
}

describe('SkillsEditor', () => {
    it('adds a skill at weight 3, changes weights and removes skills', async () => {
        const onChange = vi.fn();
        render(<Skills onChange={onChange} />);

        await userEvent.selectOptions(
            screen.getByLabelText('Añadir skill'),
            'Python',
        );
        await userEvent.click(
            screen.getByRole('button', { name: 'Añadir skill' }),
        );
        expect(onChange).toHaveBeenLastCalledWith([
            { id: 1, weight: 2 },
            { id: 2, weight: 3 },
        ]);
        expect(screen.getByText('(sin publicar)')).toBeInTheDocument();

        await userEvent.selectOptions(
            screen.getByLabelText('Peso de Git'),
            '5',
        );
        expect(onChange).toHaveBeenLastCalledWith([
            { id: 1, weight: 5 },
            { id: 2, weight: 3 },
        ]);

        await userEvent.click(
            screen.getByRole('button', { name: 'Quitar Git' }),
        );
        expect(onChange).toHaveBeenLastCalledWith([{ id: 2, weight: 3 }]);
    });
});

describe('ResourcesEditor', () => {
    it('reorders, adds and removes resources and shows where each one points', async () => {
        const onChange = vi.fn();
        render(<Resources onChange={onChange} />);

        expect(
            screen.getByRole('link', { name: /docs\.example\.test\/1/ }),
        ).toHaveAttribute('target', '_blank');
        expect(
            screen.getByRole('button', { name: 'Subir Pro Git' }),
        ).toBeDisabled();

        await userEvent.click(
            screen.getByRole('button', { name: 'Bajar Pro Git' }),
        );
        expect(onChange).toHaveBeenLastCalledWith([2, 1]);

        await userEvent.selectOptions(
            screen.getByLabelText('Añadir recurso'),
            'GitHub docs (Proveedor)',
        );
        await userEvent.click(
            screen.getByRole('button', { name: 'Añadir recurso' }),
        );
        expect(onChange).toHaveBeenLastCalledWith([2, 1, 3]);

        await userEvent.click(
            screen.getByRole('button', { name: 'Quitar Git docs' }),
        );
        expect(onChange).toHaveBeenLastCalledWith([1, 3]);
    });
});
