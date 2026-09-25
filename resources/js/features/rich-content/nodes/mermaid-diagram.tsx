import { useEffect, useId, useRef, useState } from 'react';
import { useIsDarkMode } from '@/hooks/use-is-dark-mode';
import { t } from '@/i18n';

let renderQueue: Promise<unknown> = Promise.resolve();

/**
 * Wide diagrams shrink to fit, but never below this scale (labels would be
 * unreadable on a phone); past it the figure scrolls horizontally.
 */
const MIN_SCALE = 0.75;

/**
 * Mermaid is loaded on demand and runs with securityLevel "strict" (its
 * output is sanitized with DOMPurify, no click handlers or HTML labels).
 * The SVG it returns is the one place we insert markup (documented
 * exception, frontend-architecture §7). Renders are queued because
 * `mermaid.initialize` is global.
 */
export function MermaidDiagram({ source }: { source: string }) {
    const ref = useRef<HTMLDivElement>(null);
    const id = `mermaid-${useId().replace(/[^a-zA-Z0-9-]/g, '')}`;
    const dark = useIsDarkMode();
    const [status, setStatus] = useState<'loading' | 'ready' | 'failed'>(
        'loading',
    );

    useEffect(() => {
        let active = true;

        const task = renderQueue.then(async () => {
            const { default: mermaid } = await import('mermaid');
            mermaid.initialize({
                startOnLoad: false,
                securityLevel: 'strict',
                theme: dark ? 'dark' : 'default',
                fontFamily: 'inherit',
            });

            const { svg } = await mermaid.render(id, source);

            if (active && ref.current) {
                ref.current.innerHTML = svg;
                const element = ref.current.querySelector('svg');
                const width = element?.viewBox?.baseVal?.width;

                if (element && width) {
                    element.style.minWidth = `${Math.round(width * MIN_SCALE)}px`;
                }

                setStatus('ready');
            }
        });

        renderQueue = task.catch(() => undefined);
        task.catch(() => active && setStatus('failed'));

        return () => {
            active = false;
        };
    }, [id, source, dark]);

    return (
        <figure className="not-prose my-6">
            <div
                ref={ref}
                role="img"
                aria-label={t('richContent.diagramLabel')}
                aria-busy={status === 'loading'}
                hidden={status === 'failed'}
                tabIndex={0}
                className="overflow-x-auto rounded-lg border bg-card p-4 [&_svg]:mx-auto [&_svg]:block [&_svg]:h-auto"
            />
            {status === 'failed' && (
                <div className="rounded-lg border border-destructive/40 p-4">
                    <p className="mb-2 text-sm text-destructive">
                        {t('richContent.diagramError')}
                    </p>
                    <pre className="overflow-x-auto font-mono text-xs text-muted-foreground">
                        {source}
                    </pre>
                </div>
            )}
        </figure>
    );
}
