import { Play } from 'lucide-react';
import { useState } from 'react';
import { t } from '@/i18n';

export type VideoLink = {
    id: number;
    provider: 'YOUTUBE';
    video_id: string;
    title: string;
    instructor: string | null;
    description: string | null;
    duration: string | null;
    language: string;
    thumbnail_url: string | null;
    note: string | null;
    start_seconds: number | null;
};

const YOUTUBE_ID = /^[A-Za-z0-9_-]{11}$/;

function languageName(code: string): string {
    return code === 'es' || code === 'en'
        ? t(`languages.${code}`)
        : code.toUpperCase();
}

/**
 * The player loads only when the learner asks for it: until then the
 * lesson shows YouTube's thumbnail and nothing from YouTube runs (TD-8).
 */
function VideoPlayer({ video }: { video: VideoLink }) {
    const [playing, setPlaying] = useState(false);

    if (!YOUTUBE_ID.test(video.video_id)) {
        return null;
    }

    if (playing) {
        const start =
            video.start_seconds !== null ? `&start=${video.start_seconds}` : '';

        return (
            <div className="aspect-video overflow-hidden rounded-lg border bg-muted">
                <iframe
                    src={`https://www.youtube-nocookie.com/embed/${video.video_id}?autoplay=1${start}`}
                    title={video.title}
                    referrerPolicy="strict-origin-when-cross-origin"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowFullScreen
                    className="size-full"
                />
            </div>
        );
    }

    return (
        <button
            type="button"
            onClick={() => setPlaying(true)}
            aria-label={t('lesson.playVideo', { title: video.title })}
            className="group relative block aspect-video w-full overflow-hidden rounded-lg border bg-muted focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
        >
            {video.thumbnail_url !== null && (
                <img
                    src={video.thumbnail_url}
                    alt=""
                    width={480}
                    height={360}
                    loading="lazy"
                    className="size-full object-cover"
                />
            )}
            <span className="absolute inset-0 flex items-center justify-center bg-black/20 transition-colors group-hover:bg-black/30">
                <span className="flex size-14 items-center justify-center rounded-full bg-black/75 text-white shadow-lg">
                    <Play
                        className="size-6 translate-x-0.5 fill-current"
                        aria-hidden="true"
                    />
                </span>
            </span>
        </button>
    );
}

export function VideoList({ videos }: { videos: VideoLink[] }) {
    return (
        <ul className="grid gap-6">
            {videos.map((video) => (
                <li key={video.id} className="space-y-2">
                    <VideoPlayer video={video} />
                    <div>
                        <p className="font-medium">{video.title}</p>
                        <p className="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground">
                            {video.instructor !== null && (
                                <span>{video.instructor}</span>
                            )}
                            {video.duration !== null && (
                                <>
                                    <span aria-hidden="true">·</span>
                                    <span>
                                        <span className="sr-only">
                                            {t('lesson.videoDuration')}{' '}
                                        </span>
                                        {video.duration}
                                    </span>
                                </>
                            )}
                            <span aria-hidden="true">·</span>
                            <span>{languageName(video.language)}</span>
                        </p>
                        {(video.note ?? video.description) !== null && (
                            <p className="mt-1 text-sm text-muted-foreground">
                                {video.note ?? video.description}
                            </p>
                        )}
                    </div>
                </li>
            ))}
        </ul>
    );
}
