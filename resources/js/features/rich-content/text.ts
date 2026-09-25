import type { Heading, RichNode } from './types';

/** Plain text of a node and its descendants. */
export function textOf(node: RichNode): string {
    if (node.type === 'text') {
        return node.text ?? '';
    }

    if (node.type === 'hardBreak') {
        return '\n';
    }

    return (node.content ?? []).map(textOf).join('');
}

/** "Árboles de decisión" → "arboles-de-decision". */
export function slugify(text: string): string {
    const slug = text
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

    return slug === '' ? 'seccion' : slug;
}

/**
 * Headings in document order with unique ids. The renderer uses the same
 * ids, so a table of contents built from this list always links correctly.
 */
export function collectHeadings(
    doc: RichNode,
): (Heading & { node: RichNode })[] {
    const seen = new Map<string, number>();
    const headings: (Heading & { node: RichNode })[] = [];

    const visit = (node: RichNode): void => {
        if (node.type === 'heading') {
            const text = textOf(node).trim();
            const base = slugify(text);
            const count = seen.get(base) ?? 0;
            seen.set(base, count + 1);
            headings.push({
                id: count === 0 ? base : `${base}-${count + 1}`,
                text,
                level: Number(node.attrs?.level ?? 2),
                node,
            });

            return;
        }

        node.content?.forEach(visit);
    };

    visit(doc);

    return headings;
}
