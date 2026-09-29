import { Link } from '@inertiajs/react';
import { Info, Lock } from 'lucide-react';
import { t } from '@/i18n';
import { cn } from '@/lib/utils';
import { show as showLesson } from '@/routes/lessons';
import { show as showTrack } from '@/routes/tracks';
import type { Blocker } from './types';

type Props = {
    blockers: Blocker[];
    roadmapSlug: string;
    /** STRICT roadmaps enforce the order; ADVISORY ones only recommend it. */
    strict: boolean;
    scope: 'lesson' | 'track';
    className?: string;
};

/** What to complete first, as the server computed it (ADR-009). */
export function BlockerNotice({
    blockers,
    roadmapSlug,
    strict,
    scope,
    className,
}: Props) {
    if (blockers.length === 0) {
        return null;
    }

    const Icon = strict ? Lock : Info;
    const title = strict
        ? t('progress.strictTitle')
        : scope === 'track'
          ? t('progress.advisoryTrackTitle')
          : t('progress.advisoryTitle');

    return (
        <aside
            role="note"
            className={cn(
                'flex gap-3 rounded-xl border p-4 text-sm',
                strict
                    ? 'border-state-locked/40 bg-state-locked-soft'
                    : 'border-callout-note/40 bg-muted/40',
                className,
            )}
        >
            <Icon
                className={cn(
                    'mt-0.5 size-4 shrink-0',
                    strict ? 'text-state-locked' : 'text-callout-note',
                )}
                aria-hidden="true"
            />
            <div className="space-y-1.5">
                <p className="font-medium">{title}</p>
                <ul className="space-y-1">
                    {blockers.map((blocker) => (
                        <li key={`${blocker.type}-${blocker.slug}`}>
                            <Link
                                href={
                                    blocker.type === 'lesson'
                                        ? showLesson(blocker.slug)
                                        : showTrack({
                                              roadmap: roadmapSlug,
                                              track: blocker.slug,
                                          })
                                }
                                className="underline underline-offset-4 hover:text-foreground"
                            >
                                {blocker.type === 'track'
                                    ? t('progress.trackBlocker', {
                                          track: blocker.title,
                                          progress: blocker.progress ?? 0,
                                          required: blocker.required ?? 100,
                                      })
                                    : blocker.title}
                            </Link>
                        </li>
                    ))}
                </ul>
                {!strict && (
                    <p className="text-muted-foreground">
                        {t('progress.advisoryHint')}
                    </p>
                )}
            </div>
        </aside>
    );
}
