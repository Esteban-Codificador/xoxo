import { BadgeCheck, ExternalLink } from 'lucide-react';
import { LinkStatusBadge } from '@/features/publishing/status-badges';
import { t } from '@/i18n';
import type { LinkStatus, ResourceType } from '@/types/enums';

export type ResourceLink = {
    title: string;
    url: string;
    type: ResourceType;
    provider: string;
    description: string;
    language: string;
    is_official: boolean;
    link_status: LinkStatus;
    note: string | null;
};

function languageName(code: string): string {
    return code === 'es' || code === 'en'
        ? t(`languages.${code}`)
        : code.toUpperCase();
}

export function ResourceList({ resources }: { resources: ResourceLink[] }) {
    return (
        <ul className="grid gap-3">
            {resources.map((resource) => (
                <li
                    key={resource.url}
                    className="rounded-xl border bg-card p-4"
                >
                    <a
                        href={resource.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="group inline-flex items-start gap-1.5 font-medium underline-offset-4 hover:underline"
                    >
                        {resource.title}
                        <ExternalLink
                            className="mt-1 size-3.5 shrink-0 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <span className="sr-only">
                            {' '}
                            {t('richContent.opensInNewTab')}
                        </span>
                    </a>
                    <p className="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground">
                        <span>{resource.provider}</span>
                        <span aria-hidden="true">·</span>
                        <span>{t(`resourceType.${resource.type}`)}</span>
                        <span aria-hidden="true">·</span>
                        <span>{languageName(resource.language)}</span>
                        {resource.is_official && (
                            <span className="inline-flex items-center gap-1 text-state-completed">
                                <BadgeCheck
                                    className="size-3.5"
                                    aria-hidden="true"
                                />
                                {t('lesson.official')}
                            </span>
                        )}
                    </p>
                    <p className="mt-2 text-sm text-muted-foreground">
                        {resource.note ?? resource.description}
                    </p>
                    {resource.link_status === 'BROKEN' && (
                        <p className="mt-2 flex items-center gap-2 text-xs text-destructive">
                            <LinkStatusBadge status="BROKEN" />
                            {t('lesson.brokenLink')}
                        </p>
                    )}
                </li>
            ))}
        </ul>
    );
}
