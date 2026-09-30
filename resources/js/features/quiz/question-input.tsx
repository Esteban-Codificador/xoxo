import { ArrowDown, ArrowUp } from 'lucide-react';
import { useId } from 'react';
import { Button } from '@/components/ui/button';
import { t } from '@/i18n';
import { InlineCode } from './inline-code';
import type { Answer, Choice, SheetQuestion } from './types';

type Props = {
    question: SheetQuestion;
    value: Answer | undefined;
    onChange: (answer: Answer) => void;
    disabled?: boolean;
};

const option =
    'flex cursor-pointer items-start gap-3 rounded-lg border px-4 py-3 text-sm transition-colors hover:bg-muted/50 has-[:checked]:border-primary has-[:checked]:bg-primary/5 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring';

/**
 * The answer controls of one question, by type. Choices are native radios
 * and checkboxes, ordering moves with buttons and matching uses selects:
 * all of it works with the keyboard and a screen reader.
 */
export function QuestionInput({ question, value, onChange, disabled }: Props) {
    const name = useId();

    switch (question.type) {
        case 'SINGLE_CHOICE':
            return (
                <div className="grid gap-2">
                    {question.content.options.map((choice) => (
                        <label key={choice.id} className={option}>
                            <input
                                type="radio"
                                name={name}
                                value={choice.id}
                                checked={value?.choice === choice.id}
                                disabled={disabled}
                                onChange={() => onChange({ choice: choice.id })}
                                className="mt-0.5 size-4 shrink-0 accent-primary"
                            />
                            <span>
                                <InlineCode text={choice.text} />
                            </span>
                        </label>
                    ))}
                </div>
            );
        case 'MULTIPLE_CHOICE': {
            const chosen = value?.choices ?? [];

            return (
                <div className="grid gap-2">
                    {question.content.options.map((choice) => (
                        <label key={choice.id} className={option}>
                            <input
                                type="checkbox"
                                value={choice.id}
                                checked={chosen.includes(choice.id)}
                                disabled={disabled}
                                onChange={(event) =>
                                    onChange({
                                        choices: event.target.checked
                                            ? [...chosen, choice.id]
                                            : chosen.filter(
                                                  (id) => id !== choice.id,
                                              ),
                                    })
                                }
                                className="mt-0.5 size-4 shrink-0 accent-primary"
                            />
                            <span>
                                <InlineCode text={choice.text} />
                            </span>
                        </label>
                    ))}
                </div>
            );
        }
        case 'TRUE_FALSE':
            return (
                <div className="grid gap-2 sm:grid-cols-2">
                    {[true, false].map((answer) => (
                        <label key={String(answer)} className={option}>
                            <input
                                type="radio"
                                name={name}
                                checked={value?.value === answer}
                                disabled={disabled}
                                onChange={() => onChange({ value: answer })}
                                className="mt-0.5 size-4 shrink-0 accent-primary"
                            />
                            <span>
                                {t(answer ? 'quiz.true' : 'quiz.false')}
                            </span>
                        </label>
                    ))}
                </div>
            );
        case 'ORDERING':
            return (
                <OrderingInput
                    items={question.content.items}
                    value={value?.order}
                    onChange={(order) => onChange({ order })}
                    disabled={disabled}
                />
            );
        case 'MATCHING':
            return (
                <div className="grid gap-3">
                    {question.content.left.map((left) => (
                        <div
                            key={left.id}
                            className="grid gap-2 rounded-lg border px-4 py-3 text-sm sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] sm:items-center"
                        >
                            <span className="font-medium">
                                <InlineCode text={left.text} />
                            </span>
                            <select
                                aria-label={t('quiz.matchFor', {
                                    item: left.text,
                                })}
                                value={value?.matches?.[left.id] ?? ''}
                                disabled={disabled}
                                onChange={(event) => {
                                    const matches = {
                                        ...value?.matches,
                                    };

                                    if (event.target.value === '') {
                                        delete matches[left.id];
                                    } else {
                                        matches[left.id] = event.target.value;
                                    }

                                    onChange({ matches });
                                }}
                                className="h-9 w-full min-w-0 rounded-md border bg-background px-3 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <option value="">{t('quiz.choose')}</option>
                                {question.content.right.map((right) => (
                                    <option key={right.id} value={right.id}>
                                        {right.text}
                                    </option>
                                ))}
                            </select>
                        </div>
                    ))}
                </div>
            );
    }
}

function OrderingInput({
    items,
    value,
    onChange,
    disabled,
}: {
    items: Choice[];
    value: string[] | undefined;
    onChange: (order: string[]) => void;
    disabled?: boolean;
}) {
    const byId = new Map(items.map((item) => [item.id, item]));
    // The order shown is the answer: untouched, it is the server's shuffle.
    const order = value ?? items.map((item) => item.id);

    const move = (index: number, offset: number) => {
        const next = [...order];
        [next[index], next[index + offset]] = [
            next[index + offset],
            next[index],
        ];
        onChange(next);
    };

    return (
        <ol className="grid gap-2">
            {order.map((id, index) => {
                const item = byId.get(id);

                if (item === undefined) {
                    return null;
                }

                return (
                    <li
                        key={id}
                        className="flex items-center gap-3 rounded-lg border px-3 py-2 text-sm"
                    >
                        <span
                            className="w-6 shrink-0 text-center font-mono text-muted-foreground tabular-nums"
                            aria-label={t('quiz.position', {
                                number: index + 1,
                            })}
                        >
                            {index + 1}
                        </span>
                        <span className="min-w-0 flex-1">
                            <InlineCode text={item.text} />
                        </span>
                        <span className="flex shrink-0 gap-1">
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                className="size-8"
                                disabled={disabled || index === 0}
                                aria-label={t('quiz.moveUp', {
                                    item: item.text,
                                })}
                                onClick={() => move(index, -1)}
                            >
                                <ArrowUp aria-hidden="true" />
                            </Button>
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                className="size-8"
                                disabled={
                                    disabled || index === order.length - 1
                                }
                                aria-label={t('quiz.moveDown', {
                                    item: item.text,
                                })}
                                onClick={() => move(index, 1)}
                            >
                                <ArrowDown aria-hidden="true" />
                            </Button>
                        </span>
                    </li>
                );
            })}
        </ol>
    );
}
