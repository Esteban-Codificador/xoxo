import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { Layers, Pencil, Plus } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { ContentStatusBadge } from '@/components/publishing/status-badges';
import { Button } from '@/components/ui/button';
import { t } from '@/i18n';
import { formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import { edit as editRoadmap } from '@/routes/admin/roadmaps';
import { create, edit, index } from '@/routes/admin/tracks';
import type { ContentStatus } from '@/types/enums';

type TrackRow = {
    id: number;
    title: string;
    status: ContentStatus;
    modules_count: number;
    lessons_count: number;
    prerequisites: string[];
    updated_at: string | null;
    can_edit: boolean;
};

type Props = {
    roadmaps: {
        id: number;
        slug: string;
        title: string;
        status: ContentStatus;
        can_edit: boolean;
        tracks: TrackRow[];
    }[];
    can: { create: boolean };
};

export default function AdminTracksIndex({ roadmaps, can }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.adminOverview'), href: dashboard() },
            { title: t('nav.tracks'), href: index() },
        ],
    });

    const empty = roadmaps.every((roadmap) => roadmap.tracks.length === 0);

    return (
        <>
            <Head title={t('cms.tracks.head')} />

            <div className="flex flex-1 flex-col gap-8 p-4 md:p-6">
                <PageHeader
                    title={t('cms.tracks.title')}
                    description={t('cms.tracks.description')}
                    actions={
                        // With several roadmaps, each section has its own button.
                        can.create &&
                        roadmaps.length === 1 && (
                            <Button asChild size="sm">
                                <Link
                                    href={create({
                                        query: { roadmap: roadmaps[0].slug },
                                    })}
                                >
                                    <Plus aria-hidden="true" />
                                    {t('cms.tracks.create')}
                                </Link>
                            </Button>
                        )
                    }
                />

                {empty ? (
                    <EmptyState
                        icon={Layers}
                        title={t('cms.tracks.empty')}
                        description={t('cms.lessons.emptyDescription')}
                    />
                ) : (
                    roadmaps.map((roadmap) => (
                        <section
                            key={roadmap.slug}
                            aria-label={roadmap.title}
                            className="space-y-3"
                        >
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <div className="flex items-center gap-2">
                                    <h2 className="font-medium">
                                        {roadmap.title}
                                    </h2>
                                    <ContentStatusBadge
                                        status={roadmap.status}
                                    />
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    {roadmap.can_edit && (
                                        <Button
                                            asChild
                                            size="sm"
                                            variant="outline"
                                        >
                                            <Link
                                                href={editRoadmap(roadmap.id)}
                                                aria-label={t(
                                                    'cms.tracks.editRoadmapLabel',
                                                    { roadmap: roadmap.title },
                                                )}
                                            >
                                                <Pencil aria-hidden="true" />
                                                {t('cms.tracks.editRoadmap')}
                                            </Link>
                                        </Button>
                                    )}
                                    {can.create && roadmaps.length > 1 && (
                                        <Button
                                            asChild
                                            size="sm"
                                            variant="outline"
                                        >
                                            <Link
                                                href={create({
                                                    query: {
                                                        roadmap: roadmap.slug,
                                                    },
                                                })}
                                            >
                                                <Plus aria-hidden="true" />
                                                {t('cms.tracks.create')}
                                            </Link>
                                        </Button>
                                    )}
                                </div>
                            </div>
                            <div className="overflow-x-auto rounded-xl border">
                                <table className="w-full min-w-[44rem] table-fixed text-sm">
                                    <thead className="bg-muted/50 text-left">
                                        <tr>
                                            <th
                                                scope="col"
                                                className="px-4 py-2 font-medium"
                                            >
                                                {t('cms.tracks.track')}
                                            </th>
                                            <th
                                                scope="col"
                                                className="w-32 px-4 py-2 font-medium"
                                            >
                                                {t('cms.lessons.status')}
                                            </th>
                                            <th
                                                scope="col"
                                                className="w-24 px-4 py-2 text-right font-medium"
                                            >
                                                {t('cms.tracks.modules')}
                                            </th>
                                            <th
                                                scope="col"
                                                className="w-24 px-4 py-2 text-right font-medium"
                                            >
                                                {t('cms.tracks.lessons')}
                                            </th>
                                            <th
                                                scope="col"
                                                className="w-44 px-4 py-2 font-medium"
                                            >
                                                {t('cms.lessons.updated')}
                                            </th>
                                            <th
                                                scope="col"
                                                className="w-28 px-4 py-2"
                                            >
                                                <span className="sr-only">
                                                    {t('cms.lessons.edit')}
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {roadmap.tracks.map((track) => (
                                            <tr
                                                key={track.id}
                                                className="border-t align-top"
                                            >
                                                <th
                                                    scope="row"
                                                    className="px-4 py-3 text-left font-normal"
                                                >
                                                    <span className="block font-medium">
                                                        {track.title}
                                                    </span>
                                                    <span className="text-xs text-muted-foreground">
                                                        {track.prerequisites
                                                            .length === 0
                                                            ? t(
                                                                  'cms.tracks.startingPoint',
                                                              )
                                                            : `${t('cms.tracks.requires')}: ${track.prerequisites.join(', ')}`}
                                                    </span>
                                                </th>
                                                <td className="px-4 py-3">
                                                    <ContentStatusBadge
                                                        status={track.status}
                                                    />
                                                </td>
                                                <td className="px-4 py-3 text-right text-muted-foreground tabular-nums">
                                                    {track.modules_count}
                                                </td>
                                                <td className="px-4 py-3 text-right text-muted-foreground tabular-nums">
                                                    {track.lessons_count}
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap text-muted-foreground">
                                                    {track.updated_at &&
                                                        formatDateTime(
                                                            track.updated_at,
                                                        )}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    {track.can_edit ? (
                                                        <Button
                                                            asChild
                                                            size="sm"
                                                            variant="outline"
                                                        >
                                                            <Link
                                                                href={edit(
                                                                    track.id,
                                                                )}
                                                                aria-label={t(
                                                                    'cms.tracks.editTrack',
                                                                    {
                                                                        track: track.title,
                                                                    },
                                                                )}
                                                            >
                                                                <Pencil aria-hidden="true" />
                                                                {t(
                                                                    'cms.lessons.edit',
                                                                )}
                                                            </Link>
                                                        </Button>
                                                    ) : (
                                                        <span className="text-xs text-muted-foreground">
                                                            {t(
                                                                'cms.lessons.readOnly',
                                                            )}
                                                        </span>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    ))
                )}
            </div>
        </>
    );
}
