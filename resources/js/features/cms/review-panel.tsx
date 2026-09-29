import { Link, router, useForm } from '@inertiajs/react';
import { ClipboardCheck, MessageSquareWarning, Send } from 'lucide-react';
import type { FormEvent } from 'react';
import { useId, useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { t } from '@/i18n';
import { formatDate } from '@/lib/format';
import { changes } from '@/routes/admin/lessons';
import {
    destroy as withdrawRoute,
    returnMethod as returnRoute,
    store as submitRoute,
} from '@/routes/admin/lessons/review';

export type ReviewBlocker =
    | 'in_review'
    | 'archived'
    | 'nothing_to_review'
    | 'not_ready';

export type ReviewState = {
    open: {
        submitted_by: string | null;
        submitted_at: string;
        note: string | null;
        edited_since: boolean;
    } | null;
    returned: {
        by: string | null;
        at: string | null;
        comment: string | null;
    } | null;
    submit_blocker: ReviewBlocker | null;
};

export type ReviewPermissions = {
    submit: boolean;
    return: boolean;
    withdraw: boolean;
};

/**
 * The review flow of a lesson (ADR-032) as the server describes it: the
 * open review with what a reviewer or the author can do, the comment of the
 * last return, or the form to send the saved copy for review.
 */
export function ReviewPanel({
    lessonSlug,
    lessonTitle,
    hasPublishedVersion,
    review,
    can,
    dirty,
}: {
    lessonSlug: string;
    lessonTitle: string;
    hasPublishedVersion: boolean;
    review: ReviewState;
    can: ReviewPermissions;
    dirty: boolean;
}) {
    const { open, returned } = review;

    if (open === null && returned === null && !can.submit) {
        return null;
    }

    return (
        <section aria-labelledby="review" className="space-y-3">
            <h3 id="review" className="text-sm font-medium">
                {t('cms.review.title')}
            </h3>

            {open !== null ? (
                <OpenReview
                    lessonSlug={lessonSlug}
                    lessonTitle={lessonTitle}
                    hasPublishedVersion={hasPublishedVersion}
                    review={open}
                    can={can}
                />
            ) : (
                <>
                    {returned !== null && (
                        <div className="space-y-1 rounded-lg border border-l-4 border-l-callout-warning p-3 text-sm">
                            <p className="flex items-center gap-2 font-medium">
                                <MessageSquareWarning
                                    className="size-4 shrink-0 text-callout-warning"
                                    aria-hidden="true"
                                />
                                {returned.by === null || returned.at === null
                                    ? t('cms.review.returnedOn', {
                                          date: formatDate(returned.at ?? ''),
                                      })
                                    : t('cms.review.returnedBy', {
                                          user: returned.by,
                                          date: formatDate(returned.at),
                                      })}
                            </p>
                            <p className="whitespace-pre-line">
                                {returned.comment}
                            </p>
                        </div>
                    )}
                    {can.submit && (
                        <SubmitForm
                            lessonSlug={lessonSlug}
                            blocker={
                                dirty
                                    ? t('cms.review.saveFirst')
                                    : review.submit_blocker === null
                                      ? null
                                      : t(
                                            `cms.review.blocked.${review.submit_blocker}`,
                                        )
                            }
                        />
                    )}
                </>
            )}
        </section>
    );
}

function OpenReview({
    lessonSlug,
    lessonTitle,
    hasPublishedVersion,
    review,
    can,
}: {
    lessonSlug: string;
    lessonTitle: string;
    hasPublishedVersion: boolean;
    review: NonNullable<ReviewState['open']>;
    can: ReviewPermissions;
}) {
    const [returning, setReturning] = useState(false);
    const [withdrawing, setWithdrawing] = useState(false);
    const [processing, setProcessing] = useState(false);

    const withdraw = () =>
        router.delete(withdrawRoute.url(lessonSlug), {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setWithdrawing(false);
            },
        });

    return (
        <div className="space-y-3 rounded-lg border bg-muted/40 p-3 text-sm">
            <p className="flex items-center gap-2 font-medium">
                <ClipboardCheck
                    className="size-4 shrink-0 text-state-in-progress"
                    aria-hidden="true"
                />
                {t('cms.review.inReview')}
            </p>
            <p className="text-muted-foreground">
                {review.submitted_by === null
                    ? t('cms.review.submittedOn', {
                          date: formatDate(review.submitted_at),
                      })
                    : t('cms.review.submittedBy', {
                          user: review.submitted_by,
                          date: formatDate(review.submitted_at),
                      })}
            </p>
            {review.note !== null && (
                <blockquote className="border-l-2 pl-3 whitespace-pre-line">
                    {review.note}
                </blockquote>
            )}
            {review.edited_since && (
                <p className="text-muted-foreground">
                    {t('cms.review.editedSince')}
                </p>
            )}
            {hasPublishedVersion && (
                <Link
                    href={changes(lessonSlug)}
                    className="inline-block font-medium underline-offset-4 hover:underline"
                >
                    {t('cms.review.viewChanges')}
                </Link>
            )}
            {(can.return || can.withdraw) && (
                <div className="flex flex-wrap gap-2">
                    {can.return && (
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            onClick={() => setReturning(true)}
                        >
                            {t('cms.review.return')}
                        </Button>
                    )}
                    {can.withdraw && (
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            onClick={() => setWithdrawing(true)}
                        >
                            {t('cms.review.withdraw')}
                        </Button>
                    )}
                </div>
            )}

            <ReturnDialog
                open={returning}
                lessonSlug={lessonSlug}
                lessonTitle={lessonTitle}
                onClose={() => setReturning(false)}
            />
            <ConfirmDialog
                open={withdrawing}
                title={t('cms.review.withdrawTitle', { lesson: lessonTitle })}
                description={t('cms.review.withdrawDescription')}
                confirmLabel={t('cms.review.withdraw')}
                processing={processing}
                onConfirm={withdraw}
                onCancel={() => setWithdrawing(false)}
            />
        </div>
    );
}

function SubmitForm({
    lessonSlug,
    blocker,
}: {
    lessonSlug: string;
    blocker: string | null;
}) {
    const id = useId();
    const form = useForm({ note: '' });
    const errors = form.errors as Partial<Record<string, string>>;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(submitRoute.url(lessonSlug), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <form onSubmit={submit} className="space-y-3">
            <div className="grid gap-2">
                <Label htmlFor={id}>{t('cms.review.noteLabel')}</Label>
                <Textarea
                    id={id}
                    name="note"
                    value={form.data.note}
                    maxLength={1000}
                    rows={3}
                    aria-describedby={`${id}-help`}
                    aria-invalid={form.errors.note !== undefined}
                    onChange={(event) =>
                        form.setData('note', event.target.value)
                    }
                />
                <p id={`${id}-help`} className="text-xs text-muted-foreground">
                    {t('cms.review.noteHelp')}
                </p>
                <InputError message={form.errors.note} />
            </div>
            <Button
                type="submit"
                className="w-full"
                disabled={blocker !== null || form.processing}
            >
                <Send aria-hidden="true" />
                {form.processing
                    ? t('cms.review.submitting')
                    : t('cms.review.submit')}
            </Button>
            <p className="text-xs text-muted-foreground">
                {blocker ?? t('cms.review.submitHelp')}
            </p>
            <InputError message={errors.review} />
        </form>
    );
}

function ReturnDialog({
    open,
    lessonSlug,
    lessonTitle,
    onClose,
}: {
    open: boolean;
    lessonSlug: string;
    lessonTitle: string;
    onClose: () => void;
}) {
    const id = useId();
    const form = useForm({ comment: '' });
    const errors = form.errors as Partial<Record<string, string>>;

    const close = () => {
        form.reset();
        form.clearErrors();
        onClose();
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(returnRoute.url(lessonSlug), {
            preserveScroll: true,
            onSuccess: close,
        });
    };

    return (
        <Dialog open={open} onOpenChange={(next) => !next && close()}>
            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>
                            {t('cms.review.returnTitle', {
                                lesson: lessonTitle,
                            })}
                        </DialogTitle>
                        <DialogDescription>
                            {t('cms.review.returnDescription')}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-2">
                        <Label htmlFor={id}>
                            {t('cms.review.commentLabel')}
                        </Label>
                        <Textarea
                            id={id}
                            name="comment"
                            value={form.data.comment}
                            maxLength={2000}
                            rows={5}
                            required
                            aria-invalid={form.errors.comment !== undefined}
                            onChange={(event) =>
                                form.setData('comment', event.target.value)
                            }
                        />
                        <InputError
                            message={form.errors.comment ?? errors.review}
                        />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={close}>
                            {t('common.cancel')}
                        </Button>
                        <Button
                            type="submit"
                            disabled={
                                form.processing ||
                                form.data.comment.trim() === ''
                            }
                        >
                            {form.processing
                                ? t('cms.review.returning')
                                : t('cms.review.return')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
