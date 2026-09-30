import {
    ArrowDown,
    ArrowUp,
    ChevronDown,
    CircleAlert,
    Trash2,
} from 'lucide-react';
import { lazy, Suspense, useId } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Field } from '@/features/cms/field';
import { textOf } from '@/features/rich-content';
import type { MediaMap } from '@/features/rich-content';
import { t } from '@/i18n';
import { cn } from '@/lib/utils';
import type { Difficulty, QuestionType } from '@/types/enums';
import {
    Difficulty as Difficulties,
    QuestionType as QuestionTypes,
} from '@/types/enums';
import { PayloadEditor } from './payload-editor';
import type { QuestionDraft } from './types';
import { payloadFor } from './types';

const RichContentEditor = lazy(
    () => import('@/features/rich-content-editor/rich-content-editor'),
);

export const selectClass =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

type Props = {
    question: QuestionDraft;
    number: number;
    total: number;
    open: boolean;
    media: MediaMap;
    disabled: boolean;
    /** Server errors by path inside this question ("payload.options.0.text"). */
    errorFor: (path: string) => string | undefined;
    hasErrors: boolean;
    onToggle: () => void;
    onChange: (question: QuestionDraft) => void;
    onMove: (offset: number) => void;
    onRemove: () => void;
};

/**
 * One question of the quiz editor. Closed, it is a line with its number,
 * type and first words; open, all its fields. Only the open one mounts
 * the rich text editors.
 */
export function QuestionCard({
    question,
    number,
    total,
    open,
    media,
    disabled,
    errorFor,
    hasErrors,
    onToggle,
    onChange,
    onMove,
    onRemove,
}: Props) {
    const id = useId();
    const summary = textOf(question.prompt.doc).trim();
    const set = (changes: Partial<QuestionDraft>) =>
        onChange({ ...question, ...changes });

    return (
        <li
            className={cn(
                'rounded-xl border',
                hasErrors && 'border-destructive/60',
            )}
        >
            <div className="flex items-center gap-2 p-3">
                <button
                    type="button"
                    aria-expanded={open}
                    aria-controls={`${id}-body`}
                    aria-label={t(
                        open ? 'cms.quizzes.close' : 'cms.quizzes.open',
                        { number },
                    )}
                    onClick={onToggle}
                    className="flex min-w-0 flex-1 items-center gap-3 rounded-md px-1 py-1 text-left hover:bg-muted/50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <ChevronDown
                        className={cn(
                            'size-4 shrink-0 transition-transform',
                            open && 'rotate-180',
                        )}
                        aria-hidden="true"
                    />
                    <span className="min-w-0 flex-1">
                        <span className="block text-sm font-medium">
                            {t('cms.quizzes.question', { number })}
                            <span className="font-normal text-muted-foreground">
                                {' · '}
                                {t(`cms.quizzes.types.${question.type}`)}
                                {' · '}
                                {t('quiz.points', { count: question.points })}
                            </span>
                        </span>
                        <span className="block truncate text-sm text-muted-foreground">
                            {summary === ''
                                ? t('cms.quizzes.emptyPrompt')
                                : summary}
                        </span>
                    </span>
                    {hasErrors && (
                        <span className="flex shrink-0 items-center gap-1 text-xs font-medium text-destructive">
                            <CircleAlert
                                className="size-4"
                                aria-hidden="true"
                            />
                            <span className="hidden sm:inline">
                                {t('cms.quizzes.hasErrors')}
                            </span>
                        </span>
                    )}
                </button>
                <div className="flex shrink-0 gap-1">
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        className="size-8"
                        disabled={disabled || number === 1}
                        aria-label={t('cms.quizzes.moveUp', { number })}
                        onClick={() => onMove(-1)}
                    >
                        <ArrowUp aria-hidden="true" />
                    </Button>
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        className="size-8"
                        disabled={disabled || number === total}
                        aria-label={t('cms.quizzes.moveDown', { number })}
                        onClick={() => onMove(1)}
                    >
                        <ArrowDown aria-hidden="true" />
                    </Button>
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        className="size-8 text-destructive hover:text-destructive"
                        disabled={disabled}
                        aria-label={t('cms.quizzes.remove', { number })}
                        onClick={onRemove}
                    >
                        <Trash2 aria-hidden="true" />
                    </Button>
                </div>
            </div>

            {open && (
                <fieldset
                    id={`${id}-body`}
                    disabled={disabled}
                    className="space-y-6 border-t p-4 [&_:disabled]:cursor-not-allowed [&_:disabled]:opacity-50"
                >
                    <div className="grid gap-4 sm:grid-cols-3">
                        <Field
                            id={`${id}-type`}
                            label={t('cms.quizzes.type')}
                            help={t('cms.quizzes.typeHelp')}
                            error={errorFor('type')}
                        >
                            <select
                                id={`${id}-type`}
                                value={question.type}
                                className={selectClass}
                                aria-describedby={`${id}-type-help`}
                                onChange={(event) => {
                                    const type = event.target
                                        .value as QuestionType;
                                    set({
                                        type,
                                        payload: payloadFor(
                                            type,
                                            question.payload,
                                        ),
                                    });
                                }}
                            >
                                {Object.values(QuestionTypes).map((type) => (
                                    <option key={type} value={type}>
                                        {t(`cms.quizzes.types.${type}`)}
                                    </option>
                                ))}
                            </select>
                        </Field>
                        <Field
                            id={`${id}-points`}
                            label={t('cms.quizzes.points')}
                            error={errorFor('points')}
                        >
                            <Input
                                id={`${id}-points`}
                                type="number"
                                min={1}
                                max={10}
                                value={question.points}
                                onChange={(event) =>
                                    set({
                                        points: Number(event.target.value),
                                    })
                                }
                            />
                        </Field>
                        <Field
                            id={`${id}-difficulty`}
                            label={t('cms.quizzes.difficulty')}
                            error={errorFor('difficulty')}
                        >
                            <select
                                id={`${id}-difficulty`}
                                value={question.difficulty ?? ''}
                                className={selectClass}
                                onChange={(event) =>
                                    set({
                                        difficulty:
                                            event.target.value === ''
                                                ? null
                                                : (event.target
                                                      .value as Difficulty),
                                    })
                                }
                            >
                                <option value="">
                                    {t('cms.quizzes.noDifficulty')}
                                </option>
                                {Object.values(Difficulties).map((level) => (
                                    <option key={level} value={level}>
                                        {t(`difficulty.${level}`)}
                                    </option>
                                ))}
                            </select>
                        </Field>
                    </div>

                    <section className="space-y-2">
                        <h3 id={`${id}-prompt`} className="text-sm font-medium">
                            {t('cms.quizzes.prompt')}
                        </h3>
                        <Suspense fallback={<EditorFallback />}>
                            <RichContentEditor
                                value={question.prompt}
                                media={media}
                                labelledBy={`${id}-prompt`}
                                invalid={errorFor('prompt') !== undefined}
                                onChange={(prompt) => set({ prompt })}
                                compact
                                readOnly={disabled}
                            />
                        </Suspense>
                        <InputError message={errorFor('prompt')} />
                    </section>

                    <PayloadEditor
                        type={question.type}
                        value={question.payload}
                        onChange={(payload) => set({ payload })}
                        errorFor={(path) => errorFor(`payload.${path}`)}
                    />

                    <section className="space-y-2">
                        <h3
                            id={`${id}-explanation`}
                            className="text-sm font-medium"
                        >
                            {t('cms.quizzes.explanation')}
                        </h3>
                        <p
                            id={`${id}-explanation-help`}
                            className="text-xs text-muted-foreground"
                        >
                            {t('cms.quizzes.explanationHelp')}
                        </p>
                        <Suspense fallback={<EditorFallback />}>
                            <RichContentEditor
                                value={question.explanation}
                                media={media}
                                labelledBy={`${id}-explanation`}
                                describedBy={`${id}-explanation-help`}
                                invalid={errorFor('explanation') !== undefined}
                                onChange={(explanation) => set({ explanation })}
                                compact
                                readOnly={disabled}
                            />
                        </Suspense>
                        <InputError message={errorFor('explanation')} />
                    </section>
                </fieldset>
            )}
        </li>
    );
}

function EditorFallback() {
    return (
        <div className="min-h-24 rounded-lg border p-4 text-sm text-muted-foreground">
            {t('editor.loading')}
        </div>
    );
}
