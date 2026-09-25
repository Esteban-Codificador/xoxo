import { Check, Copy } from 'lucide-react';
import type { CSSProperties } from 'react';
import { useEffect, useState } from 'react';
import type { ThemedToken } from 'shiki/core';
import { Button } from '@/components/ui/button';
import { useClipboard } from '@/hooks/use-clipboard';
import { t } from '@/i18n';
import { highlight, resolveLanguage } from '../highlighter';

type Props = { code: string; language?: unknown };

/**
 * Renders plain text first and swaps in Shiki tokens once the grammar
 * loads. Tokens become React spans: no HTML string is injected.
 */
export function CodeBlock({ code, language }: Props) {
    const resolved = resolveLanguage(language);
    const [tokens, setTokens] = useState<ThemedToken[][] | null>(null);
    const [, copy] = useClipboard();
    const [copied, setCopied] = useState(false);
    const canCopy =
        typeof navigator !== 'undefined' && navigator.clipboard !== undefined;

    useEffect(() => {
        if (!copied) {
            return;
        }

        const timer = setTimeout(() => setCopied(false), 2000);

        return () => clearTimeout(timer);
    }, [copied]);

    useEffect(() => {
        let active = true;

        if (resolved !== null) {
            highlight(code, resolved)
                .then((result) => active && setTokens(result))
                .catch(() => active && setTokens(null));
        }

        return () => {
            active = false;
        };
    }, [code, resolved]);

    const label =
        typeof language === 'string' && language !== '' ? language : null;

    return (
        <figure className="not-prose group relative my-6 overflow-hidden rounded-lg border bg-muted/40">
            {label && (
                <figcaption className="border-b px-4 py-1.5 font-mono text-xs text-muted-foreground">
                    {label}
                </figcaption>
            )}
            {canCopy && (
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="absolute top-1 right-1 size-7 text-muted-foreground"
                    onClick={async () => setCopied(await copy(code))}
                    aria-label={t('richContent.copyCode')}
                >
                    {copied ? (
                        <Check aria-hidden="true" />
                    ) : (
                        <Copy aria-hidden="true" />
                    )}
                </Button>
            )}
            <pre
                tabIndex={0}
                className="shiki-tokens overflow-x-auto p-4 pr-10 font-mono text-sm leading-relaxed"
                data-language={resolved ?? undefined}
            >
                <code>
                    {tokens === null
                        ? code
                        : tokens.map((line, index) => (
                              // Newlines stay in the text so copying a selection keeps them.
                              <span key={index}>
                                  {index > 0 && '\n'}
                                  {line.map((token, position) => (
                                      <span
                                          key={position}
                                          style={
                                              token.htmlStyle as CSSProperties
                                          }
                                      >
                                          {token.content}
                                      </span>
                                  ))}
                              </span>
                          ))}
                </code>
            </pre>
            <span className="sr-only" aria-live="polite">
                {copied ? t('richContent.codeCopied') : ''}
            </span>
        </figure>
    );
}
