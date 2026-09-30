import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { t } from '@/i18n';
import { formatRelativeDay } from '@/lib/format';
import { cn } from '@/lib/utils';
import { show as showLesson } from '@/routes/lessons';
import { show as showTrack } from '@/routes/tracks';
import type { RecommendationReason } from '@/types/enums';

/** What the server recommends (architecture §8): the rules run in PHP. */
export type Recommendation = {
    reason: RecommendationReason;
    subject: {
        type: 'lesson' | 'track';
        slug: string;
        title: string;
        /** The track of a recommended lesson. */
        track: string | null;
    };
    params: Record<string, string | number>;
    priority: number;
};

/** The sentences of a recommendation, in the learner's language. */
export function recommendationText(recommendation: Recommendation): {
    eyebrow: string;
    reason: string;
    action: string;
} {
    const { reason, subject, params } = recommendation;
    // Continue says when the lesson was opened; review, when the quiz was failed.
    const date = params.viewed_at ?? params.failed_at;
    const values = {
        ...params,
        track: subject.type === 'track' ? subject.title : (subject.track ?? ''),
        when: typeof date === 'string' ? formatRelativeDay(date) : '',
    };

    return {
        eyebrow: t(`recommendations.eyebrow.${reason}`, values),
        reason: t(`recommendations.reason.${reason}`, values),
        action: t(`recommendations.action.${reason}`),
    };
}

/**
 * The dashboard's "what next": the first recommendation leads, the rest
 * follow in the order the server gives.
 */
export function RecommendationList({
    roadmapSlug,
    recommendations,
}: {
    roadmapSlug: string;
    recommendations: Recommendation[];
}) {
    if (recommendations.length === 0) {
        return null;
    }

    return (
        <section aria-labelledby="recommended" className="space-y-3">
            <h2 id="recommended" className="font-medium">
                {t('recommendations.title')}
            </h2>
            <ol className="grid gap-3 lg:grid-cols-3">
                {recommendations.map((recommendation, index) => {
                    const { eyebrow, reason, action } =
                        recommendationText(recommendation);
                    const { subject } = recommendation;
                    const href =
                        subject.type === 'lesson'
                            ? showLesson(subject.slug)
                            : showTrack({
                                  roadmap: roadmapSlug,
                                  track: subject.slug,
                              });

                    return (
                        <li
                            key={`${subject.type}:${subject.slug}`}
                            className={cn(
                                'flex flex-col gap-3 rounded-xl border bg-card p-5',
                                index === 0 && 'border-primary/40 shadow-xs',
                            )}
                        >
                            <div className="space-y-1">
                                <p className="text-sm font-medium text-muted-foreground">
                                    {eyebrow}
                                </p>
                                <p className="text-lg leading-snug font-medium">
                                    {subject.title}
                                </p>
                                {subject.track && (
                                    <p className="text-sm text-muted-foreground">
                                        {subject.track}
                                    </p>
                                )}
                            </div>
                            <p className="text-sm">{reason}</p>
                            <Button
                                asChild
                                variant={index === 0 ? 'default' : 'outline'}
                                className="mt-auto self-start"
                            >
                                <Link
                                    href={href}
                                    aria-label={`${action}: ${subject.title}`}
                                >
                                    {action}
                                    <ArrowRight aria-hidden="true" />
                                </Link>
                            </Button>
                        </li>
                    );
                })}
            </ol>
        </section>
    );
}
