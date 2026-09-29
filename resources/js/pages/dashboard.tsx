import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { ArrowRight, BookOpen, CircleCheck, Clock } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { ProgressBar } from '@/features/progress/progress-bar';
import { StateBadge } from '@/features/progress/state-badge';
import type { TrackProgress } from '@/features/progress/types';
import { t } from '@/i18n';
import { dashboard } from '@/routes';
import { show as showLesson } from '@/routes/lessons';
import { show as showTrack } from '@/routes/tracks';
import type { Difficulty, NodeState } from '@/types/enums';

type RoadmapSummary = { slug: string; title: string; summary: string };

type TrackSummary = {
    slug: string;
    title: string;
    summary: string;
    position: number;
    difficulty: Difficulty;
    estimated_hours: number | null;
    lessons_count: number;
    progress: TrackProgress;
};

type Props = {
    roadmap: RoadmapSummary | null;
    tracks: TrackSummary[];
    continue: {
        slug: string;
        title: string;
        track: string;
        state: NodeState;
    } | null;
    all_done?: boolean;
};

export default function Dashboard({
    roadmap,
    tracks,
    continue: next,
    all_done: allDone = false,
}: Props) {
    setLayoutProps({
        breadcrumbs: [{ title: t('nav.dashboard'), href: dashboard() }],
    });

    const resuming = next?.state === 'IN_PROGRESS';

    return (
        <>
            <Head title={t('dashboard.head')} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('dashboard.title')}
                    description={
                        roadmap
                            ? t('dashboard.description', {
                                  roadmap: roadmap.title,
                              })
                            : undefined
                    }
                />

                {roadmap === null || tracks.length === 0 ? (
                    <EmptyState
                        icon={BookOpen}
                        title={t('dashboard.emptyTitle')}
                        description={t('dashboard.emptyDescription')}
                    />
                ) : (
                    <>
                        {next && (
                            <section
                                aria-labelledby="continue"
                                className="flex flex-col gap-4 rounded-xl border bg-card p-5 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div className="space-y-1">
                                    <h2
                                        id="continue"
                                        className="text-sm font-medium text-muted-foreground"
                                    >
                                        {resuming
                                            ? t('progress.continueTitle')
                                            : t('progress.startTitle')}
                                    </h2>
                                    <p className="text-lg font-medium">
                                        {next.title}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {next.track}
                                    </p>
                                </div>
                                <Button asChild className="shrink-0">
                                    <Link href={showLesson(next.slug)}>
                                        {resuming
                                            ? t('progress.continueAction')
                                            : t('progress.startAction')}
                                        <ArrowRight aria-hidden="true" />
                                    </Link>
                                </Button>
                            </section>
                        )}

                        {!next && allDone && (
                            <p className="flex items-center gap-2 rounded-xl border border-state-completed/40 bg-state-completed-soft p-4 font-medium text-state-completed">
                                <CircleCheck
                                    className="size-5"
                                    aria-hidden="true"
                                />
                                {t('progress.allDone')}
                            </p>
                        )}

                        <ol className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            {tracks.map((track) => (
                                <li
                                    key={track.slug}
                                    className="relative flex flex-col gap-3 rounded-xl border bg-card p-5 transition-colors hover:bg-muted/40"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="flex items-baseline gap-3">
                                            <span className="font-mono text-xs text-muted-foreground tabular-nums">
                                                {String(
                                                    track.position,
                                                ).padStart(2, '0')}
                                            </span>
                                            <h2 className="font-medium">
                                                {/* The whole card is the link; the title names it. */}
                                                <Link
                                                    href={showTrack({
                                                        roadmap: roadmap.slug,
                                                        track: track.slug,
                                                    })}
                                                    className="after:absolute after:inset-0 after:rounded-[inherit] focus-visible:outline-none focus-visible:after:ring-2 focus-visible:after:ring-ring"
                                                >
                                                    {track.title}
                                                </Link>
                                            </h2>
                                        </div>
                                        <StateBadge
                                            state={track.progress.state}
                                            className="shrink-0"
                                        />
                                    </div>
                                    <p className="flex-1 text-sm text-muted-foreground">
                                        {track.summary}
                                    </p>
                                    <div className="space-y-1.5">
                                        <ProgressBar
                                            value={track.progress.progress}
                                            state={
                                                track.progress.state ===
                                                'COMPLETED'
                                                    ? 'COMPLETED'
                                                    : 'IN_PROGRESS'
                                            }
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            {t('progress.lessonsDone', {
                                                completed:
                                                    track.progress.completed,
                                                total: track.progress.total,
                                            })}
                                        </p>
                                    </div>
                                    <ul className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                        <li>
                                            {t(
                                                `difficulty.${track.difficulty}`,
                                            )}
                                        </li>
                                        <li className="flex items-center gap-1">
                                            <BookOpen
                                                className="size-3.5"
                                                aria-hidden="true"
                                            />
                                            {track.lessons_count === 1
                                                ? t('dashboard.oneLesson')
                                                : t('dashboard.lessons', {
                                                      count: track.lessons_count,
                                                  })}
                                        </li>
                                        {track.estimated_hours !== null && (
                                            <li className="flex items-center gap-1">
                                                <Clock
                                                    className="size-3.5"
                                                    aria-hidden="true"
                                                />
                                                {t('common.hours', {
                                                    count: track.estimated_hours,
                                                })}
                                            </li>
                                        )}
                                    </ul>
                                </li>
                            ))}
                        </ol>
                    </>
                )}
            </div>
        </>
    );
}
