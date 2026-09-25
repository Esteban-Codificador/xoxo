import { Head, setLayoutProps } from '@inertiajs/react';
import { History } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import {
    ContentStatusBadge,
    LinkStatusBadge,
} from '@/features/publishing/status-badges';
import type { ActivityEntry } from '@/features/admin/activity';
import { activitySentence } from '@/features/admin/activity';
import { t } from '@/i18n';
import { formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import type { ContentStatus, LinkStatus } from '@/types/enums';

type Entity = 'roadmap' | 'track' | 'module' | 'lesson' | 'skill' | 'resource';

type Props = {
    content: {
        entity: Entity;
        counts: Record<ContentStatus, number>;
        total: number;
    }[];
    links: Record<LinkStatus, number>;
    activity: ActivityEntry[];
};

const statuses: ContentStatus[] = ['DRAFT', 'REVIEW', 'PUBLISHED', 'ARCHIVED'];
const linkStatuses: LinkStatus[] = ['UNCHECKED', 'OK', 'REDIRECTED', 'BROKEN'];

export default function AdminDashboard({ content, links, activity }: Props) {
    setLayoutProps({
        breadcrumbs: [{ title: t('nav.adminOverview'), href: dashboard() }],
    });

    return (
        <>
            <Head title={t('admin.head')} />

            <div className="flex flex-1 flex-col gap-8 p-4 md:p-6">
                <PageHeader
                    title={t('admin.title')}
                    description={t('admin.description')}
                />

                <section
                    aria-labelledby="content-by-status"
                    className="space-y-3"
                >
                    <h2 id="content-by-status" className="font-medium">
                        {t('admin.contentTitle')}
                    </h2>
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th
                                        scope="col"
                                        className="px-4 py-2 font-medium"
                                    >
                                        {t('admin.entity')}
                                    </th>
                                    {statuses.map((status) => (
                                        <th
                                            key={status}
                                            scope="col"
                                            className="px-4 py-2 text-right"
                                        >
                                            <ContentStatusBadge
                                                status={status}
                                            />
                                        </th>
                                    ))}
                                    <th
                                        scope="col"
                                        className="px-4 py-2 text-right font-medium"
                                    >
                                        {t('admin.total')}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {content.map((row) => (
                                    <tr key={row.entity} className="border-t">
                                        <th
                                            scope="row"
                                            className="px-4 py-2 text-left font-normal"
                                        >
                                            {t(`admin.entities.${row.entity}`)}
                                        </th>
                                        {statuses.map((status) => (
                                            <td
                                                key={status}
                                                className="px-4 py-2 text-right text-muted-foreground tabular-nums"
                                            >
                                                {row.counts[status]}
                                            </td>
                                        ))}
                                        <td className="px-4 py-2 text-right font-medium tabular-nums">
                                            {row.total}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section aria-labelledby="link-status" className="space-y-3">
                    <div className="space-y-1">
                        <h2 id="link-status" className="font-medium">
                            {t('admin.linksTitle')}
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            {t('admin.linksDescription')}
                        </p>
                    </div>
                    <ul className="flex flex-wrap gap-3">
                        {linkStatuses.map((status) => (
                            <li
                                key={status}
                                className="flex items-center gap-2 rounded-lg border px-3 py-2"
                            >
                                <LinkStatusBadge status={status} />
                                <span className="font-medium tabular-nums">
                                    {links[status]}
                                </span>
                            </li>
                        ))}
                    </ul>
                </section>

                <section
                    aria-labelledby="recent-activity"
                    className="space-y-3"
                >
                    <h2 id="recent-activity" className="font-medium">
                        {t('admin.activityTitle')}
                    </h2>
                    {activity.length === 0 ? (
                        <EmptyState
                            icon={History}
                            title={t('admin.activityEmpty')}
                        />
                    ) : (
                        <ol className="divide-y rounded-xl border">
                            {activity.map((entry) => (
                                <li
                                    key={entry.id}
                                    className="flex flex-col gap-1 px-4 py-3 text-sm sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <p>{activitySentence(entry)}</p>
                                    <time
                                        dateTime={entry.created_at}
                                        className="shrink-0 text-xs text-muted-foreground"
                                    >
                                        {formatDateTime(entry.created_at)}
                                    </time>
                                </li>
                            ))}
                        </ol>
                    )}
                </section>
            </div>
        </>
    );
}
