/**
 * Short texts of a question (options, items, pairs) are plain text; a
 * span between backticks shows as code, since so many answers are
 * commands or identifiers. Nothing is parsed as HTML.
 */
export function InlineCode({ text }: { text: string }) {
    const parts = text.split(/(`[^`]+`)/g);

    return (
        <>
            {parts.map((part, index) =>
                part.length > 2 &&
                part.startsWith('`') &&
                part.endsWith('`') ? (
                    <code
                        key={index}
                        className="rounded bg-muted px-1 py-0.5 font-mono text-[0.9em]"
                    >
                        {part.slice(1, -1)}
                    </code>
                ) : (
                    part
                ),
            )}
        </>
    );
}
