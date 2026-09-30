import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import type { RichContent } from '@/features/rich-content';
import { InlineCode } from './inline-code';
import { QuestionInput } from './question-input';
import { QuestionReview, QuestionVerdict } from './question-review';
import { QuizTimer } from './quiz-timer';
import type { Answer, SheetQuestion } from './types';
import { isAnswered } from './types';

const prompt: RichContent = {
    version: 1,
    doc: { type: 'doc', content: [] },
};

const single: SheetQuestion = {
    id: 1,
    type: 'SINGLE_CHOICE',
    prompt,
    points: 1,
    content: {
        options: [
            { id: 'm', text: 'git merge' },
            { id: 'r', text: 'git rebase' },
        ],
    },
};

const multiple: SheetQuestion = { ...single, id: 2, type: 'MULTIPLE_CHOICE' };

const ordering: SheetQuestion = {
    id: 3,
    type: 'ORDERING',
    prompt,
    points: 1,
    content: {
        items: [
            { id: 'c', text: 'commit' },
            { id: 'a', text: 'add' },
            { id: 'p', text: 'push' },
        ],
    },
};

const matching: SheetQuestion = {
    id: 4,
    type: 'MATCHING',
    prompt,
    points: 1,
    content: {
        left: [
            { id: 'l1', text: 'add' },
            { id: 'l2', text: 'commit' },
        ],
        right: [
            { id: 'r2', text: 'guarda' },
            { id: 'r1', text: 'prepara' },
        ],
    },
};

const trueFalse: SheetQuestion = {
    id: 5,
    type: 'TRUE_FALSE',
    prompt,
    points: 1,
    content: [],
};

/** QuestionInput is controlled: this keeps its value like the page does. */
function Harness({
    question,
    onAnswer,
}: {
    question: SheetQuestion;
    onAnswer: (answer: Answer) => void;
}) {
    const [value, setValue] = useState<Answer | undefined>();

    return (
        <QuestionInput
            question={question}
            value={value}
            onChange={(answer) => {
                setValue(answer);
                onAnswer(answer);
            }}
        />
    );
}

describe('QuestionInput', () => {
    it('answers a single choice with the id of the option', async () => {
        const onAnswer = vi.fn();
        render(<Harness question={single} onAnswer={onAnswer} />);

        await userEvent.click(
            screen.getByRole('radio', { name: 'git rebase' }),
        );

        expect(onAnswer).toHaveBeenLastCalledWith({ choice: 'r' });
        expect(screen.getByRole('radio', { name: 'git rebase' })).toBeChecked();
    });

    it('answers a multiple choice with every option marked', async () => {
        const onAnswer = vi.fn();
        render(<Harness question={multiple} onAnswer={onAnswer} />);

        await userEvent.click(
            screen.getByRole('checkbox', { name: 'git merge' }),
        );
        await userEvent.click(
            screen.getByRole('checkbox', { name: 'git rebase' }),
        );
        await userEvent.click(
            screen.getByRole('checkbox', { name: 'git merge' }),
        );

        expect(onAnswer).toHaveBeenLastCalledWith({ choices: ['r'] });
    });

    it('answers true or false', async () => {
        const onAnswer = vi.fn();
        render(<Harness question={trueFalse} onAnswer={onAnswer} />);

        await userEvent.click(screen.getByRole('radio', { name: 'Falso' }));

        expect(onAnswer).toHaveBeenLastCalledWith({ value: false });
    });

    it('reorders items with the keyboard-friendly buttons', async () => {
        const onAnswer = vi.fn();
        render(<Harness question={ordering} onAnswer={onAnswer} />);

        expect(
            screen.getByRole('button', { name: 'Subir «commit»' }),
        ).toBeDisabled();

        await userEvent.click(
            screen.getByRole('button', { name: 'Subir «add»' }),
        );

        expect(onAnswer).toHaveBeenLastCalledWith({ order: ['a', 'c', 'p'] });
        expect(
            screen.getAllByRole('listitem').map((item) => item.textContent),
        ).toEqual(['1add', '2commit', '3push']);
    });

    it('matches each item with a select and clears a pair when emptied', async () => {
        const onAnswer = vi.fn();
        render(<Harness question={matching} onAnswer={onAnswer} />);

        await userEvent.selectOptions(
            screen.getByRole('combobox', { name: 'Pareja de «add»' }),
            'prepara',
        );
        await userEvent.selectOptions(
            screen.getByRole('combobox', { name: 'Pareja de «commit»' }),
            'guarda',
        );
        expect(onAnswer).toHaveBeenLastCalledWith({
            matches: { l1: 'r1', l2: 'r2' },
        });

        await userEvent.selectOptions(
            screen.getByRole('combobox', { name: 'Pareja de «add»' }),
            'Elige…',
        );
        expect(onAnswer).toHaveBeenLastCalledWith({ matches: { l2: 'r2' } });
    });
});

describe('isAnswered', () => {
    it('counts only answers with something in them', () => {
        expect(isAnswered(undefined)).toBe(false);
        expect(isAnswered({ choices: [] })).toBe(false);
        expect(isAnswered({ matches: {} })).toBe(false);
        expect(isAnswered({ value: false })).toBe(true);
        expect(isAnswered({ choice: 'r' })).toBe(true);
    });
});

describe('QuestionReview', () => {
    it('marks what the learner chose and, when revealed, the right answer', () => {
        render(
            <QuestionReview
                question={{
                    ...single,
                    answer: { choice: 'm' },
                    correct: false,
                    solution: { choice: 'r' },
                }}
            />,
        );

        const [merge, rebase] = screen.getAllByRole('listitem');
        expect(merge).toHaveTextContent('Tu respuesta');
        expect(rebase).toHaveTextContent('Respuesta correcta');
    });

    it('keeps the right answer out when the server withholds it', () => {
        render(
            <QuestionReview
                question={{
                    ...single,
                    answer: { choice: 'm' },
                    correct: false,
                    solution: null,
                }}
            />,
        );

        expect(screen.queryByText('Respuesta correcta')).toBeNull();
    });

    it('lists both orders of an ordering question', () => {
        render(
            <QuestionReview
                question={{
                    ...ordering,
                    answer: { order: ['c', 'a', 'p'] },
                    correct: false,
                    solution: { order: ['a', 'c', 'p'] },
                }}
            />,
        );

        expect(screen.getByText('Tu orden').nextSibling).toHaveTextContent(
            'commitaddpush',
        );
        expect(
            screen.getByText('Orden correcto').nextSibling,
        ).toHaveTextContent('addcommitpush');
    });

    it('says whether a question was right, wrong or left unanswered, in words', () => {
        const { rerender } = render(
            <QuestionVerdict
                question={{ ...single, correct: true, answer: { choice: 'r' } }}
            />,
        );
        expect(screen.getByText('Correcta')).toBeInTheDocument();

        rerender(
            <QuestionVerdict
                question={{ ...single, correct: false, answer: null }}
            />,
        );
        expect(screen.getByText('Sin responder')).toBeInTheDocument();

        rerender(
            <QuestionVerdict
                question={{
                    ...single,
                    correct: false,
                    answer: { choice: 'm' },
                }}
            />,
        );
        expect(screen.getByText('Incorrecta')).toBeInTheDocument();
    });

    it('shows each pair of a matching question', () => {
        render(
            <QuestionReview
                question={{
                    ...matching,
                    answer: { matches: { l1: 'r2' } },
                    correct: false,
                    solution: { matches: { l1: 'r1', l2: 'r2' } },
                }}
            />,
        );

        const [add, commit] = screen.getAllByRole('listitem');
        expect(within(add).getByText('guarda')).toBeInTheDocument();
        expect(within(add).getByText('prepara')).toBeInTheDocument();
        expect(commit).toHaveTextContent('Sin responder');
    });
});

describe('InlineCode', () => {
    it('shows backticks as code and never parses HTML', () => {
        const { container } = render(
            <InlineCode text="Usa `git add -p` y no <b>esto</b>" />,
        );

        expect(container.querySelector('code')).toHaveTextContent('git add -p');
        expect(container.querySelector('b')).toBeNull();
        expect(container).toHaveTextContent('<b>esto</b>');
    });
});

describe('QuizTimer', () => {
    afterEach(() => vi.useRealTimers());

    it('counts down and sends once when the time is up', () => {
        vi.useFakeTimers();
        const onExpire = vi.fn();
        render(<QuizTimer seconds={65} onExpire={onExpire} />);

        expect(screen.getByText('1:05')).toBeInTheDocument();

        act(() => {
            vi.advanceTimersByTime(10_000);
        });
        expect(screen.getByText('0:55')).toBeInTheDocument();
        expect(screen.getByText('Queda un minuto.')).toBeInTheDocument();

        act(() => {
            vi.advanceTimersByTime(60_000);
        });
        expect(screen.getByText('0:00')).toBeInTheDocument();
        expect(onExpire).toHaveBeenCalledTimes(1);
    });
});
