import { Form, Link } from '@inertiajs/react';
import { ArrowRight, Award, CircleCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { t } from '@/i18n';
import { formatDate } from '@/lib/format';
import { complete, show as showLesson, uncomplete } from '@/routes/lessons';
import type { LessonProgress } from './types';

type Props = {
    lessonSlug: string;
    progress: LessonProgress;
    next: { slug: string; title: string } | null;
};

/**
 * The end-of-lesson action. Whether the lesson is done, and whether the
 * learner may mark it, both come from the server.
 */
export function CompleteLesson({ lessonSlug, progress, next }: Props) {
    const done =
        progress.state === 'COMPLETED' || progress.state === 'MASTERED';

    if (done) {
        return (
            <section
                aria-live="polite"
                className="flex flex-col gap-4 rounded-xl border border-state-completed/40 bg-state-completed-soft p-5"
            >
                <p className="flex items-center gap-2 font-medium text-state-completed">
                    <CircleCheck
                        className="size-5 shrink-0"
                        aria-hidden="true"
                    />
                    {progress.completed_at
                        ? t('progress.completedOn', {
                              date: formatDate(progress.completed_at),
                          })
                        : t('states.COMPLETED')}
                </p>
                {progress.state === 'MASTERED' && (
                    <p className="flex items-center gap-2 text-sm font-medium text-state-mastered">
                        <Award className="size-4 shrink-0" aria-hidden="true" />
                        {t('progress.mastered')}
                    </p>
                )}
                <div className="flex flex-wrap items-center gap-2">
                    {progress.state === 'COMPLETED' &&
                        progress.can_progress && (
                            <Form
                                {...uncomplete.form(lessonSlug)}
                                options={{ preserveScroll: true }}
                            >
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        variant="ghost"
                                        size="sm"
                                        disabled={processing}
                                    >
                                        {processing
                                            ? t('progress.saving')
                                            : t('progress.uncomplete')}
                                    </Button>
                                )}
                            </Form>
                        )}
                    {next && (
                        // Short label: the pager below already shows the
                        // full title, which can be long enough to overflow.
                        <Button asChild size="sm">
                            <Link
                                href={showLesson(next.slug)}
                                aria-label={t('progress.nextUp', {
                                    lesson: next.title,
                                })}
                            >
                                {t('progress.nextLesson')}
                                <ArrowRight aria-hidden="true" />
                            </Link>
                        </Button>
                    )}
                </div>
            </section>
        );
    }

    return (
        <section className="flex flex-col gap-4 rounded-xl border p-5 sm:flex-row sm:items-center sm:justify-between">
            <div className="space-y-1">
                <h2 className="font-medium">{t('progress.finishTitle')}</h2>
                <p className="text-sm text-muted-foreground">
                    {t('progress.finishDescription')}
                </p>
            </div>
            <Form
                {...complete.form(lessonSlug)}
                options={{ preserveScroll: true }}
            >
                {({ processing }) => (
                    <Button
                        type="submit"
                        disabled={processing || !progress.can_progress}
                        className="shrink-0"
                    >
                        <CircleCheck aria-hidden="true" />
                        {processing
                            ? t('progress.saving')
                            : t('progress.complete')}
                    </Button>
                )}
            </Form>
        </section>
    );
}
