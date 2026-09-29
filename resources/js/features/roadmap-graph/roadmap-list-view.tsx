import { Link } from '@inertiajs/react';
import { ProgressBar } from '@/features/progress/progress-bar';
import { StateBadge } from '@/features/progress/state-badge';
import { t } from '@/i18n';
import { show as showTrack } from '@/routes/tracks';
import { computeLevels } from './layout';
import type { RoadmapEdge, RoadmapTrack } from './types';

type Props = {
    roadmapSlug: string;
    tracks: RoadmapTrack[];
    edges: RoadmapEdge[];
    onOpen: (slug: string) => void;
};

/**
 * The same roadmap as a list grouped by level: the view on phones and the
 * fully accessible alternative to the canvas.
 */
export function RoadmapListView({ roadmapSlug, tracks, edges, onOpen }: Props) {
    const bySlug = new Map(tracks.map((track) => [track.slug, track]));
    const levels = computeLevels(
        tracks.map((track) => track.slug),
        edges,
    );

    return (
        <ol className="space-y-8">
            {levels.map((level, index) => (
                <li key={level.join('|')} className="space-y-3">
                    <h2 className="text-sm font-medium text-muted-foreground">
                        {t('roadmap.level', { level: index + 1 })}
                    </h2>
                    <ul className="grid gap-3 sm:grid-cols-2">
                        {level.map((slug) => {
                            const track = bySlug.get(slug);

                            if (!track) {
                                return null;
                            }

                            const requires = edges.filter(
                                (edge) => edge.to === slug,
                            );

                            return (
                                <li
                                    key={slug}
                                    className="flex flex-col gap-3 rounded-xl border bg-card p-4"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <Link
                                            href={showTrack({
                                                roadmap: roadmapSlug,
                                                track: slug,
                                            })}
                                            className="font-medium underline-offset-4 hover:underline"
                                        >
                                            {track.title}
                                        </Link>
                                        <StateBadge
                                            state={track.progress.state}
                                            className="shrink-0"
                                        />
                                    </div>
                                    <ProgressBar
                                        value={track.progress.progress}
                                        state={
                                            track.progress.state === 'COMPLETED'
                                                ? 'COMPLETED'
                                                : 'IN_PROGRESS'
                                        }
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        {t('progress.lessonsDone', {
                                            completed: track.progress.completed,
                                            total: track.progress.total,
                                        })}
                                    </p>
                                    {requires.length > 0 && (
                                        <p className="text-xs text-muted-foreground">
                                            {t('roadmap.requires')}:{' '}
                                            {requires
                                                .map(
                                                    (edge) =>
                                                        `${bySlug.get(edge.from)?.title ?? edge.from} (${t(`dependencyKind.${edge.kind}`).toLowerCase()})`,
                                                )
                                                .join(', ')}
                                        </p>
                                    )}
                                    <button
                                        type="button"
                                        onClick={() => onOpen(slug)}
                                        className="self-start text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground"
                                    >
                                        {t('roadmap.openDetails', {
                                            track: track.title,
                                        })}
                                    </button>
                                </li>
                            );
                        })}
                    </ul>
                </li>
            ))}
        </ol>
    );
}
