import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { ArrowRight, BookOpen, Clock, Signal } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import { BlockerNotice } from '@/features/progress/blocker-notice';
import { ProgressBar } from '@/features/progress/progress-bar';
import { StateBadge } from '@/features/progress/state-badge';
import type { TrackProgress } from '@/features/progress/types';
import { RichContentRenderer } from '@/features/rich-content';
import type { MediaMap, RichContent } from '@/features/rich-content';
import { t } from '@/i18n';
import { formatMinutes } from '@/lib/format';
import { dashboard } from '@/routes';
import { show as showLesson } from '@/routes/lessons';
import { show as showTrack } from '@/routes/tracks';
import type {
    ContentType,
    DependencyKind,
    Difficulty,
    NodeState,
    UnlockPolicy,
} from '@/types/enums';

type LessonItem = {
    slug: string;
    title: string;
    summary: string;
    content_type: ContentType;
    difficulty: Difficulty;
    estimated_minutes: number;
    state: NodeState;
};

type Props = {
    /** Images of the content, by media id (MediaSources). */
    media: MediaMap;
    roadmap: { slug: string; title: string };
    track: {
        slug: string;
        title: string;
        summary: string;
        why_it_matters: string;
        description: RichContent | null;
        position: number;
        difficulty: Difficulty;
        estimated_hours: number | null;
        lessons_count: number;
        total_minutes: number;
    };
    progress: TrackProgress;
    policy: UnlockPolicy;
    continue: { slug: string; title: string } | null;
    prerequisites: { slug: string; title: string; kind: DependencyKind }[];
    modules: {
        slug: string;
        title: string;
        summary: string;
        lessons: LessonItem[];
    }[];
};

const pad = (value: number) => String(value).padStart(2, '0');

export default function TrackShow({
    roadmap,
    track,
    media,
    progress,
    policy,
    continue: next,
    prerequisites,
    modules,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.dashboard'), href: dashboard() },
            {
                title: track.title,
                href: showTrack({ roadmap: roadmap.slug, track: track.slug }),
            },
        ],
    });

    const started = progress.completed > 0 || progress.state === 'IN_PROGRESS';
    // Lessons are numbered across the whole track, in study order.
    const offsets = modules.map((_, index) =>
        modules
            .slice(0, index)
            .reduce((total, module) => total + module.lessons.length, 0),
    );

    return (
        <>
            <Head title={track.title} />

            <div className="mx-auto flex w-full max-w-4xl flex-col gap-10 px-4 py-8 md:px-6">
                <header className="space-y-4">
                    <div className="flex items-center gap-3">
                        <p className="font-mono text-xs text-muted-foreground">
                            {t('track.eyebrow', {
                                position: pad(track.position),
                            })}
                        </p>
                        <StateBadge state={progress.state} />
                    </div>
                    <h1 className="text-3xl font-semibold tracking-tight text-balance">
                        {track.title}
                    </h1>
                    <p className="max-w-2xl text-lg text-muted-foreground">
                        {track.summary}
                    </p>
                    <ul className="flex flex-wrap gap-x-5 gap-y-2 text-sm text-muted-foreground">
                        <li className="flex items-center gap-1.5">
                            <Signal className="size-4" aria-hidden="true" />
                            {t(`difficulty.${track.difficulty}`)}
                        </li>
                        <li className="flex items-center gap-1.5">
                            <BookOpen className="size-4" aria-hidden="true" />
                            {track.lessons_count === 1
                                ? t('dashboard.oneLesson')
                                : t('dashboard.lessons', {
                                      count: track.lessons_count,
                                  })}
                        </li>
                        {track.total_minutes > 0 && (
                            <li className="flex items-center gap-1.5">
                                <Clock className="size-4" aria-hidden="true" />
                                {t('track.readingTime', {
                                    time: formatMinutes(track.total_minutes),
                                })}
                            </li>
                        )}
                    </ul>
                    {track.estimated_hours !== null && (
                        <p className="text-sm text-muted-foreground">
                            {t('track.dedication', {
                                hours: track.estimated_hours,
                            })}
                        </p>
                    )}
                    {progress.total > 0 && (
                        <div className="max-w-md space-y-1.5">
                            <ProgressBar
                                value={progress.progress}
                                state={
                                    progress.state === 'COMPLETED'
                                        ? 'COMPLETED'
                                        : 'IN_PROGRESS'
                                }
                                showValue
                            />
                            <p className="text-xs text-muted-foreground">
                                {t('progress.lessonsDone', {
                                    completed: progress.completed,
                                    total: progress.total,
                                })}
                            </p>
                        </div>
                    )}
                    {next && (
                        // Lesson titles can be long: let the label wrap on phones.
                        <Button
                            asChild
                            className="h-auto max-w-full py-2 text-left whitespace-normal"
                        >
                            <Link href={showLesson(next.slug)}>
                                {started
                                    ? t('progress.trackContinue', {
                                          lesson: next.title,
                                      })
                                    : t('track.start', { lesson: next.title })}
                                <ArrowRight aria-hidden="true" />
                            </Link>
                        </Button>
                    )}
                </header>

                <BlockerNotice
                    blockers={progress.blockers}
                    roadmapSlug={roadmap.slug}
                    strict={policy === 'STRICT'}
                    scope="track"
                />

                <section
                    aria-labelledby="why"
                    className="rounded-xl border bg-muted/40 p-5"
                >
                    <h2 id="why" className="mb-2 font-medium">
                        {t('track.whyItMatters')}
                    </h2>
                    <p className="text-muted-foreground">
                        {track.why_it_matters}
                    </p>
                </section>

                {track.description && (
                    <section aria-labelledby="about">
                        <h2 id="about" className="mb-3 text-lg font-semibold">
                            {t('track.about')}
                        </h2>
                        <RichContentRenderer
                            content={track.description}
                            media={media}
                        />
                    </section>
                )}

                <section aria-labelledby="prerequisites" className="space-y-3">
                    <h2 id="prerequisites" className="text-lg font-semibold">
                        {t('track.prerequisites')}
                    </h2>
                    {prerequisites.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            {t('track.startingPoint')}
                        </p>
                    ) : (
                        <ul className="flex flex-wrap gap-2">
                            {prerequisites.map((prerequisite) => (
                                <li key={prerequisite.slug}>
                                    <Link
                                        href={showTrack({
                                            roadmap: roadmap.slug,
                                            track: prerequisite.slug,
                                        })}
                                        className="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm hover:bg-muted/50"
                                    >
                                        {prerequisite.title}
                                        <span className="text-xs text-muted-foreground">
                                            {t(
                                                `dependencyKind.${prerequisite.kind}`,
                                            )}
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section aria-labelledby="outline" className="space-y-6">
                    <h2 id="outline" className="text-lg font-semibold">
                        {t('track.outline')}
                    </h2>

                    {modules.length === 0 ? (
                        <EmptyState
                            icon={BookOpen}
                            title={t('track.emptyTitle')}
                            description={t('track.emptyDescription')}
                        />
                    ) : (
                        modules.map((module, moduleIndex) => (
                            <div
                                key={module.slug}
                                id={module.slug}
                                className="scroll-mt-20 space-y-3"
                            >
                                <div>
                                    <h3 className="font-medium">
                                        {module.title}
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        {module.summary}
                                    </p>
                                </div>
                                <ol className="divide-y rounded-xl border">
                                    {module.lessons.map(
                                        (lesson, lessonIndex) => (
                                            <li
                                                key={lesson.slug}
                                                className="relative flex gap-4 p-4 hover:bg-muted/40"
                                            >
                                                <span className="pt-0.5 font-mono text-xs text-muted-foreground tabular-nums">
                                                    {pad(
                                                        offsets[moduleIndex] +
                                                            lessonIndex +
                                                            1,
                                                    )}
                                                </span>
                                                <div className="min-w-0 flex-1 space-y-1">
                                                    <Link
                                                        href={showLesson(
                                                            lesson.slug,
                                                        )}
                                                        className="font-medium after:absolute after:inset-0 after:rounded-[inherit] focus-visible:outline-none focus-visible:after:ring-2 focus-visible:after:ring-ring"
                                                    >
                                                        {lesson.title}
                                                    </Link>
                                                    <p className="line-clamp-2 text-sm text-muted-foreground">
                                                        {lesson.summary}
                                                    </p>
                                                    <p className="flex flex-wrap gap-x-3 text-xs text-muted-foreground">
                                                        <span>
                                                            {t(
                                                                `contentType.${lesson.content_type}`,
                                                            )}
                                                        </span>
                                                        <span>
                                                            {formatMinutes(
                                                                lesson.estimated_minutes,
                                                            )}
                                                        </span>
                                                    </p>
                                                </div>
                                                <StateBadge
                                                    state={lesson.state}
                                                    className="self-start"
                                                />
                                            </li>
                                        ),
                                    )}
                                </ol>
                            </div>
                        ))
                    )}
                </section>
            </div>
        </>
    );
}
