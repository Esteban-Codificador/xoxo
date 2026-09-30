import { Link, useHttp } from '@inertiajs/react';
import { CircleAlert, CircleCheck, CircleHelp } from 'lucide-react';
import { useState } from 'react';
import { t } from '@/i18n';
import { lookup as lookupRoute } from '@/routes/admin/videos';

/** What the server learned from YouTube's oEmbed about a pasted link. */
export type VideoLookup = {
    video_id: string;
    url: string;
    /** true: exists and can be embedded; false: cannot be used; null: YouTube did not answer. */
    available: boolean | null;
    reason: string | null;
    title: string | null;
    instructor: string | null;
    thumbnail_url: string | null;
    existing: { id: number; title: string; href: string } | null;
};

/**
 * The same lookup outside React state (the editor's video prompt). Null
 * when it could not be done: a failed lookup never blocks the editor.
 */
export async function lookupVideo(url: string): Promise<VideoLookup | null> {
    try {
        const response = await fetch(lookupRoute.url({ query: { url } }), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        return response.ok ? ((await response.json()) as VideoLookup) : null;
    } catch {
        return null;
    }
}

/**
 * Asks the server to look a YouTube link up (oEmbed). The server parses
 * the link, so what counts as a valid one is decided in one place.
 */
export function useVideoLookup() {
    const http = useHttp<Record<string, never>, VideoLookup>({});
    const [result, setResult] = useState<VideoLookup | null>(null);
    const [error, setError] = useState<string | null>(null);

    const lookup = (url: string): Promise<VideoLookup | null> => {
        setError(null);
        setResult(null);

        return http
            .get(lookupRoute.url({ query: { url } }), {
                onError: (errors) =>
                    setError(String(Object.values(errors)[0] ?? '')),
            })
            .then((found) => {
                setResult(found ?? null);

                return found ?? null;
            })
            .catch(() => {
                setError(t('cms.videos.lookup.failed'));

                return null;
            });
    };

    const reset = () => {
        setResult(null);
        setError(null);
    };

    return { lookup, reset, result, error, processing: http.processing };
}

/** The answer of a lookup: the video as YouTube shows it, or why it cannot be used. */
export function VideoLookupResult({
    result,
    onUse,
}: {
    result: VideoLookup;
    /** Offered when YouTube gave a title: fill the form with it. */
    onUse?: () => void;
}) {
    const Icon =
        result.available === true
            ? CircleCheck
            : result.available === false
              ? CircleAlert
              : CircleHelp;

    return (
        <div
            role="status"
            className="flex gap-3 rounded-lg border bg-muted/30 p-3 text-sm"
        >
            {result.thumbnail_url !== null && (
                <img
                    src={result.thumbnail_url}
                    alt=""
                    width={120}
                    height={90}
                    className="h-[68px] w-[120px] shrink-0 rounded-md border object-cover"
                />
            )}
            <div className="min-w-0 space-y-1">
                <p
                    className={
                        result.available === false
                            ? 'flex items-start gap-1.5 font-medium text-destructive'
                            : 'flex items-start gap-1.5 font-medium'
                    }
                >
                    <Icon
                        className={
                            result.available === true
                                ? 'mt-0.5 size-4 shrink-0 text-state-completed'
                                : 'mt-0.5 size-4 shrink-0'
                        }
                        aria-hidden="true"
                    />
                    {result.available === true
                        ? t('cms.videos.lookup.available')
                        : result.available === false
                          ? t('cms.videos.lookup.unavailable', {
                                reason: result.reason ?? '',
                            })
                          : t('cms.videos.lookup.inconclusive')}
                </p>
                {result.title !== null && (
                    <p className="[overflow-wrap:anywhere]">
                        {result.title}
                        {result.instructor !== null && (
                            <span className="text-muted-foreground">
                                {' '}
                                · {result.instructor}
                            </span>
                        )}
                    </p>
                )}
                {result.existing !== null && (
                    <p className="text-muted-foreground">
                        {t('cms.videos.lookup.existing')}{' '}
                        <Link
                            href={result.existing.href}
                            className="text-foreground underline underline-offset-4"
                        >
                            {result.existing.title}
                        </Link>
                    </p>
                )}
                {onUse !== undefined && result.title !== null && (
                    <button
                        type="button"
                        onClick={onUse}
                        className="text-sm font-medium underline underline-offset-4"
                    >
                        {t('cms.videos.lookup.useData')}
                    </button>
                )}
            </div>
        </div>
    );
}
