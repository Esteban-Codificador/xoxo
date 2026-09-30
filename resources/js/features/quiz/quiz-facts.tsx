import { Award, Clock, ListChecks, RotateCcw, Target } from 'lucide-react';
import { t } from '@/i18n';
import { formatMinutes } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { QuizInfo } from './types';

/** The rules of a quiz in one line each: what it takes and what it allows. */
export function QuizFacts({
    quiz,
    className,
}: {
    quiz: QuizInfo;
    className?: string;
}) {
    const facts = [
        {
            icon: ListChecks,
            text: t('quiz.questions', { count: quiz.questions }),
        },
        {
            icon: Target,
            text: t('quiz.passWith', { percent: quiz.pass_threshold }),
        },
        {
            icon: Award,
            text: t('quiz.masterWith', { percent: quiz.mastery_threshold }),
        },
        {
            icon: Clock,
            text:
                quiz.time_limit_seconds === null
                    ? t('quiz.noTimeLimit')
                    : t('quiz.timeLimit', {
                          time: formatMinutes(
                              Math.round(quiz.time_limit_seconds / 60),
                          ),
                      }),
        },
        {
            icon: RotateCcw,
            text:
                quiz.max_attempts === null
                    ? t('quiz.unlimitedAttempts')
                    : t('quiz.maxAttempts', { count: quiz.max_attempts }),
        },
    ];

    return (
        <ul
            className={cn(
                'flex flex-wrap gap-x-5 gap-y-2 text-sm text-muted-foreground',
                className,
            )}
        >
            {facts.map(({ icon: Icon, text }) => (
                <li key={text} className="flex items-center gap-1.5">
                    <Icon className="size-4" aria-hidden="true" />
                    {text}
                </li>
            ))}
        </ul>
    );
}

/** Passed, failed or out of time: an icon-free word with the state color. */
export function AttemptBadge({
    passed,
    timedOut,
}: {
    passed: boolean;
    timedOut: boolean;
}) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium whitespace-nowrap',
                passed
                    ? 'bg-state-completed-soft text-state-completed'
                    : 'bg-destructive/10 text-destructive',
            )}
        >
            {passed
                ? t('quiz.passed')
                : timedOut
                  ? t('quiz.timedOut')
                  : t('quiz.failed')}
        </span>
    );
}
