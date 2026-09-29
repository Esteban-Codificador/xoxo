import { ArrowDown, ArrowUp, ExternalLink, Plus, X } from 'lucide-react';
import { useId, useState } from 'react';
import InputError from '@/components/input-error';
import { LinkStatusBadge } from '@/components/publishing/status-badges';
import { Button } from '@/components/ui/button';
import { t } from '@/i18n';
import { cn } from '@/lib/utils';
import type { LinkStatus, ResourceType } from '@/types/enums';

export type ResourceOption = {
    id: number;
    title: string;
    provider: string;
    type: ResourceType;
    url: string;
    is_official: boolean;
    link_status: LinkStatus;
    published: boolean;
};

const selectClass =
    'h-9 rounded-md border border-input bg-transparent px-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

/**
 * Resources of a lesson in display order. Resources are created in their
 * own section (a URL is verified by content:verify-links, never typed here).
 */
export function ResourcesEditor({
    options,
    value,
    onChange,
    errorFor,
}: {
    options: ResourceOption[];
    value: number[];
    onChange: (value: number[]) => void;
    errorFor?: (index: number) => string | undefined;
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
                <p className="text-sm text-muted-foreground">
                    {t('cms.relations.noResources')}
                </p>
            ) : (
                <ol className="divide-y rounded-lg border">
                    {value.map((resourceId, index) => {
                        const resource = byId.get(resourceId);
                        const name = resource?.title ?? `#${resourceId}`;

                        return (
                            <li key={resourceId} className="space-y-1 p-3">
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
                                        {resource !== undefined && (
                                            <p className="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground">
                                                <span>
                                                    {resource.provider} ·{' '}
                                                    {t(
                                                        `resourceType.${resource.type}`,
                                                    )}
                                                </span>
                                                {resource.is_official && (
                                                    <span>
                                                        {t(
                                                            'cms.relations.official',
                                                        )}
                                                    </span>
                                                )}
                                                {!resource.published && (
                                                    <span>
                                                        (
                                                        {t(
                                                            'cms.dependencies.unpublished',
                                                        )}
                                                        )
                                                    </span>
                                                )}
                                                <LinkStatusBadge
                                                    status={
                                                        resource.link_status
                                                    }
                                                />
                                                <a
                                                    href={resource.url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex max-w-full items-center gap-1 truncate underline-offset-4 hover:underline"
                                                >
                                                    <span className="truncate">
                                                        {resource.url}
                                                    </span>
                                                    <ExternalLink
                                                        className="size-3 shrink-0"
                                                        aria-hidden="true"
                                                    />
                                                    <span className="sr-only">
                                                        {t(
                                                            'richContent.opensInNewTab',
                                                        )}
                                                    </span>
                                                </a>
                                            </p>
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
                        {t('cms.relations.addResource')}
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
                                {option.title} ({option.provider})
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
                        {t('cms.relations.addResource')}
                    </Button>
                </div>
            )}
        </div>
    );
}
