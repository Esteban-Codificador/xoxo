import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { ExternalLink, Pencil, Plus, SquarePlay } from 'lucide-react';
import { useId } from 'react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import {
    ContentStatusBadge,
    VideoAvailabilityBadge,
} from '@/components/publishing/status-badges';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { t } from '@/i18n';
import { formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import { create, edit, index } from '@/routes/admin/videos';
import type { ContentStatus, LinkStatus } from '@/types/enums';

type VideoRow = {
    id: number;
    title: string;
    instructor: string | null;
    external_id: string;
    url: string;
    duration: string | null;
    thumbnail_url: string | null;
    link_status: LinkStatus;
    last_checked_at: string | null;
    status: ContentStatus;
    lessons_count: number;
    can_edit: boolean;
};

type Props = {
    videos: VideoRow[];
    filters: { q: string; link: LinkStatus | null };
    can: { create: boolean };
};

// REDIRECTED never happens to a video: oEmbed answers or it does not.
const availabilities: LinkStatus[] = ['UNCHECKED', 'OK', 'BROKEN'];

const selectClass =
    'h-9 rounded-md border border-input bg-transparent px-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

export default function AdminVideosIndex({ videos, filters, can }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.adminOverview'), href: dashboard() },
            { title: t('nav.videos'), href: index() },
        ],
    });

    const id = useId();

    return (
        <>
            <Head title={t('cms.videos.head')} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('cms.videos.title')}
                    description={t('cms.videos.description')}
                    actions={
                        can.create && (
                            <Button asChild size="sm">
                                <Link href={create()}>
                                    <Plus aria-hidden="true" />
                                    {t('cms.videos.create')}
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
                            {t('cms.videos.searchLabel')}
                        </label>
                        <Input
                            id={`${id}-q`}
                            name="q"
                            type="search"
                            defaultValue={filters.q}
                            placeholder={t('cms.videos.searchPlaceholder')}
                            className="w-64"
                        />
                    </div>
                    <div className="grid gap-1">
                        <label
                            htmlFor={`${id}-link`}
                            className="text-xs text-muted-foreground"
                        >
                            {t('cms.videos.availabilityFilter')}
                        </label>
                        <select
                            id={`${id}-link`}
                            name="link"
                            defaultValue={filters.link ?? ''}
                            className={selectClass}
                        >
                            <option value="">{t('cms.videos.all')}</option>
                            {availabilities.map((status) => (
                                <option key={status} value={status}>
                                    {t(`videoAvailability.${status}`)}
                                </option>
                            ))}
                        </select>
                    </div>
                    <Button type="submit" variant="outline">
                        {t('cms.videos.search')}
                    </Button>
                </Form>

                {videos.length === 0 ? (
                    <EmptyState
                        icon={SquarePlay}
                        title={t('cms.videos.empty')}
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
                                        {t('cms.videos.video')}
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
                                        {t('cms.videos.availability')}
                                    </th>
                                    <th
                                        scope="col"
                                        className="w-24 px-4 py-2 text-right font-medium"
                                    >
                                        {t('cms.videos.usedIn')}
                                    </th>
                                    <th scope="col" className="w-28 px-4 py-2">
                                        <span className="sr-only">
                                            {t('cms.lessons.edit')}
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {videos.map((video) => (
                                    <tr
                                        key={video.id}
                                        className="border-t align-top"
                                    >
                                        <th
                                            scope="row"
                                            className="px-4 py-3 text-left font-normal"
                                        >
                                            <div className="flex gap-3">
                                                {video.thumbnail_url !==
                                                    null && (
                                                    <img
                                                        src={
                                                            video.thumbnail_url
                                                        }
                                                        alt=""
                                                        width={96}
                                                        height={54}
                                                        loading="lazy"
                                                        className="h-[54px] w-24 shrink-0 rounded-md border object-cover"
                                                    />
                                                )}
                                                <div className="min-w-0">
                                                    <span className="block font-medium">
                                                        {video.title}
                                                    </span>
                                                    <span className="block text-xs text-muted-foreground">
                                                        {[
                                                            video.instructor,
                                                            video.duration,
                                                        ]
                                                            .filter(Boolean)
                                                            .join(' · ')}
                                                    </span>
                                                    <a
                                                        href={video.url}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="inline-flex items-center gap-1 font-mono text-xs text-muted-foreground underline-offset-4 hover:underline"
                                                    >
                                                        {video.external_id}
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
                                                </div>
                                            </div>
                                        </th>
                                        <td className="px-4 py-3">
                                            <ContentStatusBadge
                                                status={video.status}
                                            />
                                        </td>
                                        <td className="space-y-1 px-4 py-3">
                                            <VideoAvailabilityBadge
                                                status={video.link_status}
                                            />
                                            <span className="block text-xs text-muted-foreground">
                                                {video.last_checked_at === null
                                                    ? t('cms.videos.never')
                                                    : formatDateTime(
                                                          video.last_checked_at,
                                                      )}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-right text-muted-foreground tabular-nums">
                                            {video.lessons_count}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {video.can_edit ? (
                                                <Button
                                                    asChild
                                                    size="sm"
                                                    variant="outline"
                                                >
                                                    <Link
                                                        href={edit(video.id)}
                                                        aria-label={t(
                                                            'cms.videos.edit',
                                                            {
                                                                video: video.title,
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
