import { ImageOff } from 'lucide-react';
import { t } from '@/i18n';
import { useMediaSource } from '../media';

/**
 * An image of the content: a stored file (by media id) with its
 * alternative text. Width and height reserve its box before it loads; the
 * white backdrop keeps transparent diagrams readable in dark mode.
 */
export function ContentImage({
    mediaId,
    alt,
}: {
    mediaId: unknown;
    alt: unknown;
}) {
    const source = useMediaSource(mediaId);
    const text = typeof alt === 'string' ? alt : '';

    if (source === undefined) {
        return (
            <p className="not-prose my-6 flex items-start gap-2 rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                <ImageOff
                    className="mt-0.5 size-4 shrink-0"
                    aria-hidden="true"
                />
                <span>{t('richContent.imageUnavailable', { alt: text })}</span>
            </p>
        );
    }

    return (
        <div className="not-prose my-6">
            <img
                src={source.url}
                width={source.width}
                height={source.height}
                alt={text}
                loading="lazy"
                decoding="async"
                className="mx-auto h-auto max-w-full rounded-lg border bg-white"
            />
        </div>
    );
}
