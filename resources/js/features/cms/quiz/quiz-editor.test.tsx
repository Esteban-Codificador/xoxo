import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { describe, expect, it, vi } from 'vitest';
import type { QuestionType } from '@/types/enums';
import { PayloadEditor } from './payload-editor';
import type { Payload } from './types';
import { newQuestion, payloadFor } from './types';

function Harness({
    type,
    initial,
    onPayload,
    errors = {},
}: {
    type: QuestionType;
    initial: Payload;
    onPayload: (payload: Payload) => void;
    errors?: Record<string, string>;
}) {
    const [value, setValue] = useState(initial);

    return (
        <PayloadEditor
            type={type}
            value={value}
            errorFor={(path) => errors[path]}
            onChange={(payload) => {
                setValue(payload);
                onPayload(payload);
            }}
        />
    );
}

describe('PayloadEditor', () => {
    it('marks a single right option and moves the mark when another is chosen', async () => {
        const onPayload = vi.fn();
        render(
            <Harness
                type="SINGLE_CHOICE"
                initial={payloadFor('SINGLE_CHOICE')}
                onPayload={onPayload}
            />,
        );

        await userEvent.type(
            screen.getByRole('textbox', { name: 'Opción 1' }),
            'merge',
        );
        await userEvent.click(
            screen.getByRole('radio', { name: 'La opción 1 es correcta' }),
        );
        await userEvent.click(
            screen.getByRole('radio', { name: 'La opción 2 es correcta' }),
        );

        expect(onPayload).toHaveBeenLastCalledWith({
            options: [
                { text: 'merge', correct: false },
                { text: '', correct: true },
                { text: '', correct: false },
            ],
        });
    });

    it('marks several right options in a multiple choice question', async () => {
        const onPayload = vi.fn();
        render(
            <Harness
                type="MULTIPLE_CHOICE"
                initial={payloadFor('MULTIPLE_CHOICE')}
                onPayload={onPayload}
            />,
        );

        await userEvent.click(
            screen.getByRole('checkbox', { name: 'La opción 1 es correcta' }),
        );
        await userEvent.click(
            screen.getByRole('checkbox', { name: 'La opción 3 es correcta' }),
        );
        await userEvent.click(
            screen.getByRole('button', { name: 'Quitar la opción 2' }),
        );

        expect(onPayload).toHaveBeenLastCalledWith({
            options: [
                { text: '', correct: true },
                { text: '', correct: true },
            ],
        });
        // Two options is the minimum.
        expect(
            screen.getByRole('button', { name: 'Quitar la opción 1' }),
        ).toBeDisabled();
    });

    it('writes ordering items in the right order and reorders them', async () => {
        const onPayload = vi.fn();
        render(
            <Harness
                type="ORDERING"
                initial={{ items: ['commit', 'add'] }}
                onPayload={onPayload}
            />,
        );

        await userEvent.click(
            screen.getByRole('button', { name: 'Subir el elemento 2' }),
        );

        expect(onPayload).toHaveBeenLastCalledWith({
            items: ['add', 'commit'],
        });
    });

    it('shows the server errors next to the field', () => {
        render(
            <Harness
                type="MATCHING"
                initial={payloadFor('MATCHING')}
                onPayload={vi.fn()}
                errors={{ 'pairs.1.right': 'Este texto está repetido.' }}
            />,
        );

        expect(
            screen.getByText('Este texto está repetido.'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('textbox', { name: 'Pareja del elemento 2' }),
        ).toHaveAttribute('aria-invalid', 'true');
    });
});

describe('payloadFor', () => {
    it('keeps the options between single and multiple choice and starts over otherwise', () => {
        const options = [{ text: 'a', correct: true }];

        expect(payloadFor('MULTIPLE_CHOICE', { options })).toEqual({ options });
        expect(payloadFor('TRUE_FALSE', { options })).toEqual({ answer: true });
        expect(newQuestion('ORDERING')).toMatchObject({
            id: null,
            points: 1,
            payload: { items: ['', '', ''] },
        });
        expect(newQuestion('ORDERING').key).not.toBe(
            newQuestion('ORDERING').key,
        );
    });
});
