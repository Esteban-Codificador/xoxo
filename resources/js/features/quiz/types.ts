import type { RichContent } from '@/features/rich-content';
import type { QuestionType } from '@/types/enums';

/** An option, item or side of a pair; the id is a hash of its text. */
export type Choice = { id: string; text: string };

/**
 * What the learner answers, per type (QuestionTypeHandler::answer).
 * Only the key of the question's type is present.
 */
export type Answer = {
    choice?: string;
    choices?: string[];
    value?: boolean;
    order?: string[];
    matches?: Record<string, string>;
};

type Graded = {
    /** Present once the attempt is graded. */
    answer?: Answer | null;
    correct?: boolean;
    points_awarded?: number;
    explanation?: RichContent;
    /** The right answer; null while the server keeps it hidden. */
    solution?: Answer | null;
};

type Base = { id: number; prompt: RichContent; points: number } & Graded;

/** A question as the server presents it: never with the answer while open. */
export type SheetQuestion = Base &
    (
        | {
              type: Extract<QuestionType, 'SINGLE_CHOICE' | 'MULTIPLE_CHOICE'>;
              content: { options: Choice[] };
          }
        | { type: Extract<QuestionType, 'TRUE_FALSE'>; content: unknown }
        | {
              type: Extract<QuestionType, 'ORDERING'>;
              content: { items: Choice[] };
          }
        | {
              type: Extract<QuestionType, 'MATCHING'>;
              content: { left: Choice[]; right: Choice[] };
          }
    );

/** The rules of a quiz (ShowQuizController::quiz). */
export type QuizInfo = {
    title: string;
    description: string | null;
    questions: number;
    pass_threshold: number;
    mastery_threshold: number;
    time_limit_seconds: number | null;
    max_attempts: number | null;
};

/** Whether an answer counts as given; ordering always has one. */
export function isAnswered(answer: Answer | undefined): boolean {
    if (answer === undefined) {
        return false;
    }

    return (
        answer.choice !== undefined ||
        (answer.choices?.length ?? 0) > 0 ||
        answer.value !== undefined ||
        (answer.order?.length ?? 0) > 0 ||
        Object.keys(answer.matches ?? {}).length > 0
    );
}
