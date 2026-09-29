import { Link, useForm } from '@inertiajs/react';
import { CircleAlert, CircleCheck, GitCompare, Send } from 'lucide-react';
import { useId } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { StatusActions } from '@/components/publishing/status-actions';
import { ContentStatusBadge } from '@/components/publishing/status-badges';
import { t } from '@/i18n';
import { formatDate } from '@/lib/format';
import {
    changes,
    publish,
    status as statusRoute,
} from '@/routes/admin/lessons';
import { show as showVersion } from '@/routes/admin/lessons/versions';
import type { ContentStatus } from '@/types/enums';

export type Readiness = { code: string; message: string }[];

export type Publication = {
    version: number | null;
    published_at: string | null;
    /** Status PUBLISHED (a restored lesson keeps its version but is not). */
    live: boolean;
    has_unpublished_changes: boolean;
    /** The version a publish makes live: the current one when unchanged. */
    next_version: number | null;
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
    lessonTitle,
    status,
    readiness,
    publication,
    versions,
    canPublish,
    statusActions,
    dirty,
    error,
}: {
    lessonSlug: string;
    lessonTitle: string;
    status: ContentStatus;
    statusActions: ContentStatus[];
    readiness: Readiness;
    publication: Publication;
    versions: VersionEntry[];
    canPublish: boolean;
    dirty: boolean;
    error?: string;
}) {
    const noteId = useId();
    const form = useForm({ change_note: '' });
    const nextVersion = publication.next_version;
    const archived = status === 'ARCHIVED';

    const blocked = !canPublish
        ? t('cms.edit.cannotPublish')
        : archived
          ? t('cms.edit.restoreFirst')
          : dirty
            ? t('cms.edit.saveBeforePublish')
            : readiness.length > 0
              ? t('cms.edit.fixBeforePublish')
              : publication.live && !publication.has_unpublished_changes
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
                    {archived ? (
                        <p>{t('cms.edit.archived')}</p>
                    ) : (
                        <>
                            {publication.version !== null && (
                                <p>
                                    {publication.has_unpublished_changes
                                        ? t('cms.edit.pending')
                                        : t('cms.edit.upToDate')}
                                </p>
                            )}
                            {publication.version !== null &&
                                publication.has_unpublished_changes && (
                                    <Link
                                        href={changes(lessonSlug)}
                                        className="inline-flex items-center gap-1.5 font-medium text-foreground underline-offset-4 hover:underline"
                                    >
                                        <GitCompare
                                            className="size-4"
                                            aria-hidden="true"
                                        />
                                        {t('cms.versions.viewChanges')}
                                    </Link>
                                )}
                            {publication.version !== null &&
                                !publication.visible_to_learners && (
                                    <p>{t('cms.edit.notVisible')}</p>
                                )}
                        </>
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
                            : nextVersion === null
                              ? t('cms.edit.publishNow')
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

            {statusActions.length > 0 && (
                <section aria-labelledby="lesson-status" className="space-y-2">
                    <h3 id="lesson-status" className="text-sm font-medium">
                        {t('cms.edit.lessonStatus')}
                    </h3>
                    <StatusActions
                        entity="lesson"
                        name={lessonTitle}
                        current={status}
                        actions={statusActions}
                        url={statusRoute.url(lessonSlug)}
                    />
                </section>
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
                                    <Link
                                        href={showVersion({
                                            lesson: lessonSlug,
                                            version: entry.version,
                                        })}
                                        className="underline-offset-4 hover:underline"
                                    >
                                        {t('cms.edit.version', {
                                            version: entry.version,
                                        })}
                                    </Link>
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
