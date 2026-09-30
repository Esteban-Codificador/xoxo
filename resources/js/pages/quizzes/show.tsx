import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Lock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { AttemptBadge, QuizFacts } from '@/features/quiz/quiz-facts';
import { StartAttempt } from '@/features/quiz/start-attempt';
import type { QuizInfo } from '@/features/quiz/types';
import { t } from '@/i18n';
import { formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes';
import { quiz as quizRoute, show as showLesson } from '@/routes/lessons';
import { show as showAttempt } from '@/routes/quiz-attempts';
import { show as showTrack } from '@/routes/tracks';
import type { ProgressStatus } from '@/types/enums';

type Props = {
    roadmap: { slug: string };
    track: { slug: string; title: string };
    lesson: { slug: string; title: string };
    quiz: QuizInfo;
    /** Graded attempts, newest first. */
    attempts: {
        id: number;
        number: number;
        score: number;
        passed: boolean;
        timed_out: boolean;
        submitted_at: string;
    }[];
    open_attempt: { id: number; seconds_left: number | null } | null;
    best_score: number | null;
    passed: boolean;
    attempts_left: number | null;
    lesson_status: ProgressStatus | null;
    /** Why the learner cannot start (a STRICT roadmap, a LOCKED lesson). */
    blocked: string | null;
};

export default function QuizShow({
    roadmap,
    track,
    lesson,
    quiz,
    attempts,
    open_attempt,
    best_score,
    passed,
    attempts_left,
    blocked,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.dashboard'), href: dashboard() },
            {
                title: track.title,
                href: showTrack({ roadmap: roadmap.slug, track: track.slug }),
            },
            { title: lesson.title, href: showLesson(lesson.slug) },
            { title: t('quiz.section'), href: quizRoute(lesson.slug) },
        ],
    });

    return (
        <>
            <Head title={t('quiz.head', { title: quiz.title })} />

            <div className="mx-auto w-full max-w-3xl space-y-8 px-4 py-8 md:px-6">
                <header className="space-y-4">
                    <Link
                        href={showLesson(lesson.slug)}
                        className="inline-flex items-center gap-1.5 text-sm text-muted-foreground underline-offset-4 hover:underline"
                    >
                        <ArrowLeft className="size-4" aria-hidden="true" />
                        {lesson.title}
                    </Link>
                    <h1 className="text-3xl font-semibold tracking-tight text-balance">
                        {quiz.title}
                    </h1>
                    {quiz.description && (
                        <p className="text-lg text-muted-foreground">
                            {quiz.description}
                        </p>
                    )}
                    <QuizFacts quiz={quiz} />
                </header>

                <section
                    aria-live="polite"
                    className="flex flex-col gap-4 rounded-xl border p-5 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div className="space-y-1 text-sm">
                        {best_score === null ? (
                            <p className="text-muted-foreground">
                                {t('quiz.notTried')}
                            </p>
                        ) : (
                            <p className="flex flex-wrap items-center gap-2 font-medium">
                                {t('quiz.bestScore', { score: best_score })}
                                <AttemptBadge
                                    passed={passed}
                                    timedOut={false}
                                />
                            </p>
                        )}
                        {attempts_left !== null && (
                            <p className="text-muted-foreground">
                                {attempts_left === 0
                                    ? t('quiz.noAttemptsLeft')
                                    : t('quiz.attemptsLeft', {
                                          count: attempts_left,
                                      })}
                            </p>
                        )}
                    </div>
                    {open_attempt !== null ? (
                        <Button asChild>
                            <Link href={showAttempt(open_attempt.id)}>
                                {t('quiz.resume')}
                                <ArrowRight aria-hidden="true" />
                            </Link>
                        </Button>
                    ) : blocked !== null ? (
                        <p className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Lock
                                className="size-4 shrink-0"
                                aria-hidden="true"
                            />
                            {blocked}
                        </p>
                    ) : (
                        attempts_left !== 0 && (
                            <StartAttempt
                                lessonSlug={lesson.slug}
                                retry={attempts.length > 0}
                            />
                        )
                    )}
                </section>

                <section aria-labelledby="rules" className="space-y-2">
                    <h2 id="rules" className="font-medium">
                        {t('quiz.rules')}
                    </h2>
                    <ul className="list-disc space-y-1 pl-5 text-sm text-muted-foreground">
                        {quiz.time_limit_seconds !== null && (
                            <li>{t('quiz.ruleServerTime')}</li>
                        )}
                        <li>{t('quiz.ruleResume')}</li>
                        <li>{t('quiz.ruleReveal')}</li>
                    </ul>
                </section>

                {attempts.length > 0 && (
                    <section aria-labelledby="history" className="space-y-3">
                        <h2 id="history" className="font-medium">
                            {t('quiz.history')}
                        </h2>
                        <ul className="divide-y rounded-xl border">
                            {attempts.map((attempt) => (
                                <li
                                    key={attempt.id}
                                    className="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm"
                                >
                                    <div className="space-y-0.5">
                                        <p className="font-medium">
                                            {t('quiz.attempt', {
                                                number: attempt.number,
                                            })}
                                            {` · ${attempt.score}\u00a0%`}
                                        </p>
                                        <p className="text-muted-foreground">
                                            {formatDateTime(
                                                attempt.submitted_at,
                                            )}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <AttemptBadge
                                            passed={attempt.passed}
                                            timedOut={attempt.timed_out}
                                        />
                                        <Link
                                            href={showAttempt(attempt.id)}
                                            className="underline-offset-4 hover:underline"
                                        >
                                            {t('quiz.review')}
                                        </Link>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </>
    );
}
