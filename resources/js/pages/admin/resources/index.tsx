import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { ExternalLink, Library, Pencil, Plus } from 'lucide-react';
import { useId } from 'react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import {
    ContentStatusBadge,
    LinkStatusBadge,
} from '@/components/publishing/status-badges';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { t } from '@/i18n';
import { formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import { create, edit, index } from '@/routes/admin/resources';
import type { ContentStatus, LinkStatus, ResourceType } from '@/types/enums';

type ResourceRow = {
    id: number;
    title: string;
    provider: string;
    type: ResourceType;
    url: string;
    is_official: boolean;
    link_status: LinkStatus;
    last_checked_at: string | null;
    status: ContentStatus;
    lessons_count: number;
    can_edit: boolean;
};

type Props = {
    resources: ResourceRow[];
    filters: { q: string; link: LinkStatus | null };
    can: { create: boolean };
};

const linkStatuses: LinkStatus[] = ['UNCHECKED', 'OK', 'REDIRECTED', 'BROKEN'];

const selectClass =
    'h-9 rounded-md border border-input bg-transparent px-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

export default function AdminResourcesIndex({
    resources,
    filters,
    can,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.adminOverview'), href: dashboard() },
            { title: t('nav.resources'), href: index() },
        ],
    });

    const id = useId();

    return (
        <>
            <Head title={t('cms.resources.head')} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('cms.resources.title')}
                    description={t('cms.resources.description')}
                    actions={
                        can.create && (
                            <Button asChild size="sm">
                                <Link href={create()}>
                                    <Plus aria-hidden="true" />
                                    {t('cms.resources.create')}
                                </Link>
                            </Button>
                        )
                    }
                />

                {/* A GET form: the filters live in the URL and survive a reload. */}
                <Form
                    {...index.form()}
                    options={{ preserveScroll: true }}
                    className="flex flex-wrap items-end gap-3"
                >
                    <div className="grid gap-1">
                        <label
                            htmlFor={`${id}-q`}
                            className="text-xs text-muted-foreground"
                        >
                            {t('cms.resources.searchLabel')}
                        </label>
                        <Input
                            id={`${id}-q`}
                            name="q"
                            type="search"
                            defaultValue={filters.q}
                            placeholder={t('cms.resources.searchPlaceholder')}
                            className="w-64"
                        />
                    </div>
                    <div className="grid gap-1">
                        <label
                            htmlFor={`${id}-link`}
                            className="text-xs text-muted-foreground"
                        >
                            {t('cms.resources.linkFilter')}
                        </label>
                        <select
                            id={`${id}-link`}
                            name="link"
                            defaultValue={filters.link ?? ''}
                            className={selectClass}
                        >
                            <option value="">
                                {t('cms.resources.allLinks')}
                            </option>
                            {linkStatuses.map((status) => (
                                <option key={status} value={status}>
                                    {t(`linkStatus.${status}`)}
                                </option>
                            ))}
                        </select>
                    </div>
                    <Button type="submit" variant="outline">
                        {t('cms.resources.search')}
                    </Button>
                </Form>

                {resources.length === 0 ? (
                    <EmptyState
                        icon={Library}
                        title={t('cms.resources.empty')}
                    />
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full min-w-[52rem] table-fixed text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th
                                        scope="col"
                                        className="px-4 py-2 font-medium"
                                    >
                                        {t('cms.resources.resource')}
                                    </th>
                                    <th
                                        scope="col"
                                        className="w-32 px-4 py-2 font-medium"
                                    >
                                        {t('cms.lessons.status')}
                                    </th>
                                    <th
                                        scope="col"
                                        className="w-44 px-4 py-2 font-medium"
                                    >
                                        {t('cms.resources.link')}
                                    </th>
                                    <th
                                        scope="col"
                                        className="w-24 px-4 py-2 text-right font-medium"
                                    >
                                        {t('cms.resources.usedIn')}
                                    </th>
                                    <th scope="col" className="w-28 px-4 py-2">
                                        <span className="sr-only">
                                            {t('cms.lessons.edit')}
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {resources.map((resource) => (
                                    <tr
                                        key={resource.id}
                                        className="border-t align-top"
                                    >
                                        <th
                                            scope="row"
                                            className="px-4 py-3 text-left font-normal"
                                        >
                                            <span className="block font-medium">
                                                {resource.title}
                                            </span>
                                            <span className="block text-xs text-muted-foreground">
                                                {resource.provider} ·{' '}
                                                {t(
                                                    `resourceType.${resource.type}`,
                                                )}
                                                {resource.is_official &&
                                                    ` · ${t('cms.relations.official')}`}
                                            </span>
                                            <a
                                                href={resource.url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="inline-flex max-w-full items-center gap-1 text-xs text-muted-foreground underline-offset-4 hover:underline"
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
                                        </th>
                                        <td className="px-4 py-3">
                                            <ContentStatusBadge
                                                status={resource.status}
                                            />
                                        </td>
                                        <td className="space-y-1 px-4 py-3">
                                            <LinkStatusBadge
                                                status={resource.link_status}
                                            />
                                            <span className="block text-xs text-muted-foreground">
                                                {resource.last_checked_at ===
                                                null
                                                    ? t('cms.resources.never')
                                                    : formatDateTime(
                                                          resource.last_checked_at,
                                                      )}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-right text-muted-foreground tabular-nums">
                                            {resource.lessons_count}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {resource.can_edit ? (
                                                <Button
                                                    asChild
                                                    size="sm"
                                                    variant="outline"
                                                >
                                                    <Link
                                                        href={edit(resource.id)}
                                                        aria-label={t(
                                                            'cms.resources.edit',
                                                            {
                                                                resource:
                                                                    resource.title,
                                                            },
                                                        )}
                                                    >
                                                        <Pencil aria-hidden="true" />
                                                        {t('cms.lessons.edit')}
                                                    </Link>
                                                </Button>
                                            ) : (
                                                <span className="text-xs text-muted-foreground">
                                                    {t('cms.lessons.readOnly')}
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}
