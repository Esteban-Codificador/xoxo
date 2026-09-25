import { Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { t } from '@/i18n';
import { show } from '@/routes/lessons';

export type LessonLink = { slug: string; title: string };

/** Previous and next lesson in the track's study order (from the server). */
export function LessonPager({
    previous,
    next,
}: {
    previous: LessonLink | null;
    next: LessonLink | null;
}) {
    if (previous === null && next === null) {
        return null;
    }

    return (
        <nav
            aria-label={t('lesson.pager')}
            className="grid gap-3 sm:grid-cols-2"
        >
            {previous ? (
                <Link
                    href={show(previous.slug)}
                    rel="prev"
                    className="group flex flex-col gap-1 rounded-xl border p-4 transition-colors hover:bg-muted/50"
                >
                    <span className="flex items-center gap-1 text-xs text-muted-foreground">
                        <ArrowLeft className="size-3.5" aria-hidden="true" />
                        {t('lesson.previous')}
                    </span>
                    <span className="font-medium">{previous.title}</span>
                </Link>
            ) : (
                <span className="hidden sm:block" />
            )}
            {next ? (
                <Link
                    href={show(next.slug)}
                    rel="next"
                    className="group flex flex-col items-end gap-1 rounded-xl border p-4 text-right transition-colors hover:bg-muted/50"
                >
                    <span className="flex items-center gap-1 text-xs text-muted-foreground">
                        {t('lesson.next')}
                        <ArrowRight className="size-3.5" aria-hidden="true" />
                    </span>
                    <span className="font-medium">{next.title}</span>
                </Link>
            ) : (
                <p className="flex items-center justify-end rounded-xl border border-dashed p-4 text-sm text-muted-foreground">
                    {t('lesson.endOfTrack')}
                </p>
            )}
        </nav>
    );
}
