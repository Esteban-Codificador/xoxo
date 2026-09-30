import { Head, Link, setLayoutProps, useForm } from '@inertiajs/react';
import { Eye, Plus } from 'lucide-react';
import type { FormEvent } from 'react';
import { useId, useState } from 'react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { StatusActions } from '@/components/publishing/status-actions';
import { ContentStatusBadge } from '@/components/publishing/status-badges';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Field } from '@/features/cms/field';
import { LessonTabs } from '@/features/cms/lesson-tabs';
import { QuestionCard, selectClass } from '@/features/cms/quiz/question-card';
import type { Payload, QuestionDraft } from '@/features/cms/quiz/types';
import { newQuestion } from '@/features/cms/quiz/types';
import { SaveBar } from '@/features/cms/save-bar';
import { useUnsavedChangesGuard } from '@/features/cms/use-unsaved-changes-guard';
import type { MediaMap, RichContent } from '@/features/rich-content';
import { t } from '@/i18n';
import { dashboard } from '@/routes/admin';
import { edit, index } from '@/routes/admin/lessons';
import { update } from '@/routes/admin/lessons/quiz';
import { status } from '@/routes/admin/quizzes';
import { quiz as learnerQuiz } from '@/routes/lessons';
import type { ContentStatus, Difficulty, QuestionType } from '@/types/enums';
import { QuestionType as QuestionTypes } from '@/types/enums';

type SavedQuestion = {
    id: number;
    type: QuestionType;
    prompt: RichContent;
    payload: Payload;
    explanation: RichContent;
    difficulty: Difficulty | null;
    points: number;
};

type Props = {
    lesson: { slug: string; title: string; track: string; module: string };
    quiz: {
        id: number;
        title: string;
        description: string | null;
        pass_threshold: number;
        time_limit_minutes: number | null;
        max_attempts: number | null;
        shuffle_questions: boolean;
        status: ContentStatus;
        /** Graded attempts so far. */
        attempts: number;
        questions: SavedQuestion[];
    } | null;
    media: MediaMap;
    status_actions: ContentStatus[];
    /** False while the lesson is in review for its author (ADR-032). */
    can: { save: boolean };
};

type QuizFields = {
    title: string;
    description: string;
    pass_threshold: number | '';
    time_limit_minutes: number | '';
    max_attempts: number | '';
    shuffle_questions: boolean;
};

/** The mastery score the server applies (Quiz::MASTERY_THRESHOLD). */
const MASTERY = 90;

/**
 * Saving the first time creates the quiz and the page reloads with it:
 * the key gives the saved quiz a fresh form.
 */
export default function AdminLessonQuiz(props: Props) {
    return <QuizForm key={props.quiz?.id ?? 'new'} {...props} />;
}

function QuizForm({ lesson, quiz, media, status_actions, can }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.adminOverview'), href: dashboard() },
            { title: t('nav.lessons'), href: index() },
            { title: lesson.title, href: edit(lesson.slug) },
        ],
    });

    const id = useId();
    const form = useForm<QuizFields>({
        title: quiz?.title ?? lesson.title,
        description: quiz?.description ?? '',
        pass_threshold: quiz?.pass_threshold ?? 70,
        time_limit_minutes: quiz?.time_limit_minutes ?? '',
        max_attempts: quiz?.max_attempts ?? '',
        shuffle_questions: quiz?.shuffle_questions ?? true,
    });
    // Questions carry rich text, which the form helper cannot type: they
    // live next to it and join the request in transform().
    const [questions, setQuestionsState] = useState<QuestionDraft[]>(() =>
        (quiz?.questions ?? []).map((question) => ({
            ...question,
            key: `saved-${question.id}`,
        })),
    );
    const [questionsDirty, setQuestionsDirty] = useState(false);
    const [open, setOpen] = useState<string | null>(null);
    const [newType, setNewType] = useState<QuestionType>('SINGLE_CHOICE');
    const errors = form.errors as Partial<Record<string, string>>;
    const dirty = form.isDirty || questionsDirty;

    useUnsavedChangesGuard(dirty);

    const errorsOf = (index: number) =>
        Object.keys(errors).some((key) =>
            key.startsWith(`questions.${index}.`),
        );

    const setQuestions = (next: QuestionDraft[]) => {
        setQuestionsState(next);
        setQuestionsDirty(true);
        form.clearErrors();
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            time_limit_minutes:
                data.time_limit_minutes === '' ? null : data.time_limit_minutes,
            max_attempts: data.max_attempts === '' ? null : data.max_attempts,
            questions: questions.map(({ key: _key, ...question }) => question),
        }));
        form.put(update.url(lesson.slug), {
            preserveScroll: true,
            onSuccess: () => {
                form.setDefaults();
                setQuestionsDirty(false);
            },
            onError: (bag) => {
                // Open the first question with a problem.
                const first = Object.keys(bag)
                    .map((key) => /^questions\.(\d+)\./.exec(key)?.[1])
                    .find((match) => match !== undefined);

                if (first !== undefined) {
                    setOpen(questions[Number(first)]?.key ?? null);
                }
            },
        });
    };

    return (
        <>
            <Head title={t('cms.quizzes.tabHead', { lesson: lesson.title })} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={lesson.title}
                    description={t('cms.edit.location', {
                        track: lesson.track,
                        module: lesson.module,
                    })}
                />
                <LessonTabs slug={lesson.slug} current="quiz" />

                <div className="grid gap-8 xl:grid-cols-[minmax(0,1fr)_20rem]">
                    <form onSubmit={submit} className="min-w-0 space-y-8">
                        <p
                            role={can.save ? undefined : 'status'}
                            className="rounded-lg border bg-muted/40 px-4 py-3 text-sm text-muted-foreground"
                        >
                            {!can.save
                                ? t('cms.review.frozen')
                                : quiz === null
                                  ? t('cms.quizzes.intro')
                                  : t('cms.quizzes.liveNotice')}
                        </p>

                        <fieldset
                            disabled={!can.save}
                            className="min-w-0 space-y-8 [&_:disabled]:cursor-not-allowed [&_:disabled]:opacity-50"
                        >
                            <section className="space-y-5">
                                <h2 className="font-medium">
                                    {t('cms.quizzes.settings')}
                                </h2>
                                <Field
                                    id={`${id}-title`}
                                    label={t('cms.quizzes.fields.title')}
                                    error={errors.title}
                                >
                                    <Input
                                        id={`${id}-title`}
                                        value={form.data.title}
                                        maxLength={200}
                                        required
                                        onChange={(event) =>
                                            form.setData(
                                                'title',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </Field>
                                <Field
                                    id={`${id}-description`}
                                    label={t('cms.quizzes.fields.description')}
                                    help={t(
                                        'cms.quizzes.fields.descriptionHelp',
                                    )}
                                    error={errors.description}
                                >
                                    <Textarea
                                        id={`${id}-description`}
                                        value={form.data.description}
                                        maxLength={1000}
                                        rows={2}
                                        aria-describedby={`${id}-description-help`}
                                        onChange={(event) =>
                                            form.setData(
                                                'description',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </Field>
                                <div className="grid gap-5 sm:grid-cols-3">
                                    <Field
                                        id={`${id}-pass`}
                                        label={t(
                                            'cms.quizzes.fields.passThreshold',
                                        )}
                                        help={t('cms.quizzes.fields.passHelp', {
                                            mastery: Math.max(
                                                MASTERY,
                                                Number(
                                                    form.data.pass_threshold,
                                                ) || 0,
                                            ),
                                        })}
                                        error={errors.pass_threshold}
                                    >
                                        <Input
                                            id={`${id}-pass`}
                                            type="number"
                                            min={1}
                                            max={100}
                                            required
                                            value={form.data.pass_threshold}
                                            aria-describedby={`${id}-pass-help`}
                                            onChange={(event) =>
                                                form.setData(
                                                    'pass_threshold',
                                                    event.target.value === ''
                                                        ? ''
                                                        : Number(
                                                              event.target
                                                                  .value,
                                                          ),
                                                )
                                            }
                                        />
                                    </Field>
                                    <Field
                                        id={`${id}-time`}
                                        label={t(
                                            'cms.quizzes.fields.timeLimit',
                                        )}
                                        help={t('cms.quizzes.fields.timeHelp')}
                                        error={errors.time_limit_minutes}
                                    >
                                        <Input
                                            id={`${id}-time`}
                                            type="number"
                                            min={1}
                                            max={180}
                                            value={form.data.time_limit_minutes}
                                            aria-describedby={`${id}-time-help`}
                                            onChange={(event) =>
                                                form.setData(
                                                    'time_limit_minutes',
                                                    event.target.value === ''
                                                        ? ''
                                                        : Number(
                                                              event.target
                                                                  .value,
                                                          ),
                                                )
                                            }
                                        />
                                    </Field>
                                    <Field
                                        id={`${id}-attempts`}
                                        label={t(
                                            'cms.quizzes.fields.maxAttempts',
                                        )}
                                        help={t(
                                            'cms.quizzes.fields.attemptsHelp',
                                        )}
                                        error={errors.max_attempts}
                                    >
                                        <Input
                                            id={`${id}-attempts`}
                                            type="number"
                                            min={1}
                                            max={100}
                                            value={form.data.max_attempts}
                                            aria-describedby={`${id}-attempts-help`}
                                            onChange={(event) =>
                                                form.setData(
                                                    'max_attempts',
                                                    event.target.value === ''
                                                        ? ''
                                                        : Number(
                                                              event.target
                                                                  .value,
                                                          ),
                                                )
                                            }
                                        />
                                    </Field>
                                </div>
                                <label className="flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        checked={form.data.shuffle_questions}
                                        onChange={(event) =>
                                            form.setData(
                                                'shuffle_questions',
                                                event.target.checked,
                                            )
                                        }
                                        className="size-4 accent-primary"
                                    />
                                    {t('cms.quizzes.fields.shuffle')}
                                </label>
                            </section>

                            <section
                                aria-labelledby={`${id}-questions`}
                                className="space-y-4"
                            >
                                <div className="space-y-1">
                                    <h2
                                        id={`${id}-questions`}
                                        className="font-medium"
                                    >
                                        {t('cms.quizzes.questionsTitle')}
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        {t('cms.quizzes.questionsHelp')}
                                    </p>
                                </div>

                                {questions.length === 0 ? (
                                    <p className="rounded-lg border border-dashed px-4 py-6 text-center text-sm text-muted-foreground">
                                        {t('cms.quizzes.noQuestions')}
                                    </p>
                                ) : (
                                    <ol className="space-y-3">
                                        {questions.map((question, index) => (
                                            <QuestionCard
                                                key={question.key}
                                                question={question}
                                                number={index + 1}
                                                total={questions.length}
                                                open={open === question.key}
                                                media={media}
                                                disabled={!can.save}
                                                hasErrors={errorsOf(index)}
                                                errorFor={(path) =>
                                                    errors[
                                                        `questions.${index}.${path}`
                                                    ]
                                                }
                                                onToggle={() =>
                                                    setOpen(
                                                        open === question.key
                                                            ? null
                                                            : question.key,
                                                    )
                                                }
                                                onChange={(changed) =>
                                                    setQuestions(
                                                        questions.map(
                                                            (other) =>
                                                                other.key ===
                                                                question.key
                                                                    ? changed
                                                                    : other,
                                                        ),
                                                    )
                                                }
                                                onMove={(offset) => {
                                                    const next = [...questions];
                                                    [
                                                        next[index],
                                                        next[index + offset],
                                                    ] = [
                                                        next[index + offset],
                                                        next[index],
                                                    ];
                                                    setQuestions(next);
                                                }}
                                                onRemove={() =>
                                                    setQuestions(
                                                        questions.filter(
                                                            (other) =>
                                                                other.key !==
                                                                question.key,
                                                        ),
                                                    )
                                                }
                                            />
                                        ))}
                                    </ol>
                                )}
                                <InputError message={errors.questions} />

                                <div className="flex flex-wrap items-end gap-2">
                                    <div className="grid gap-2">
                                        <label
                                            htmlFor={`${id}-new-type`}
                                            className="text-sm font-medium"
                                        >
                                            {t('cms.quizzes.newType')}
                                        </label>
                                        <select
                                            id={`${id}-new-type`}
                                            value={newType}
                                            className={selectClass}
                                            onChange={(event) =>
                                                setNewType(
                                                    event.target
                                                        .value as QuestionType,
                                                )
                                            }
                                        >
                                            {Object.values(QuestionTypes).map(
                                                (type) => (
                                                    <option
                                                        key={type}
                                                        value={type}
                                                    >
                                                        {t(
                                                            `cms.quizzes.types.${type}`,
                                                        )}
                                                    </option>
                                                ),
                                            )}
                                        </select>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => {
                                            const question =
                                                newQuestion(newType);
                                            setQuestions([
                                                ...questions,
                                                question,
                                            ]);
                                            setOpen(question.key);
                                        }}
                                    >
                                        <Plus aria-hidden="true" />
                                        {t('cms.quizzes.addQuestion')}
                                    </Button>
                                </div>
                            </section>
                        </fieldset>

                        {can.save && (
                            <SaveBar
                                dirty={dirty || quiz === null}
                                processing={form.processing}
                            />
                        )}
                    </form>

                    <aside className="space-y-4 xl:sticky xl:top-4 xl:self-start">
                        <section className="space-y-3 rounded-xl border p-4">
                            <div className="flex items-center justify-between gap-2">
                                <h2 className="font-medium">
                                    {t('cms.quizzes.status')}
                                </h2>
                                {quiz === null ? (
                                    <span className="text-sm text-muted-foreground">
                                        {t('cms.quizzes.notCreated')}
                                    </span>
                                ) : (
                                    <ContentStatusBadge status={quiz.status} />
                                )}
                            </div>
                            {quiz !== null && (
                                <>
                                    <StatusActions
                                        entity="quiz"
                                        name={quiz.title}
                                        current={quiz.status}
                                        actions={status_actions}
                                        url={status.url(quiz.id)}
                                    />
                                    {quiz.attempts > 0 && (
                                        <p className="text-sm text-muted-foreground">
                                            {t('cms.quizzes.attemptsNotice', {
                                                count: quiz.attempts,
                                            })}
                                        </p>
                                    )}
                                    {quiz.status === 'PUBLISHED' && (
                                        <Button
                                            asChild
                                            size="sm"
                                            variant="outline"
                                        >
                                            <Link
                                                href={learnerQuiz(lesson.slug)}
                                            >
                                                <Eye aria-hidden="true" />
                                                {t('cms.quizzes.view')}
                                            </Link>
                                        </Button>
                                    )}
                                </>
                            )}
                        </section>
                    </aside>
                </div>
            </div>
        </>
    );
}
