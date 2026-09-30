import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { MediaMap, MediaSource } from './types';

const MediaContext = createContext<MediaMap>({});

export function MediaProvider({
    media,
    children,
}: {
    media: MediaMap;
    children: ReactNode;
}) {
    return <MediaContext value={media}>{children}</MediaContext>;
}

export function useMediaSource(mediaId: unknown): MediaSource | undefined {
    const media = useContext(MediaContext);

    return typeof mediaId === 'number' ? media[mediaId] : undefined;
}
