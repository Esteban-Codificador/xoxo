import { ArrowDown, ArrowUp, Plus, X } from 'lucide-react';
import type { ReactNode } from 'react';
import { useId, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { t } from '@/i18n';
import { cn } from '@/lib/utils';

const selectClass =
    'h-9 rounded-md border border-input bg-transparent px-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

/**
 * An ordered list of catalog entries attached to a lesson (resources,
 * videos): reorder with buttons (keyboard friendly, no dragging), remove,
 * and add from what is not attached yet. The entries themselves are
 * created in their own section of the CMS.
 */
export function OrderedLinksEditor<T extends { id: number; title: string }>({
    options,
    value,
    onChange,
    errorFor,
    describe,
    optionLabel,
    emptyText,
    addLabel,
}: {
    options: T[];
    value: number[];
    onChange: (value: number[]) => void;
    errorFor?: (index: number) => string | undefined;
    /** The line under each attached entry. */
    describe: (option: T) => ReactNode;
    optionLabel: (option: T) => string;
    emptyText: string;
    addLabel: string;
}) {
    const id = useId();
    const [candidate, setCandidate] = useState('');
    const byId = new Map(options.map((option) => [option.id, option]));
    const available = options.filter((option) => !value.includes(option.id));

    const move = (index: number, delta: -1 | 1) => {
        const next = [...value];
        [next[index], next[index + delta]] = [next[index + delta], next[index]];
        onChange(next);
    };

    const add = () => {
        const option = available.find((item) => String(item.id) === candidate);

        if (option !== undefined) {
            onChange([...value, option.id]);
            setCandidate('');
        }
    };

    return (
        <div className="space-y-3">
            {value.length === 0 ? (
                <p className="text-sm text-muted-foreground">{emptyText}</p>
            ) : (
                <ol className="divide-y rounded-lg border">
                    {value.map((entryId, index) => {
                        const entry = byId.get(entryId);
                        const name = entry?.title ?? `#${entryId}`;

                        return (
                            <li key={entryId} className="space-y-1 p-3">
                                <div className="flex items-start gap-2">
                                    <div className="flex shrink-0 flex-col">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            className="size-7"
                                            disabled={index === 0}
                                            aria-label={t(
                                                'cms.relations.moveUp',
                                                { name },
                                            )}
                                            onClick={() => move(index, -1)}
                                        >
                                            <ArrowUp aria-hidden="true" />
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            className="size-7"
                                            disabled={
                                                index === value.length - 1
                                            }
                                            aria-label={t(
                                                'cms.relations.moveDown',
                                                { name },
                                            )}
                                            onClick={() => move(index, 1)}
                                        >
                                            <ArrowDown aria-hidden="true" />
                                        </Button>
                                    </div>
                                    <div className="min-w-0 flex-1 space-y-1">
                                        <p className="text-sm font-medium">
                                            {name}
                                        </p>
                                        {entry !== undefined && (
                                            <div className="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground">
                                                {describe(entry)}
                                            </div>
                                        )}
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="shrink-0"
                                        aria-label={t('cms.relations.remove', {
                                            name,
                                        })}
                                        onClick={() =>
                                            onChange(
                                                value.filter(
                                                    (_, i) => i !== index,
                                                ),
                                            )
                                        }
                                    >
                                        <X aria-hidden="true" />
                                    </Button>
                                </div>
                                <InputError message={errorFor?.(index)} />
                            </li>
                        );
                    })}
                </ol>
            )}

            {available.length > 0 && (
                <div className="flex flex-wrap items-center gap-2">
                    <label htmlFor={`${id}-add`} className="sr-only">
                        {addLabel}
                    </label>
                    <select
                        id={`${id}-add`}
                        value={candidate}
                        className={cn(
                            selectClass,
                            'max-w-full min-w-56 flex-1 sm:flex-none',
                        )}
                        onChange={(event) => setCandidate(event.target.value)}
                    >
                        <option value="">{t('cms.relations.choose')}</option>
                        {available.map((option) => (
                            <option key={option.id} value={option.id}>
                                {optionLabel(option)}
                            </option>
                        ))}
                    </select>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={candidate === ''}
                        onClick={add}
                    >
                        <Plus aria-hidden="true" />
                        {addLabel}
                    </Button>
                </div>
            )}
        </div>
    );
}
