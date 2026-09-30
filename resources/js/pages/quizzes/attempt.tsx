import { Head, Link, setLayoutProps, useForm } from '@inertiajs/react';
import { ArrowLeft, Award, Send } from 'lucide-react';
import { useCallback, useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { Button } from '@/components/ui/button';
import { QuestionInput } from '@/features/quiz/question-input';
import {
    QuestionReview,
    QuestionVerdict,
} from '@/features/quiz/question-review';
import { QuizTimer } from '@/features/quiz/quiz-timer';
import { StartAttempt } from '@/features/quiz/start-attempt';
import { isAnswered } from '@/features/quiz/types';
import type { Answer, QuizInfo, SheetQuestion } from '@/features/quiz/types';
import { RichContentRenderer } from '@/features/rich-content';
import type { MediaMap } from '@/features/rich-content';
import { t } from '@/i18n';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { quiz as quizRoute, show as showLesson } from '@/routes/lessons';
import { show as showAttempt, update } from '@/routes/quiz-attempts';
import type { ProgressStatus } from '@/types/enums';

type Attempt = {
    id: number;
    number: number;
    open: boolean;
    /** Null without a time limit or once graded. */
    seconds_left: number | null;
    submitted_at: string | null;
    score: number | null;
    points_earned: number | null;
    points_total: number | null;
    passed: boolean | null;
    timed_out: boolean;
    /** Passed at the mastery score. */
    masters: boolean;
};

type Props = {
    lesson: { slug: string; title: string };
    quiz: QuizInfo;
    attempt: Attempt;
    questions: SheetQuestion[];
    media: MediaMap;
    /** The right answers are included (passed, or no attempts left). */
    revealed: boolean;
    attempts_left: number | null;
    can_retry: boolean;
    lesson_status: ProgressStatus | null;
};

export default function QuizAttemptPage(props: Props) {
    const { lesson, quiz, attempt } = props;

    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.dashboard'), href: dashboard() },
            { title: lesson.title, href: showLesson(lesson.slug) },
            { title: t('quiz.section'), href: quizRoute(lesson.slug) },
            {
                title: t('quiz.attempt', { number: attempt.number }),
                href: showAttempt(attempt.id),
            },
        ],
    });

    return (
        <>
            <Head
                title={t('quiz.attemptHead', {
                    number: attempt.number,
                    title: quiz.title,
                })}
            />
            <div className="mx-auto w-full max-w-3xl space-y-8 px-4 py-8 md:px-6">
                {attempt.open ? (
                    <OpenAttempt {...props} />
                ) : (
                    <GradedAttempt {...props} />
                )}
            </div>
        </>
    );
}

function OpenAttempt({ quiz, attempt, questions, media }: Props) {
    const form = useForm<{ answers: Record<number, Answer> }>({ answers: {} });
    const [confirming, setConfirming] = useState(false);
    const answered = questions.filter((question) =>
        isAnswered(form.data.answers[question.id]),
    ).length;

    const send = useCallback(() => {
        setConfirming(false);
        form.put(update.url(attempt.id));
    }, [form, attempt.id]);

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();

                if (answered < questions.length) {
                    setConfirming(true);
                } else {
                    send();
                }
            }}
            className="space-y-8"
        >
            <header className="space-y-3">
                <p className="text-sm text-muted-foreground">
                    {t('quiz.attempt', { number: attempt.number })}
                </p>
                <h1 className="text-3xl font-semibold tracking-tight text-balance">
                    {quiz.title}
                </h1>
            </header>

            {/* Sticky, so the time and the count stay in view while scrolling. */}
            <div className="sticky top-0 z-10 -mx-4 flex flex-wrap items-center justify-between gap-3 border-b bg-background/95 px-4 py-3 backdrop-blur md:-mx-6 md:px-6">
                <p className="text-sm text-muted-foreground" aria-live="polite">
                    {t('quiz.answered', {
                        answered,
                        total: questions.length,
                    })}
                </p>
                {attempt.seconds_left !== null && (
                    <QuizTimer seconds={attempt.seconds_left} onExpire={send} />
                )}
            </div>

            <ol className="space-y-6">
                {questions.map((question, index) => (
                    <li key={question.id}>
                        <fieldset
                            className="space-y-4 rounded-xl border p-5"
                            aria-describedby={`question-${question.id}-hint`}
                        >
                            <legend className="sr-only">
                                {t('quiz.questionOf', {
                                    number: index + 1,
                                    total: questions.length,
                                })}
                            </legend>
                            <p
                                className="flex flex-wrap justify-between gap-2 text-xs text-muted-foreground"
                                aria-hidden="true"
                            >
                                <span>
                                    {t('quiz.questionOf', {
                                        number: index + 1,
                                        total: questions.length,
                                    })}
                                </span>
                                <span>
                                    {t('quiz.points', {
                                        count: question.points,
                                    })}
                                </span>
                            </p>
                            <RichContentRenderer
                                content={question.prompt}
                                media={media}
                            />
                            <p
                                id={`question-${question.id}-hint`}
                                className="text-sm font-medium text-muted-foreground"
                            >
                                {t(`quiz.types.${question.type}`)}
                            </p>
                            <QuestionInput
                                question={question}
                                value={form.data.answers[question.id]}
                                disabled={form.processing}
                                onChange={(answer) =>
                                    form.setData('answers', {
                                        ...form.data.answers,
                                        [question.id]: answer,
                                    })
                                }
                            />
                        </fieldset>
                    </li>
                ))}
            </ol>

            <div className="flex flex-wrap items-center justify-end gap-3 border-t pt-6">
                <Button type="submit" disabled={form.processing}>
                    <Send aria-hidden="true" />
                    {form.processing ? t('quiz.submitting') : t('quiz.submit')}
                </Button>
            </div>

            <ConfirmDialog
                open={confirming}
                title={t('quiz.confirmTitle')}
                description={t('quiz.confirmDescription', {
                    count: questions.length - answered,
                })}
                confirmLabel={t('quiz.confirmSubmit')}
                processing={form.processing}
                onConfirm={send}
                onCancel={() => setConfirming(false)}
            />
        </form>
    );
}

function GradedAttempt({
    lesson,
    quiz,
    attempt,
    questions,
    media,
    revealed,
    attempts_left,
    can_retry,
    lesson_status,
}: Props) {
    const missed = questions.filter((question) => !question.correct).length;
    const passed = attempt.passed === true;

    return (
        <>
            <section
                aria-labelledby="result"
                className={cn(
                    'space-y-4 rounded-xl border p-5',
                    passed
                        ? 'border-state-completed/40 bg-state-completed-soft'
                        : 'border-destructive/30 bg-destructive/5',
                )}
            >
                <p className="text-sm text-muted-foreground">
                    {quiz.title} ·{' '}
                    {t('quiz.attempt', { number: attempt.number })}
                </p>
                <h1
                    id="result"
                    className={cn(
                        'text-2xl font-semibold tracking-tight',
                        passed ? 'text-state-completed' : 'text-destructive',
                    )}
                >
                    {attempt.timed_out
                        ? t('quiz.result.timedOut')
                        : passed
                          ? t('quiz.result.passed', {
                                score: attempt.score ?? 0,
                            })
                          : t('quiz.result.failed', {
                                score: attempt.score ?? 0,
                            })}
                </h1>
                <p className="text-sm">
                    {t('quiz.result.points', {
                        earned: attempt.points_earned ?? 0,
                        total: attempt.points_total ?? 0,
                    })}
                    {' · '}
                    {t('quiz.result.passLine', {
                        percent: quiz.pass_threshold,
                    })}
                </p>
                {attempt.masters && (
                    <p className="flex items-center gap-2 text-sm font-medium text-state-mastered">
                        <Award className="size-4 shrink-0" aria-hidden="true" />
                        {lesson_status === 'MASTERED'
                            ? t('quiz.result.mastered')
                            : t('quiz.result.completeToMaster')}
                    </p>
                )}
                <div className="flex flex-wrap items-center gap-3 pt-1">
                    {can_retry && (
                        <StartAttempt lessonSlug={lesson.slug} retry />
                    )}
                    <Button asChild variant="outline">
                        <Link href={showLesson(lesson.slug)}>
                            <ArrowLeft aria-hidden="true" />
                            {t('quiz.backToLesson')}
                        </Link>
                    </Button>
                    <Link
                        href={quizRoute(lesson.slug)}
                        className="text-sm underline-offset-4 hover:underline"
                    >
                        {t('quiz.allAttempts')}
                    </Link>
                </div>
                {attempts_left !== null && (
                    <p className="text-sm text-muted-foreground">
                        {attempts_left === 0
                            ? t('quiz.noAttemptsLeft')
                            : t('quiz.attemptsLeft', { count: attempts_left })}
                    </p>
                )}
            </section>

            <section aria-labelledby="review" className="space-y-4">
                <h2 id="review" className="font-medium">
                    {missed === 0
                        ? t('quiz.result.allCorrect')
                        : t('quiz.result.failedCount', { count: missed })}
                </h2>
                {!revealed && missed > 0 && (
                    <p className="rounded-lg border bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
                        {t('quiz.result.hidden')}
                    </p>
                )}
                <ol className="space-y-6">
                    {questions.map((question, index) => (
                        <li
                            key={question.id}
                            className="space-y-4 rounded-xl border p-5"
                        >
                            <p className="flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
                                <span>
                                    {t('quiz.questionOf', {
                                        number: index + 1,
                                        total: questions.length,
                                    })}
                                </span>
                                <span className="flex items-center gap-2">
                                    {t('quiz.points', {
                                        count: `${question.points_awarded ?? 0}/${question.points}`,
                                    })}
                                    <QuestionVerdict question={question} />
                                </span>
                            </p>
                            <RichContentRenderer
                                content={question.prompt}
                                media={media}
                            />
                            <QuestionReview question={question} />
                            {question.explanation && (
                                <div className="space-y-2 rounded-lg bg-muted/40 p-4">
                                    <p className="text-sm font-medium">
                                        {t('quiz.result.explanation')}
                                    </p>
                                    <RichContentRenderer
                                        content={question.explanation}
                                        media={media}
                                    />
                                </div>
                            )}
                        </li>
                    ))}
                </ol>
            </section>
        </>
    );
}
