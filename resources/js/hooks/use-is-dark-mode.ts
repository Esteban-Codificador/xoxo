import { useSyncExternalStore } from 'react';

function subscribe(callback: () => void): () => void {
    const observer = new MutationObserver(callback);
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });

    return () => observer.disconnect();
}

const isDark = (): boolean =>
    document.documentElement.classList.contains('dark');

/** Follows the `dark` class on <html>, whatever set it. */
export function useIsDarkMode(): boolean {
    return useSyncExternalStore(subscribe, isDark, () => false);
}
