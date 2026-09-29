import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { BlockerNotice } from '@/features/progress/blocker-notice';
import { ProgressBar } from '@/features/progress/progress-bar';
import { StateBadge } from '@/features/progress/state-badge';
import { t } from '@/i18n';
import { show as showLesson } from '@/routes/lessons';
import { show as showTrack } from '@/routes/tracks';
import type { UnlockPolicy } from '@/types/enums';
import type { RoadmapEdge, RoadmapTrack } from './types';

type Props = {
    roadmapSlug: string;
    policy: UnlockPolicy;
    track: RoadmapTrack | null;
    tracks: RoadmapTrack[];
    edges: RoadmapEdge[];
    onClose: () => void;
};

/** What a selected node means: progress, what it needs, what it unlocks, its lessons. */
export function NodeDetailPanel({
    roadmapSlug,
    policy,
    track,
    tracks,
    edges,
    onClose,
}: Props) {
    const titleOf = (slug: string) =>
        tracks.find((candidate) => candidate.slug === slug)?.title ?? slug;
    const requires = track
        ? edges.filter((edge) => edge.to === track.slug)
        : [];
    const unlocks = track
        ? edges.filter((edge) => edge.from === track.slug)
        : [];
    const modules = track
        ? track.lessons.reduce<
              {
                  slug: string;
                  title: string;
                  lessons: RoadmapTrack['lessons'];
              }[]
          >((groups, lesson) => {
              const group = groups.find(
                  (candidate) => candidate.slug === lesson.module_slug,
              );

              if (group) {
                  group.lessons.push(lesson);
              } else {
                  groups.push({
                      slug: lesson.module_slug,
                      title: lesson.module_title,
                      lessons: [lesson],
                  });
              }

              return groups;
          }, [])
        : [];

    return (
        <Sheet
            open={track !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <SheetContent
                side="right"
                className="w-full gap-0 overflow-y-auto sm:max-w-md"
            >
                {track && (
                    <>
                        <SheetHeader className="gap-2 border-b pr-12">
                            <StateBadge
                                state={track.progress.state}
                                className="self-start"
                            />
                            <SheetTitle className="text-lg">
                                {track.title}
                            </SheetTitle>
                            <SheetDescription>{track.summary}</SheetDescription>
                        </SheetHeader>

                        <div className="space-y-6 p-4">
                            <div className="space-y-1.5">
                                <ProgressBar
                                    value={track.progress.progress}
                                    state={
                                        track.progress.state === 'COMPLETED'
                                            ? 'COMPLETED'
                                            : 'IN_PROGRESS'
                                    }
                                    showValue
                                />
                                <p className="text-xs text-muted-foreground">
                                    {t('progress.lessonsDone', {
                                        completed: track.progress.completed,
                                        total: track.progress.total,
                                    })}
                                </p>
                            </div>

                            <BlockerNotice
                                blockers={track.progress.blockers}
                                roadmapSlug={roadmapSlug}
                                strict={policy === 'STRICT'}
                                scope="track"
                            />

                            <section className="space-y-2">
                                <h3 className="text-sm font-medium">
                                    {t('roadmap.requires')}
                                </h3>
                                {requires.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        {t('roadmap.noRequirements')}
                                    </p>
                                ) : (
                                    <ul className="space-y-1 text-sm">
                                        {requires.map((edge) => (
                                            <li
                                                key={edge.from}
                                                className="flex flex-wrap items-center gap-x-2"
                                            >
                                                <span>
                                                    {t('roadmap.minProgress', {
                                                        track: titleOf(
                                                            edge.from,
                                                        ),
                                                        value: edge.min_progress,
                                                    })}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {t(
                                                        `dependencyKind.${edge.kind}`,
                                                    )}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </section>

                            {unlocks.length > 0 && (
                                <section className="space-y-2">
                                    <h3 className="text-sm font-medium">
                                        {t('roadmap.unlocks')}
                                    </h3>
                                    <ul className="space-y-1 text-sm">
                                        {unlocks.map((edge) => (
                                            <li key={edge.to}>
                                                {titleOf(edge.to)}
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            )}

                            {modules.map((module) => (
                                <section
                                    key={module.slug}
                                    className="space-y-2"
                                >
                                    <h3 className="text-sm font-medium">
                                        {module.title}
                                    </h3>
                                    <ol className="divide-y rounded-lg border">
                                        {module.lessons.map((lesson) => (
                                            <li
                                                key={lesson.slug}
                                                className="flex items-center justify-between gap-3 p-3"
                                            >
                                                <Link
                                                    href={showLesson(
                                                        lesson.slug,
                                                    )}
                                                    className="text-sm underline-offset-4 hover:underline"
                                                >
                                                    {lesson.title}
                                                </Link>
                                                <StateBadge
                                                    state={lesson.state}
                                                    className="shrink-0"
                                                />
                                            </li>
                                        ))}
                                    </ol>
                                </section>
                            ))}

                            <div className="flex flex-col gap-2">
                                {track.continue && (
                                    <Button
                                        asChild
                                        className="h-auto py-2 text-left whitespace-normal"
                                    >
                                        <Link
                                            href={showLesson(
                                                track.continue.slug,
                                            )}
                                        >
                                            {t('progress.trackContinue', {
                                                lesson: track.continue.title,
                                            })}
                                            <ArrowRight aria-hidden="true" />
                                        </Link>
                                    </Button>
                                )}
                                <Button asChild variant="outline">
                                    <Link
                                        href={showTrack({
                                            roadmap: roadmapSlug,
                                            track: track.slug,
                                        })}
                                    >
                                        {t('roadmap.viewTrack')}
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </>
                )}
            </SheetContent>
        </Sheet>
    );
}
