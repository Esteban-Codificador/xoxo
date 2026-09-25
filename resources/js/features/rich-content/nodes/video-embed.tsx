import { t } from '@/i18n';

const YOUTUBE_ID = /^[A-Za-z0-9_-]{11}$/;

/**
 * Videos are stored as provider + ID (never an iframe or URL). The ID is
 * checked again here before building the privacy-enhanced embed URL.
 */
export function VideoEmbed({
    provider,
    videoId,
}: {
    provider: unknown;
    videoId: unknown;
}) {
    if (
        provider !== 'youtube' ||
        typeof videoId !== 'string' ||
        !YOUTUBE_ID.test(videoId)
    ) {
        return (
            <p className="not-prose my-6 rounded-lg border p-4 text-sm text-muted-foreground">
                {t('richContent.invalidVideo')}
            </p>
        );
    }

    return (
        <div className="not-prose my-6 aspect-video overflow-hidden rounded-lg border bg-muted">
            <iframe
                src={`https://www.youtube-nocookie.com/embed/${videoId}`}
                title={t('richContent.videoTitle')}
                loading="lazy"
                referrerPolicy="strict-origin-when-cross-origin"
                allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowFullScreen
                className="size-full"
            />
        </div>
    );
}
