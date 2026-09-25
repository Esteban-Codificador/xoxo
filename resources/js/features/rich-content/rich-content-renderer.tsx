import { Link } from '@inertiajs/react';
import { ExternalLink } from 'lucide-react';
import type { ReactNode } from 'react';
import { useMemo } from 'react';
import { t } from '@/i18n';
import { cn } from '@/lib/utils';
import { Callout, isCalloutVariant } from './nodes/callout';
import { CodeBlock } from './nodes/code-block';
import { MathBlock, MathInline } from './nodes/math';
import { MermaidDiagram } from './nodes/mermaid-diagram';
import { VideoEmbed } from './nodes/video-embed';
import { safeHref } from './safe-href';
import { collectHeadings, textOf } from './text';
import type { RichContent, RichMark, RichNode } from './types';

type Context = { headingIds: Map<RichNode, string> };

const warned = new Set<string>();

function warnUnknown(kind: 'node' | 'mark', type: string): void {
    if (import.meta.env.DEV && !warned.has(`${kind}:${type}`)) {
        warned.add(`${kind}:${type}`);
        console.warn(`[RichContent] ${kind} desconocido ignorado: ${type}`);
    }
}

function renderChildren(node: RichNode, context: Context): ReactNode[] {
    return (node.content ?? []).map((child, index) =>
        renderNode(child, index, context),
    );
}

function renderLink(
    mark: RichMark,
    children: ReactNode,
    key: number,
): ReactNode {
    const link = safeHref(mark.attrs?.href);

    switch (link?.kind) {
        case 'internal':
            return (
                <Link key={key} href={link.href}>
                    {children}
                </Link>
            );
        case 'anchor':
        case 'mail':
            return (
                <a key={key} href={link.href}>
                    {children}
                </a>
            );
        case 'external':
            return (
                <a
                    key={key}
                    href={link.href}
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    {children}
                    <ExternalLink
                        className="ml-0.5 inline size-3.5 align-baseline"
                        aria-hidden="true"
                    />
                    <span className="sr-only">
                        {' '}
                        {t('richContent.opensInNewTab')}
                    </span>
                </a>
            );
        default:
            return children;
    }
}

function renderText(node: RichNode, key: number): ReactNode {
    return (node.marks ?? []).reduce<ReactNode>(
        (children, mark, index) => {
            switch (mark.type) {
                case 'bold':
                    return <strong key={index}>{children}</strong>;
                case 'italic':
                    return <em key={index}>{children}</em>;
                case 'strike':
                    return <s key={index}>{children}</s>;
                case 'code':
                    return <code key={index}>{children}</code>;
                case 'link':
                    return renderLink(mark, children, index);
                default:
                    warnUnknown('mark', mark.type);

                    return children;
            }
        },
        <span key={key}>{node.text ?? ''}</span>,
    );
}

function renderTable(node: RichNode, key: number, context: Context): ReactNode {
    const rows = node.content ?? [];
    const hasHeaderRow =
        rows[0]?.content?.every((cell) => cell.type === 'tableHeader') ?? false;

    const renderRow = (
        row: RichNode,
        rowIndex: number,
        isHeaderRow: boolean,
    ) => (
        <tr key={rowIndex}>
            {(row.content ?? []).map((cell, cellIndex) => {
                const Cell = cell.type === 'tableHeader' ? 'th' : 'td';

                return (
                    <Cell
                        key={cellIndex}
                        scope={
                            Cell === 'th'
                                ? isHeaderRow
                                    ? 'col'
                                    : 'row'
                                : undefined
                        }
                        colSpan={Number(cell.attrs?.colspan ?? 1)}
                        rowSpan={Number(cell.attrs?.rowspan ?? 1)}
                    >
                        {renderChildren(cell, context)}
                    </Cell>
                );
            })}
        </tr>
    );

    return (
        <div
            key={key}
            role="region"
            aria-label={t('richContent.table')}
            tabIndex={0}
            className="my-6 overflow-x-auto [&_:is(td,th)>p]:my-0"
        >
            <table className="my-0">
                {hasHeaderRow && <thead>{renderRow(rows[0], 0, true)}</thead>}
                <tbody>
                    {rows
                        .slice(hasHeaderRow ? 1 : 0)
                        .map((row, index) => renderRow(row, index, false))}
                </tbody>
            </table>
        </div>
    );
}

function renderNode(node: RichNode, key: number, context: Context): ReactNode {
    const attrs = node.attrs ?? {};

    switch (node.type) {
        case 'text':
            return renderText(node, key);
        case 'paragraph':
            return <p key={key}>{renderChildren(node, context)}</p>;
        case 'heading': {
            const level = Math.min(Math.max(Number(attrs.level ?? 2), 2), 4);
            const Tag = `h${level}` as 'h2' | 'h3' | 'h4';

            return (
                <Tag key={key} id={context.headingIds.get(node)}>
                    {renderChildren(node, context)}
                </Tag>
            );
        }
        case 'bulletList':
            return <ul key={key}>{renderChildren(node, context)}</ul>;
        case 'orderedList':
            return (
                <ol
                    key={key}
                    start={
                        typeof attrs.start === 'number'
                            ? attrs.start
                            : undefined
                    }
                    type={
                        typeof attrs.type === 'string'
                            ? (attrs.type as '1')
                            : undefined
                    }
                >
                    {renderChildren(node, context)}
                </ol>
            );
        case 'listItem':
            return <li key={key}>{renderChildren(node, context)}</li>;
        case 'blockquote':
            return (
                <blockquote key={key}>
                    {renderChildren(node, context)}
                </blockquote>
            );
        case 'horizontalRule':
            return <hr key={key} />;
        case 'hardBreak':
            return <br key={key} />;
        case 'codeBlock':
            return (
                <CodeBlock
                    key={key}
                    code={textOf(node)}
                    language={attrs.language}
                />
            );
        case 'table':
            return renderTable(node, key, context);
        case 'callout':
            return (
                <Callout
                    key={key}
                    variant={
                        isCalloutVariant(attrs.variant) ? attrs.variant : 'note'
                    }
                >
                    {renderChildren(node, context)}
                </Callout>
            );
        case 'inlineMath':
            return typeof attrs.latex === 'string' ? (
                <MathInline key={key} latex={attrs.latex} />
            ) : null;
        case 'blockMath':
            return typeof attrs.latex === 'string' ? (
                <MathBlock key={key} latex={attrs.latex} />
            ) : null;
        case 'diagram':
            return attrs.kind === 'mermaid' &&
                typeof attrs.source === 'string' ? (
                <MermaidDiagram key={key} source={attrs.source} />
            ) : null;
        case 'video':
            return (
                <VideoEmbed
                    key={key}
                    provider={attrs.provider}
                    videoId={attrs.videoId}
                />
            );
        default:
            warnUnknown('node', node.type);

            return null;
    }
}

/**
 * Maps each RichContent node to a React element (frontend-architecture §7).
 * No HTML string is generated; unknown nodes and marks are skipped.
 */
export function RichContentRenderer({
    content,
    className,
}: {
    content: RichContent;
    className?: string;
}) {
    const context = useMemo<Context>(
        () => ({
            headingIds: new Map(
                collectHeadings(content.doc).map(({ node, id }) => [node, id]),
            ),
        }),
        [content],
    );

    return (
        <div
            className={cn(
                'rich-content prose max-w-[72ch] prose-headings:scroll-mt-20 prose-code:rounded prose-code:bg-muted prose-code:px-1 prose-code:py-0.5 prose-code:font-normal prose-code:before:content-none prose-code:after:content-none',
                className,
            )}
        >
            {renderChildren(content.doc, context)}
        </div>
    );
}
