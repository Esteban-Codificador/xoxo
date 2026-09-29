import { t } from '@/i18n';
import type { TranslationKey } from '@/i18n/types';
import { formatMinutes } from '@/lib/format';
import { cn } from '@/lib/utils';

export type DiffSegment = { text: string; changed: boolean };

export type DiffLine = {
    type: 'same' | 'added' | 'removed';
    old: number | null;
    new: number | null;
    segments: DiffSegment[];
};

export type DiffRow = DiffLine | { type: 'skipped'; count: number };

type TextField = 'title' | 'summary' | 'why_it_matters' | 'learning_objectives';

type AttributeChange =
    | { field: 'content_type' | 'difficulty'; before: string; after: string }
    | { field: 'estimated_minutes'; before: number; after: number };

export type Changes = {
    fields: { field: TextField; rows: DiffRow[] }[];
    attributes: AttributeChange[];
    body: DiffRow[];
};

const fieldLabels: Record<
    TextField | AttributeChange['field'],
    TranslationKey
> = {
    title: 'cms.edit.fields.title',
    summary: 'cms.edit.fields.summary',
    why_it_matters: 'cms.edit.fields.whyItMatters',
    learning_objectives: 'cms.edit.fields.objectives',
    content_type: 'cms.edit.fields.contentType',
    difficulty: 'cms.edit.fields.difficulty',
    estimated_minutes: 'cms.edit.fields.minutes',
};

export function hasChanges(changes: Changes): boolean {
    return (
        changes.fields.length > 0 ||
        changes.attributes.length > 0 ||
        changes.body.length > 0
    );
}

function attributeValue(change: AttributeChange, side: 'before' | 'after') {
    if (change.field === 'estimated_minutes') {
        return formatMinutes(change[side]);
    }

    return change.field === 'content_type'
        ? t(`contentType.${change[side]}` as TranslationKey)
        : t(`difficulty.${change[side]}` as TranslationKey);
}

/**
 * The changes the server computed between two snapshots of a lesson:
 * attributes as before → after, text fields and the body (as Markdown) as
 * line diffs with the edited words marked.
 */
export function LessonChanges({ changes }: { changes: Changes }) {
    if (!hasChanges(changes)) {
        return (
            <p className="text-sm text-muted-foreground">
                {t('cms.versions.noChanges')}
            </p>
        );
    }

    return (
        <div className="space-y-8">
            {changes.attributes.length > 0 && (
                <dl className="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[max-content_1fr]">
                    {changes.attributes.map((change) => (
                        <div key={change.field} className="contents">
                            <dt className="font-medium">
                                {t(fieldLabels[change.field])}
                            </dt>
                            <dd className="flex flex-wrap items-center gap-2">
                                <del className="rounded-sm bg-destructive/10 px-1.5 no-underline">
                                    {attributeValue(change, 'before')}
                                </del>
                                <span aria-hidden="true">→</span>
                                <span className="sr-only">
                                    {t('cms.versions.becomes')}
                                </span>
                                <ins className="rounded-sm bg-state-completed-soft px-1.5 no-underline">
                                    {attributeValue(change, 'after')}
                                </ins>
                            </dd>
                        </div>
                    ))}
                </dl>
            )}

            {changes.fields.map(({ field, rows }) => (
                <section key={field} className="space-y-2">
                    <h3 className="text-sm font-medium">
                        {t(fieldLabels[field])}
                    </h3>
                    <DiffView rows={rows} />
                </section>
            ))}

            {changes.body.length > 0 && (
                <section className="space-y-2">
                    <h3 className="text-sm font-medium">
                        {t('cms.edit.fields.body')}
                    </h3>
                    <p className="text-xs text-muted-foreground">
                        {t('cms.versions.bodyAsMarkdown')}
                    </p>
                    <DiffView rows={changes.body} numbered />
                </section>
            )}
        </div>
    );
}

const lineStyles = {
    same: '',
    added: 'bg-state-completed-soft',
    removed: 'bg-destructive/10',
} as const;

const signs = { same: ' ', added: '+', removed: '−' } as const;

export function DiffView({
    rows,
    numbered = false,
}: {
    rows: DiffRow[];
    numbered?: boolean;
}) {
    return (
        <ol className="overflow-hidden rounded-lg border font-mono text-[0.8125rem] leading-relaxed">
            {rows.map((row, index) =>
                row.type === 'skipped' ? (
                    <li
                        key={`skipped-${index}`}
                        className="border-y bg-muted/50 px-3 py-1 text-center font-sans text-xs text-muted-foreground first:border-t-0 last:border-b-0"
                    >
                        {row.count === 1
                            ? t('cms.versions.skippedOne')
                            : t('cms.versions.skipped', { count: row.count })}
                    </li>
                ) : (
                    <li
                        key={`${row.type}-${row.old}-${row.new}`}
                        className={cn(
                            'grid gap-x-2 px-2',
                            // Line numbers only where there is room for them.
                            numbered
                                ? 'grid-cols-[1ch_minmax(0,1fr)] sm:grid-cols-[2.5rem_2.5rem_1ch_minmax(0,1fr)]'
                                : 'grid-cols-[1ch_minmax(0,1fr)]',
                            lineStyles[row.type],
                        )}
                    >
                        {numbered && (
                            <>
                                <span
                                    aria-hidden="true"
                                    className="text-right text-muted-foreground tabular-nums select-none max-sm:hidden"
                                >
                                    {row.old}
                                </span>
                                <span
                                    aria-hidden="true"
                                    className="text-right text-muted-foreground tabular-nums select-none max-sm:hidden"
                                >
                                    {row.new}
                                </span>
                            </>
                        )}
                        <span aria-hidden="true" className="select-none">
                            {signs[row.type]}
                        </span>
                        <DiffText row={row} />
                    </li>
                ),
            )}
        </ol>
    );
}

function DiffText({ row }: { row: DiffLine }) {
    const Mark = row.type === 'removed' ? 'del' : 'ins';
    const markClass =
        row.type === 'removed'
            ? 'bg-destructive/25 no-underline'
            : 'bg-state-completed/25 no-underline';

    return (
        <span className="break-words whitespace-pre-wrap">
            {row.type !== 'same' && (
                <span className="sr-only">
                    {t(
                        row.type === 'added'
                            ? 'cms.versions.added'
                            : 'cms.versions.removed',
                    )}
                </span>
            )}
            {row.segments.map((segment, index) =>
                segment.changed ? (
                    <Mark key={index} className={cn('rounded-sm', markClass)}>
                        {segment.text}
                    </Mark>
                ) : (
                    <span key={index}>{segment.text}</span>
                ),
            )}
            {/* Keeps the height of an empty line. */}
            {row.segments.every((segment) => segment.text === '') && '​'}
        </span>
    );
}
