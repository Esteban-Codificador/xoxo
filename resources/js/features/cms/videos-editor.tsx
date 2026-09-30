import { ExternalLink } from 'lucide-react';
import { VideoAvailabilityBadge } from '@/components/publishing/status-badges';
import { t } from '@/i18n';
import type { LinkStatus } from '@/types/enums';
import { OrderedLinksEditor } from './ordered-links-editor';

export type VideoOption = {
    id: number;
    title: string;
    instructor: string | null;
    duration: string | null;
    url: string;
    link_status: LinkStatus;
    published: boolean;
};

/**
 * Videos of a lesson in display order. Videos are added to the catalog in
 * their own section, where YouTube confirms them (ADR-035).
 */
export function VideosEditor({
    options,
    value,
    onChange,
    errorFor,
}: {
    options: VideoOption[];
    value: number[];
    onChange: (value: number[]) => void;
    errorFor?: (index: number) => string | undefined;
}) {
    return (
        <OrderedLinksEditor
            options={options}
            value={value}
            onChange={onChange}
            errorFor={errorFor}
            emptyText={t('cms.relations.noVideos')}
            addLabel={t('cms.relations.addVideo')}
            optionLabel={(option) =>
                option.instructor === null
                    ? option.title
                    : `${option.title} (${option.instructor})`
            }
            describe={(video) => (
                <>
                    {[video.instructor, video.duration].some(Boolean) && (
                        <span>
                            {[video.instructor, video.duration]
                                .filter(Boolean)
                                .join(' · ')}
                        </span>
                    )}
                    {!video.published && (
                        <span>({t('cms.dependencies.unpublished')})</span>
                    )}
                    <VideoAvailabilityBadge status={video.link_status} />
                    <a
                        href={video.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-1 underline-offset-4 hover:underline"
                    >
                        {t('cms.videos.watch')}
                        <ExternalLink
                            className="size-3 shrink-0"
                            aria-hidden="true"
                        />
                        <span className="sr-only">
                            {t('richContent.opensInNewTab')}
                        </span>
                    </a>
                </>
            )}
        />
    );
}
