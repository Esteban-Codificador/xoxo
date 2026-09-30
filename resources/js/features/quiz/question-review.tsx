import { CircleCheck, CircleMinus, CircleX } from 'lucide-react';
import { t } from '@/i18n';
import { cn } from '@/lib/utils';
import { InlineCode } from './inline-code';
import type { Answer, Choice, SheetQuestion } from './types';

/**
 * How a graded question went: whether it was right, what the learner
 * answered and, when the server reveals it, the right answer. Right and
 * wrong always carry an icon and a word, never only a color.
 */
export function QuestionVerdict({ question }: { question: SheetQuestion }) {
    const unanswered =
        question.answer === null || question.answer === undefined;
    const Icon = question.correct
        ? CircleCheck
        : unanswered
          ? CircleMinus
          : CircleX;

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium',
                question.correct
                    ? 'bg-state-completed-soft text-state-completed'
                    : 'bg-destructive/10 text-destructive',
            )}
        >
            <Icon className="size-3.5" aria-hidden="true" />
            {question.correct
                ? t('quiz.result.correct')
                : unanswered
                  ? t('quiz.result.unanswered')
                  : t('quiz.result.incorrect')}
        </span>
    );
}

export function QuestionReview({ question }: { question: SheetQuestion }) {
    const answer = question.answer ?? null;
    const solution = question.solution ?? null;

    switch (question.type) {
        case 'SINGLE_CHOICE':
        case 'MULTIPLE_CHOICE': {
            const chosen = new Set(
                answer?.choices ?? (answer?.choice ? [answer.choice] : []),
            );
            const right = new Set(
                solution?.choices ??
                    (solution?.choice ? [solution.choice] : []),
            );

            // Whether a marked option was right is known when the answer is
            // revealed, when the whole question was right, or when it was a
            // single choice (then the one marked was wrong).
            const verdict = (id: string): boolean | null => {
                if (solution !== null) {
                    return right.has(id);
                }

                return question.correct || question.type === 'SINGLE_CHOICE'
                    ? Boolean(question.correct)
                    : null;
            };

            return (
                <ul className="grid gap-2">
                    {question.content.options.map((choice) => (
                        <li
                            key={choice.id}
                            className={cn(
                                'flex items-start justify-between gap-3 rounded-lg border px-4 py-3 text-sm',
                                chosen.has(choice.id) &&
                                    {
                                        true: 'border-state-completed/60',
                                        false: 'border-destructive/60',
                                        null: 'border-foreground/40',
                                    }[String(verdict(choice.id))],
                                right.has(choice.id) &&
                                    'bg-state-completed-soft',
                            )}
                        >
                            <span>
                                <InlineCode text={choice.text} />
                            </span>
                            <span className="flex shrink-0 flex-col items-end gap-1 text-xs">
                                {chosen.has(choice.id) && (
                                    <span className="font-medium">
                                        {t('quiz.result.yourAnswer')}
                                    </span>
                                )}
                                {right.has(choice.id) && (
                                    <span className="font-medium text-state-completed">
                                        {t('quiz.result.rightAnswer')}
                                    </span>
                                )}
                            </span>
                        </li>
                    ))}
                </ul>
            );
        }
        case 'TRUE_FALSE':
            return (
                <dl className="grid gap-1 text-sm">
                    <Row
                        label={t('quiz.result.yourAnswer')}
                        value={
                            answer?.value === undefined
                                ? t('quiz.result.none')
                                : t(answer.value ? 'quiz.true' : 'quiz.false')
                        }
                    />
                    {solution?.value !== undefined && (
                        <Row
                            label={t('quiz.result.rightAnswer')}
                            value={t(
                                solution.value ? 'quiz.true' : 'quiz.false',
                            )}
                        />
                    )}
                </dl>
            );
        case 'ORDERING':
            return (
                <div className="grid gap-4 sm:grid-cols-2">
                    <OrderList
                        label={t('quiz.result.yourOrder')}
                        items={question.content.items}
                        order={answer?.order}
                    />
                    {solution?.order && (
                        <OrderList
                            label={t('quiz.result.rightOrder')}
                            items={question.content.items}
                            order={solution.order}
                        />
                    )}
                </div>
            );
        case 'MATCHING': {
            const rights = new Map(
                question.content.right.map((choice) => [choice.id, choice]),
            );

            return (
                <ul className="grid gap-2 text-sm">
                    {question.content.left.map((left) => {
                        const given = rights.get(
                            answer?.matches?.[left.id] ?? '',
                        );
                        const expected = rights.get(
                            solution?.matches?.[left.id] ?? '',
                        );

                        return (
                            <li
                                key={left.id}
                                className="grid gap-1 rounded-lg border px-4 py-3 sm:grid-cols-3"
                            >
                                <span className="font-medium">
                                    <InlineCode text={left.text} />
                                </span>
                                <span>
                                    <span className="block text-xs text-muted-foreground">
                                        {t('quiz.result.yourAnswer')}
                                    </span>
                                    {given ? (
                                        <InlineCode text={given.text} />
                                    ) : (
                                        <span className="text-muted-foreground">
                                            {t('quiz.result.none')}
                                        </span>
                                    )}
                                </span>
                                {expected && (
                                    <span>
                                        <span className="block text-xs text-muted-foreground">
                                            {t('quiz.result.rightAnswer')}
                                        </span>
                                        <span className="text-state-completed">
                                            <InlineCode text={expected.text} />
                                        </span>
                                    </span>
                                )}
                            </li>
                        );
                    })}
                </ul>
            );
        }
    }
}

function Row({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex gap-2">
            <dt className="text-muted-foreground">{label}:</dt>
            <dd className="font-medium">{value}</dd>
        </div>
    );
}

function OrderList({
    label,
    items,
    order,
}: {
    label: string;
    items: Choice[];
    order: Answer['order'];
}) {
    const byId = new Map(items.map((item) => [item.id, item]));

    return (
        <div className="space-y-2">
            <p className="text-sm font-medium">{label}</p>
            {order === undefined ? (
                <p className="text-sm text-muted-foreground">
                    {t('quiz.result.none')}
                </p>
            ) : (
                <ol className="list-decimal space-y-1 pl-5 text-sm">
                    {order.map((id) => (
                        <li key={id}>
                            <InlineCode text={byId.get(id)?.text ?? id} />
                        </li>
                    ))}
                </ol>
            )}
        </div>
    );
}
