import { useForm } from '@inertiajs/react';
import { CircleAlert, CircleCheck, Send } from 'lucide-react';
import { useId } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { ContentStatusBadge } from '@/features/publishing/status-badges';
import { t } from '@/i18n';
import { formatDate } from '@/lib/format';
import { publish } from '@/routes/admin/lessons';
import type { ContentStatus } from '@/types/enums';

export type Readiness = { code: string; message: string }[];

export type Publication = {
    version: number | null;
    published_at: string | null;
    has_unpublished_changes: boolean;
    visible_to_learners: boolean;
};

export type VersionEntry = {
    version: number;
    published_at: string;
    published_by: string | null;
    change_note: string | null;
    current: boolean;
};

/**
 * Publishing state of the saved working copy. The checklist and the
 * "unpublished changes" flag come from the server; this panel only decides
 * which explanation to show next to a disabled button.
 */
export function PublishPanel({
    lessonSlug,
    status,
    readiness,
    publication,
    versions,
    canPublish,
    dirty,
    error,
}: {
    lessonSlug: string;
    status: ContentStatus;
    readiness: Readiness;
    publication: Publication;
    versions: VersionEntry[];
    canPublish: boolean;
    dirty: boolean;
    error?: string;
}) {
    const noteId = useId();
    const form = useForm({ change_note: '' });
    const nextVersion = (versions[0]?.version ?? 0) + 1;

    const blocked = !canPublish
        ? t('cms.edit.cannotPublish')
        : dirty
          ? t('cms.edit.saveBeforePublish')
          : readiness.length > 0
            ? t('cms.edit.fixBeforePublish')
            : !publication.has_unpublished_changes
              ? t('cms.edit.nothingToPublish')
              : null;

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(publish.url(lessonSlug), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <div className="space-y-6">
            <section aria-labelledby="publication" className="space-y-3">
                <div className="flex items-center justify-between gap-2">
                    <h2 id="publication" className="font-medium">
                        {t('cms.edit.publication')}
                    </h2>
                    <ContentStatusBadge status={status} />
                </div>
                <div className="space-y-1 text-sm text-muted-foreground">
                    <p>
                        {publication.version === null ||
                        publication.published_at === null
                            ? t('cms.edit.neverPublished')
                            : t('cms.edit.publishedVersion', {
                                  version: publication.version,
                                  date: formatDate(publication.published_at),
                              })}
                    </p>
                    {publication.version !== null && (
                        <p>
                            {publication.has_unpublished_changes
                                ? t('cms.edit.pending')
                                : t('cms.edit.upToDate')}
                        </p>
                    )}
                    {publication.version !== null &&
                        !publication.visible_to_learners && (
                            <p>{t('cms.edit.notVisible')}</p>
                        )}
                </div>
            </section>

            <section aria-labelledby="readiness" className="space-y-2">
                <h3 id="readiness" className="text-sm font-medium">
                    {t('cms.edit.readiness')}
                </h3>
                {readiness.length === 0 ? (
                    <p className="flex items-center gap-2 text-sm text-state-completed">
                        <CircleCheck
                            className="size-4 shrink-0"
                            aria-hidden="true"
                        />
                        {t('cms.edit.ready')}
                    </p>
                ) : (
                    <ul className="space-y-1.5 text-sm">
                        {readiness.map((issue) => (
                            <li key={issue.code} className="flex gap-2">
                                <CircleAlert
                                    className="mt-0.5 size-4 shrink-0 text-destructive"
                                    aria-hidden="true"
                                />
                                {issue.message}
                            </li>
                        ))}
                    </ul>
                )}
                {dirty && (
                    <p className="text-xs text-muted-foreground">
                        {t('cms.edit.readinessStale')}
                    </p>
                )}
            </section>

            {canPublish && (
                <form onSubmit={submit} className="space-y-3">
                    <div className="grid gap-2">
                        <Label htmlFor={noteId}>
                            {t('cms.edit.changeNote')}
                        </Label>
                        <Textarea
                            id={noteId}
                            name="change_note"
                            value={form.data.change_note}
                            maxLength={500}
                            rows={3}
                            aria-describedby={`${noteId}-help`}
                            aria-invalid={form.errors.change_note !== undefined}
                            onChange={(event) =>
                                form.setData('change_note', event.target.value)
                            }
                        />
                        <p
                            id={`${noteId}-help`}
                            className="text-xs text-muted-foreground"
                        >
                            {t('cms.edit.changeNoteHelp')}
                        </p>
                        <InputError message={form.errors.change_note} />
                    </div>
                    <Button
                        type="submit"
                        className="w-full"
                        disabled={blocked !== null || form.processing}
                    >
                        <Send aria-hidden="true" />
                        {form.processing
                            ? t('cms.edit.publishing')
                            : t('cms.edit.publish', { version: nextVersion })}
                    </Button>
                    {blocked !== null && (
                        <p className="text-xs text-muted-foreground">
                            {blocked}
                        </p>
                    )}
                    <InputError message={error} />
                </form>
            )}
            {!canPublish && (
                <p className="text-sm text-muted-foreground">{blocked}</p>
            )}

            <section aria-labelledby="history" className="space-y-2">
                <h3 id="history" className="text-sm font-medium">
                    {t('cms.edit.history')}
                </h3>
                {versions.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {t('cms.edit.historyEmpty')}
                    </p>
                ) : (
                    <ol className="divide-y rounded-lg border text-sm">
                        {versions.map((entry) => (
                            <li key={entry.version} className="space-y-0.5 p-3">
                                <p className="flex items-center gap-2 font-medium">
                                    {t('cms.edit.version', {
                                        version: entry.version,
                                    })}
                                    {entry.current && (
                                        <span className="rounded-md bg-state-completed-soft px-1.5 py-0.5 text-xs text-state-completed">
                                            {t('cms.edit.current')}
                                        </span>
                                    )}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {entry.published_by === null
                                        ? t('cms.edit.publishedOn', {
                                              date: formatDate(
                                                  entry.published_at,
                                              ),
                                          })
                                        : t('cms.edit.publishedBy', {
                                              date: formatDate(
                                                  entry.published_at,
                                              ),
                                              user: entry.published_by,
                                          })}
                                </p>
                                <p className="text-muted-foreground">
                                    {entry.change_note ?? t('cms.edit.noNote')}
                                </p>
                            </li>
                        ))}
                    </ol>
                )}
            </section>
        </div>
    );
}
