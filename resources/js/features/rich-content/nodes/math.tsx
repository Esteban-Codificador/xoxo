import { useEffect, useRef, useState } from 'react';
import { t } from '@/i18n';
import { cn } from '@/lib/utils';

type KatexModule = typeof import('katex');

let katexModule: Promise<KatexModule> | null = null;

function loadKatex(): Promise<KatexModule> {
    katexModule ??= Promise.all([
        import('katex'),
        import('katex/dist/katex.min.css'),
    ]).then(([module]) => module);

    return katexModule;
}

/**
 * KaTeX writes its own DOM into the element (documented exception to
 * "no HTML injection", frontend-architecture §7): the LaTeX was validated
 * by the server and `trust` stays false, so no links, classes or styles
 * from the source reach the page. React renders no children in that
 * element; the raw LaTeX is the text while loading and on error.
 */
function useKatex<T extends HTMLElement>(latex: string, displayMode: boolean) {
    const ref = useRef<T>(null);
    const [failed, setFailed] = useState(false);

    useEffect(() => {
        let active = true;
        const element = ref.current;

        if (element === null) {
            return;
        }

        element.textContent = latex;

        loadKatex()
            .then((katex) => {
                if (!active) {
                    return;
                }

                katex.default.render(latex, element, {
                    displayMode,
                    throwOnError: true,
                    trust: false,
                    strict: 'ignore',
                    maxExpand: 500,
                    output: 'htmlAndMathml',
                });
                setFailed(false);
            })
            .catch(() => {
                if (active) {
                    element.textContent = latex;
                    setFailed(true);
                }
            });

        return () => {
            active = false;
        };
    }, [latex, displayMode]);

    return { ref, failed };
}

export function MathInline({ latex }: { latex: string }) {
    const { ref, failed } = useKatex<HTMLSpanElement>(latex, false);

    return (
        <span
            ref={ref}
            data-math="inline"
            className={cn(failed && 'font-mono text-destructive')}
            title={failed ? t('richContent.mathError') : undefined}
        />
    );
}

export function MathBlock({ latex }: { latex: string }) {
    const { ref, failed } = useKatex<HTMLDivElement>(latex, true);

    return (
        <div className="not-prose my-6">
            {failed && (
                <p className="mb-1 text-sm text-destructive">
                    {t('richContent.mathError')}
                </p>
            )}
            <div
                ref={ref}
                data-math="block"
                tabIndex={0}
                className={cn('overflow-x-auto', failed && 'font-mono')}
            />
        </div>
    );
}
