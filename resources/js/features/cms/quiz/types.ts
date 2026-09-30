import type { RichContent } from '@/features/rich-content';
import type { Difficulty, QuestionType } from '@/types/enums';

/**
 * A question's answer data as the author writes it (the server's payload):
 * options with the right ones marked, items in the right order, or pairs.
 */
export type Payload = {
    options?: { text: string; correct: boolean }[];
    answer?: boolean;
    items?: string[];
    pairs?: { left: string; right: string }[];
};

export type QuestionDraft = {
    /** Stable React key; saved questions also carry their id. */
    key: string;
    id: number | null;
    type: QuestionType;
    prompt: RichContent;
    payload: Payload;
    explanation: RichContent;
    difficulty: Difficulty | null;
    points: number;
};

export const EMPTY_DOCUMENT: RichContent = {
    version: 1,
    doc: { type: 'doc', content: [{ type: 'paragraph' }] },
};

let counter = 0;

export function newKey(): string {
    counter += 1;

    return `new-${counter}`;
}

/**
 * The starting payload of a type. Switching between single and multiple
 * choice keeps the options; any other change starts over.
 */
export function payloadFor(type: QuestionType, previous?: Payload): Payload {
    switch (type) {
        case 'SINGLE_CHOICE':
        case 'MULTIPLE_CHOICE':
            return {
                options: previous?.options ?? [
                    { text: '', correct: false },
                    { text: '', correct: false },
                    { text: '', correct: false },
                ],
            };
        case 'TRUE_FALSE':
            return { answer: true };
        case 'ORDERING':
            return { items: ['', '', ''] };
        case 'MATCHING':
            return {
                pairs: [
                    { left: '', right: '' },
                    { left: '', right: '' },
                    { left: '', right: '' },
                ],
            };
    }
}

export function newQuestion(type: QuestionType): QuestionDraft {
    return {
        key: newKey(),
        id: null,
        type,
        prompt: EMPTY_DOCUMENT,
        payload: payloadFor(type),
        explanation: EMPTY_DOCUMENT,
        difficulty: null,
        points: 1,
    };
}
