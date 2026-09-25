export type SafeLink = {
    href: string;
    kind: 'internal' | 'anchor' | 'external' | 'mail';
};

/**
 * Mirrors RichContentSchema::safeHref on the server. The server already
 * rejects anything else; this is defense in depth for the renderer.
 */
export function safeHref(value: unknown): SafeLink | null {
    if (typeof value !== 'string' || value === '' || value.length > 2048) {
        return null;
    }

    // eslint-disable-next-line no-control-regex
    if (/[\x00-\x20]/.test(value)) {
        return null;
    }

    if (value.startsWith('#')) {
        return { href: value, kind: 'anchor' };
    }

    if (value.startsWith('/') && !value.startsWith('//')) {
        return { href: value, kind: 'internal' };
    }

    const scheme = /^([a-z][a-z0-9+.-]*):/i.exec(value)?.[1]?.toLowerCase();

    if (scheme === 'https' || scheme === 'http') {
        return { href: value, kind: 'external' };
    }

    return scheme === 'mailto' ? { href: value, kind: 'mail' } : null;
}
