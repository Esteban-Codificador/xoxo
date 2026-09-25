import { useEffect, useState } from 'react';

/**
 * Id of the section being read: the last heading that crossed the top
 * third of the viewport. Null until the reader scrolls into the body.
 */
export function useActiveHeading(ids: string[]): string | null {
    const [active, setActive] = useState<string | null>(null);
    const key = ids.join('|');

    useEffect(() => {
        if (typeof IntersectionObserver === 'undefined' || ids.length === 0) {
            return;
        }

        const elements = ids
            .map((id) => document.getElementById(id))
            .filter((element): element is HTMLElement => element !== null);

        const observer = new IntersectionObserver(
            () => {
                const passed = elements.filter(
                    (element) =>
                        element.getBoundingClientRect().top <
                        window.innerHeight / 3,
                );
                setActive(passed.at(-1)?.id ?? null);
            },
            { rootMargin: '0px 0px -66% 0px', threshold: [0, 1] },
        );

        elements.forEach((element) => observer.observe(element));

        return () => observer.disconnect();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [key]);

    return active;
}
