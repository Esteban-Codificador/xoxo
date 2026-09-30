import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { t } from '@/i18n';
import { quiz as quizRoute } from '@/routes/lessons';
import { AttemptBadge, QuizFacts } from './quiz-facts';
import type { QuizInfo } from './types';

export type LessonQuizSummary = QuizInfo & {
    best_score: number | null;
    passed: boolean;
    attempts_left: number | null;
};

/** The quiz of a lesson on its page: what it asks and how the learner did. */
export function LessonQuiz({
    lessonSlug,
    quiz,
}: {
    lessonSlug: string;
    quiz: LessonQuizSummary;
}) {
    return (
        <section aria-labelledby="quiz" className="max-w-[72ch] space-y-3">
            <h2 id="quiz" className="text-lg font-semibold">
                {t('quiz.section')}
            </h2>
            <div className="space-y-4 rounded-xl border p-5">
                <div className="space-y-1">
                    <p className="font-medium">{quiz.title}</p>
                    <p className="text-sm text-muted-foreground">
                        {t('quiz.cardDescription', {
                            mastery: quiz.mastery_threshold,
                        })}
                    </p>
                </div>
                <QuizFacts quiz={quiz} />
                <div className="flex flex-wrap items-center justify-between gap-3 border-t pt-4">
                    <p className="flex flex-wrap items-center gap-2 text-sm">
                        {quiz.best_score === null ? (
                            <span className="text-muted-foreground">
                                {t('quiz.notTried')}
                            </span>
                        ) : (
                            <>
                                {t('quiz.bestScore', {
                                    score: quiz.best_score,
                                })}
                                <AttemptBadge
                                    passed={quiz.passed}
                                    timedOut={false}
                                />
                            </>
                        )}
                    </p>
                    <Button asChild size="sm" variant="outline">
                        <Link href={quizRoute(lessonSlug)}>
                            {t('quiz.goToQuiz')}
                            <ArrowRight aria-hidden="true" />
                        </Link>
                    </Button>
                </div>
            </div>
        </section>
    );
}
