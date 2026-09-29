const YOUTUBE_ID = /^[A-Za-z0-9_-]{11}$/;

const HOSTS = new Set(['youtube.com', 'youtube-nocookie.com']);

/**
 * Extracts the video ID from what an editor pastes: a bare ID or a
 * youtube.com / youtu.be link. Only the ID is stored (ADR-023); anything
 * else is rejected rather than guessed.
 */
export function parseYouTubeId(input: string): string | null {
    const value = input.trim();

    if (YOUTUBE_ID.test(value)) {
        return value;
    }

    let url: URL;

    try {
        url = new URL(value);
    } catch {
        return null;
    }

    if (url.protocol !== 'https:' && url.protocol !== 'http:') {
        return null;
    }

    const host = url.hostname.replace(/^(www|m)\./, '');
    let candidate: string | null = null;

    if (host === 'youtu.be') {
        candidate = url.pathname.split('/')[1] ?? null;
    } else if (HOSTS.has(host)) {
        candidate =
            url.pathname === '/watch'
                ? url.searchParams.get('v')
                : (/^\/(?:embed|shorts|live|v)\/([^/]+)/.exec(
                      url.pathname,
                  )?.[1] ?? null);
    }

    return candidate !== null && YOUTUBE_ID.test(candidate) ? candidate : null;
}
