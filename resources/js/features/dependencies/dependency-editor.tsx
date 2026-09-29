import { Plus, X } from 'lucide-react';
import { useId, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { t } from '@/i18n';
import { cn } from '@/lib/utils';
import type { DependencyKind } from '@/types/enums';

export type DependencyRow = {
    id: number;
    kind: DependencyKind;
    min_progress: number;
};

export type DependencyOption = {
    id: number;
    title: string;
    published: boolean;
};

const kinds: DependencyKind[] = ['REQUIRED', 'RECOMMENDED'];

const selectClass =
    'h-9 rounded-md border border-input bg-transparent px-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

/**
 * Edits the prerequisites of one node (track, lesson or skill): which
 * nodes, required or recommended and, when the graph uses it, the minimum
 * progress. The server rejects cycles; this component only lists the
 * candidates it received.
 */
export function DependencyEditor({
    options,
    value,
    onChange,
    withMinProgress,
    errorFor,
}: {
    options: DependencyOption[];
    value: DependencyRow[];
    onChange: (value: DependencyRow[]) => void;
    withMinProgress: boolean;
    errorFor?: (index: number) => string | undefined;
}) {
    const id = useId();
    const [candidate, setCandidate] = useState('');
    const byId = new Map(options.map((option) => [option.id, option]));
    const available = options.filter(
        (option) => !value.some((row) => row.id === option.id),
    );

    const update = (index: number, patch: Partial<DependencyRow>) =>
        onChange(
            value.map((row, i) => (i === index ? { ...row, ...patch } : row)),
        );

    const add = () => {
        const option = available.find((item) => String(item.id) === candidate);

        if (option !== undefined) {
            onChange([
                ...value,
                { id: option.id, kind: 'REQUIRED', min_progress: 100 },
            ]);
            setCandidate('');
        }
    };

    return (
        <div className="space-y-3">
            {value.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    {t('cms.dependencies.empty')}
                </p>
            ) : (
                <ul className="divide-y rounded-lg border">
                    {value.map((row, index) => {
                        const name = byId.get(row.id)?.title ?? `#${row.id}`;
                        const error = errorFor?.(index);

                        return (
                            <li key={row.id} className="space-y-1 p-3">
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="min-w-40 flex-1 text-sm font-medium">
                                        {name}
                                        {byId.get(row.id)?.published ===
                                            false && (
                                            <span className="ml-2 text-xs font-normal text-muted-foreground">
                                                (
                                                {t(
                                                    'cms.dependencies.unpublished',
                                                )}
                                                )
                                            </span>
                                        )}
                                    </span>
                                    <select
                                        aria-label={t('cms.dependencies.kind', {
                                            name,
                                        })}
                                        value={row.kind}
                                        className={selectClass}
                                        onChange={(event) =>
                                            update(index, {
                                                kind: event.target
                                                    .value as DependencyKind,
                                            })
                                        }
                                    >
                                        {kinds.map((kind) => (
                                            <option key={kind} value={kind}>
                                                {t(`dependencyKind.${kind}`)}
                                            </option>
                                        ))}
                                    </select>
                                    {withMinProgress && (
                                        <label className="flex items-center gap-1 text-xs text-muted-foreground">
                                            <Input
                                                type="number"
                                                min={1}
                                                max={100}
                                                value={row.min_progress}
                                                aria-label={t(
                                                    'cms.dependencies.minProgress',
                                                    { name },
                                                )}
                                                className="h-9 w-20"
                                                onChange={(event) =>
                                                    update(index, {
                                                        min_progress:
                                                            event.target
                                                                .valueAsNumber,
                                                    })
                                                }
                                            />
                                            <span aria-hidden="true">
                                                {t(
                                                    'cms.dependencies.minProgressShort',
                                                )}
                                            </span>
                                        </label>
                                    )}
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label={t(
                                            'cms.dependencies.remove',
                                            { name },
                                        )}
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
                                <InputError message={error} />
                            </li>
                        );
                    })}
                </ul>
            )}

            {available.length > 0 && (
                <div className="flex flex-wrap items-center gap-2">
                    <label htmlFor={`${id}-add`} className="sr-only">
                        {t('cms.dependencies.addLabel')}
                    </label>
                    <select
                        id={`${id}-add`}
                        value={candidate}
                        className={cn(
                            selectClass,
                            'min-w-56 flex-1 sm:flex-none',
                        )}
                        onChange={(event) => setCandidate(event.target.value)}
                    >
                        <option value="">{t('cms.dependencies.choose')}</option>
                        {available.map((option) => (
                            <option key={option.id} value={option.id}>
                                {option.title}
                                {option.published
                                    ? ''
                                    : ` (${t('cms.dependencies.unpublished')})`}
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
                        {t('cms.dependencies.add')}
                    </Button>
                </div>
            )}
        </div>
    );
}
