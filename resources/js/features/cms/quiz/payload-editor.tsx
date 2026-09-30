import { ArrowDown, ArrowUp, Plus, X } from 'lucide-react';
import { useId } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { t } from '@/i18n';
import type { QuestionType } from '@/types/enums';
import type { Payload } from './types';

const MAX = 8;
const MIN = 2;

type Props = {
    type: QuestionType;
    value: Payload;
    onChange: (payload: Payload) => void;
    /** Server errors by path inside the payload ("options.1.text"). */
    errorFor: (path: string) => string | undefined;
};

function move<T>(list: T[], index: number, offset: number): T[] {
    const next = [...list];
    [next[index], next[index + offset]] = [next[index + offset], next[index]];

    return next;
}

/** The answer data of a question, by type: what the grader compares against. */
export function PayloadEditor({ type, value, onChange, errorFor }: Props) {
    const id = useId();

    switch (type) {
        case 'SINGLE_CHOICE':
        case 'MULTIPLE_CHOICE': {
            const options = value.options ?? [];
            const single = type === 'SINGLE_CHOICE';

            return (
                <fieldset className="space-y-3" aria-describedby={`${id}-help`}>
                    <legend className="text-sm font-medium">
                        {t('cms.quizzes.options')}
                    </legend>
                    <p
                        id={`${id}-help`}
                        className="text-xs text-muted-foreground"
                    >
                        {single
                            ? t('cms.quizzes.optionsSingleHelp')
                            : t('cms.quizzes.optionsMultipleHelp')}
                    </p>
                    <ol className="space-y-2">
                        {options.map((option, index) => (
                            <li key={index} className="space-y-1">
                                <div className="flex items-center gap-2">
                                    <label className="flex shrink-0 items-center gap-1.5 text-xs text-muted-foreground">
                                        <input
                                            type={single ? 'radio' : 'checkbox'}
                                            name={`${id}-correct`}
                                            checked={option.correct}
                                            aria-label={t(
                                                'cms.quizzes.correctOption',
                                                { number: index + 1 },
                                            )}
                                            onChange={(event) =>
                                                onChange({
                                                    options: options.map(
                                                        (other, position) => ({
                                                            ...other,
                                                            correct:
                                                                position ===
                                                                index
                                                                    ? event
                                                                          .target
                                                                          .checked
                                                                    : single
                                                                      ? false
                                                                      : other.correct,
                                                        }),
                                                    ),
                                                })
                                            }
                                            className="size-4 accent-primary"
                                        />
                                        <span aria-hidden="true">
                                            {t('cms.quizzes.correct')}
                                        </span>
                                    </label>
                                    <Input
                                        value={option.text}
                                        maxLength={300}
                                        aria-label={t('cms.quizzes.option', {
                                            number: index + 1,
                                        })}
                                        aria-invalid={
                                            errorFor(
                                                `options.${index}.text`,
                                            ) !== undefined
                                        }
                                        className="min-w-0 flex-1"
                                        onChange={(event) =>
                                            onChange({
                                                options: options.map(
                                                    (other, position) =>
                                                        position === index
                                                            ? {
                                                                  ...other,
                                                                  text: event
                                                                      .target
                                                                      .value,
                                                              }
                                                            : other,
                                                ),
                                            })
                                        }
                                    />
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        className="size-8 shrink-0"
                                        disabled={options.length <= MIN}
                                        aria-label={t(
                                            'cms.quizzes.removeOption',
                                            { number: index + 1 },
                                        )}
                                        onClick={() =>
                                            onChange({
                                                options: options.filter(
                                                    (_, position) =>
                                                        position !== index,
                                                ),
                                            })
                                        }
                                    >
                                        <X aria-hidden="true" />
                                    </Button>
                                </div>
                                <InputError
                                    message={errorFor(`options.${index}.text`)}
                                />
                            </li>
                        ))}
                    </ol>
                    <InputError message={errorFor('options')} />
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        disabled={options.length >= MAX}
                        onClick={() =>
                            onChange({
                                options: [
                                    ...options,
                                    { text: '', correct: false },
                                ],
                            })
                        }
                    >
                        <Plus aria-hidden="true" />
                        {t('cms.quizzes.addOption')}
                    </Button>
                </fieldset>
            );
        }
        case 'TRUE_FALSE':
            return (
                <fieldset className="space-y-2">
                    <legend className="text-sm font-medium">
                        {t('cms.quizzes.statement')}
                    </legend>
                    <div className="flex flex-wrap gap-4 text-sm">
                        {[true, false].map((answer) => (
                            <label
                                key={String(answer)}
                                className="flex items-center gap-2"
                            >
                                <input
                                    type="radio"
                                    name={`${id}-answer`}
                                    checked={value.answer === answer}
                                    onChange={() => onChange({ answer })}
                                    className="size-4 accent-primary"
                                />
                                {t(answer ? 'quiz.true' : 'quiz.false')}
                            </label>
                        ))}
                    </div>
                    <InputError message={errorFor('answer')} />
                </fieldset>
            );
        case 'ORDERING': {
            const items = value.items ?? [];

            return (
                <fieldset className="space-y-3" aria-describedby={`${id}-help`}>
                    <legend className="text-sm font-medium">
                        {t('cms.quizzes.items')}
                    </legend>
                    <p
                        id={`${id}-help`}
                        className="text-xs text-muted-foreground"
                    >
                        {t('cms.quizzes.itemsHelp')}
                    </p>
                    <ol className="space-y-2">
                        {items.map((item, index) => (
                            <li key={index} className="space-y-1">
                                <div className="flex items-center gap-2">
                                    <span className="w-5 shrink-0 text-center font-mono text-xs text-muted-foreground tabular-nums">
                                        {index + 1}
                                    </span>
                                    <Input
                                        value={item}
                                        maxLength={300}
                                        aria-label={t('cms.quizzes.item', {
                                            number: index + 1,
                                        })}
                                        aria-invalid={
                                            errorFor(`items.${index}`) !==
                                            undefined
                                        }
                                        className="min-w-0 flex-1"
                                        onChange={(event) =>
                                            onChange({
                                                items: items.map(
                                                    (other, position) =>
                                                        position === index
                                                            ? event.target.value
                                                            : other,
                                                ),
                                            })
                                        }
                                    />
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        className="size-8 shrink-0"
                                        disabled={index === 0}
                                        aria-label={t(
                                            'cms.quizzes.moveItemUp',
                                            {
                                                number: index + 1,
                                            },
                                        )}
                                        onClick={() =>
                                            onChange({
                                                items: move(items, index, -1),
                                            })
                                        }
                                    >
                                        <ArrowUp aria-hidden="true" />
                                    </Button>
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        className="size-8 shrink-0"
                                        disabled={index === items.length - 1}
                                        aria-label={t(
                                            'cms.quizzes.moveItemDown',
                                            { number: index + 1 },
                                        )}
                                        onClick={() =>
                                            onChange({
                                                items: move(items, index, 1),
                                            })
                                        }
                                    >
                                        <ArrowDown aria-hidden="true" />
                                    </Button>
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        className="size-8 shrink-0"
                                        disabled={items.length <= MIN}
                                        aria-label={t(
                                            'cms.quizzes.removeItem',
                                            {
                                                number: index + 1,
                                            },
                                        )}
                                        onClick={() =>
                                            onChange({
                                                items: items.filter(
                                                    (_, position) =>
                                                        position !== index,
                                                ),
                                            })
                                        }
                                    >
                                        <X aria-hidden="true" />
                                    </Button>
                                </div>
                                <InputError
                                    message={errorFor(`items.${index}`)}
                                />
                            </li>
                        ))}
                    </ol>
                    <InputError message={errorFor('items')} />
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        disabled={items.length >= MAX}
                        onClick={() => onChange({ items: [...items, ''] })}
                    >
                        <Plus aria-hidden="true" />
                        {t('cms.quizzes.addItem')}
                    </Button>
                </fieldset>
            );
        }
        case 'MATCHING': {
            const pairs = value.pairs ?? [];
            const update = (
                index: number,
                side: 'left' | 'right',
                text: string,
            ) =>
                onChange({
                    pairs: pairs.map((pair, position) =>
                        position === index ? { ...pair, [side]: text } : pair,
                    ),
                });

            return (
                <fieldset className="space-y-3" aria-describedby={`${id}-help`}>
                    <legend className="text-sm font-medium">
                        {t('cms.quizzes.pairs')}
                    </legend>
                    <p
                        id={`${id}-help`}
                        className="text-xs text-muted-foreground"
                    >
                        {t('cms.quizzes.pairsHelp')}
                    </p>
                    <ol className="space-y-2">
                        {pairs.map((pair, index) => (
                            <li key={index} className="space-y-1">
                                <div className="flex items-center gap-2">
                                    <div className="grid min-w-0 flex-1 gap-2 sm:grid-cols-2">
                                        <Input
                                            value={pair.left}
                                            maxLength={300}
                                            aria-label={t('cms.quizzes.left', {
                                                number: index + 1,
                                            })}
                                            aria-invalid={
                                                errorFor(
                                                    `pairs.${index}.left`,
                                                ) !== undefined
                                            }
                                            onChange={(event) =>
                                                update(
                                                    index,
                                                    'left',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <Input
                                            value={pair.right}
                                            maxLength={300}
                                            aria-label={t('cms.quizzes.right', {
                                                number: index + 1,
                                            })}
                                            aria-invalid={
                                                errorFor(
                                                    `pairs.${index}.right`,
                                                ) !== undefined
                                            }
                                            onChange={(event) =>
                                                update(
                                                    index,
                                                    'right',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        className="size-8 shrink-0"
                                        disabled={pairs.length <= MIN}
                                        aria-label={t(
                                            'cms.quizzes.removePair',
                                            {
                                                number: index + 1,
                                            },
                                        )}
                                        onClick={() =>
                                            onChange({
                                                pairs: pairs.filter(
                                                    (_, position) =>
                                                        position !== index,
                                                ),
                                            })
                                        }
                                    >
                                        <X aria-hidden="true" />
                                    </Button>
                                </div>
                                <InputError
                                    message={
                                        errorFor(`pairs.${index}.left`) ??
                                        errorFor(`pairs.${index}.right`)
                                    }
                                />
                            </li>
                        ))}
                    </ol>
                    <InputError message={errorFor('pairs')} />
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        disabled={pairs.length >= MAX}
                        onClick={() =>
                            onChange({
                                pairs: [...pairs, { left: '', right: '' }],
                            })
                        }
                    >
                        <Plus aria-hidden="true" />
                        {t('cms.quizzes.addPair')}
                    </Button>
                </fieldset>
            );
        }
    }
}
