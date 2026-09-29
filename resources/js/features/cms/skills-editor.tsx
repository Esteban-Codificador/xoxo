import { Plus, X } from 'lucide-react';
import { useId, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { t } from '@/i18n';
import { cn } from '@/lib/utils';

export type SkillRow = { id: number; weight: number };

export type SkillOption = { id: number; title: string; published: boolean };

const selectClass =
    'h-9 rounded-md border border-input bg-transparent px-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

const weights = [1, 2, 3, 4, 5];

/** Skills a lesson develops, each with a weight from 1 to 5. */
export function SkillsEditor({
    options,
    value,
    onChange,
    errorFor,
}: {
    options: SkillOption[];
    value: SkillRow[];
    onChange: (value: SkillRow[]) => void;
    errorFor?: (index: number) => string | undefined;
}) {
    const id = useId();
    const [candidate, setCandidate] = useState('');
    const byId = new Map(options.map((option) => [option.id, option]));
    const available = options.filter(
        (option) => !value.some((row) => row.id === option.id),
    );

    const add = () => {
        const option = available.find((item) => String(item.id) === candidate);

        if (option !== undefined) {
            onChange([...value, { id: option.id, weight: 3 }]);
            setCandidate('');
        }
    };

    return (
        <div className="space-y-3">
            {value.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    {t('cms.relations.noSkills')}
                </p>
            ) : (
                <ul className="divide-y rounded-lg border">
                    {value.map((row, index) => {
                        const name = byId.get(row.id)?.title ?? `#${row.id}`;

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
                                        aria-label={t('cms.relations.weight', {
                                            name,
                                        })}
                                        value={row.weight}
                                        className={selectClass}
                                        onChange={(event) =>
                                            onChange(
                                                value.map((item, i) =>
                                                    i === index
                                                        ? {
                                                              ...item,
                                                              weight: Number(
                                                                  event.target
                                                                      .value,
                                                              ),
                                                          }
                                                        : item,
                                                ),
                                            )
                                        }
                                    >
                                        {weights.map((weight) => (
                                            <option key={weight} value={weight}>
                                                {weight}
                                            </option>
                                        ))}
                                    </select>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
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
                </ul>
            )}

            {available.length > 0 && (
                <div className="flex flex-wrap items-center gap-2">
                    <label htmlFor={`${id}-add`} className="sr-only">
                        {t('cms.relations.addSkill')}
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
                        <option value="">{t('cms.relations.choose')}</option>
                        {available.map((option) => (
                            <option key={option.id} value={option.id}>
                                {option.title}
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
                        {t('cms.relations.addSkill')}
                    </Button>
                </div>
            )}
        </div>
    );
}
