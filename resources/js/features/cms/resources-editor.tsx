import { ExternalLink } from 'lucide-react';
import { LinkStatusBadge } from '@/components/publishing/status-badges';
import { t } from '@/i18n';
import type { LinkStatus, ResourceType } from '@/types/enums';
import { OrderedLinksEditor } from './ordered-links-editor';

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
    return (
        <OrderedLinksEditor
            options={options}
            value={value}
            onChange={onChange}
            errorFor={errorFor}
            emptyText={t('cms.relations.noResources')}
            addLabel={t('cms.relations.addResource')}
            optionLabel={(option) => `${option.title} (${option.provider})`}
            describe={(resource) => (
                <>
                    <span>
                        {resource.provider} ·{' '}
                        {t(`resourceType.${resource.type}`)}
                    </span>
                    {resource.is_official && (
                        <span>{t('cms.relations.official')}</span>
                    )}
                    {!resource.published && (
                        <span>({t('cms.dependencies.unpublished')})</span>
                    )}
                    <LinkStatusBadge status={resource.link_status} />
                    <a
                        href={resource.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex max-w-full items-center gap-1 truncate underline-offset-4 hover:underline"
                    >
                        <span className="truncate">{resource.url}</span>
                        <ExternalLink
                            className="size-3 shrink-0"
                            aria-hidden="true"
                        />
                        <span className="sr-only">
                            {t('richContent.opensInNewTab')}
                        </span>
                    </a>
                </>
            )}
        />
    );
}
