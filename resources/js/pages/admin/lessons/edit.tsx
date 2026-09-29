import { Head, Link, setLayoutProps, useForm, usePage } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import type { FormEvent } from 'react';
import { lazy, Suspense, useId, useState } from 'react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Field } from '@/features/cms/field';
import { ObjectivesField } from '@/features/cms/objectives-field';
import type {
    Publication,
    Readiness,
    VersionEntry,
} from '@/features/cms/publish-panel';
import { PublishPanel } from '@/features/cms/publish-panel';
import { SaveBar } from '@/features/cms/save-bar';
import { useUnsavedChangesGuard } from '@/features/cms/use-unsaved-changes-guard';
import type { RichContent } from '@/features/rich-content';
import { t } from '@/i18n';
import { dashboard } from '@/routes/admin';
import { edit, index, update } from '@/routes/admin/lessons';
import { show as showLesson } from '@/routes/lessons';
import type { ContentStatus, ContentType } from '@/types/enums';
import { Difficulty } from '@/types/enums';

// TipTap, KaTeX and the node views load only on this page (ADR-024).
const RichContentEditor = lazy(
    () => import('@/features/rich-content-editor/rich-content-editor'),
);

type LessonFields = {
    title: string;
    summary: string;
    why_it_matters: string;
    learning_objectives: string[];
    content_type: ContentType;
    difficulty: Difficulty;
    estimated_minutes: number;
};

type Props = {
    lesson: LessonFields & {
        body: RichContent;
        slug: string;
        status: ContentStatus;
        track: string;
        module: string;
    };
    readiness: Readiness;
    publication: Publication;
    versions: VersionEntry[];
    content_types: ContentType[];
    can: { publish: boolean };
};

export default function AdminLessonEdit({
    lesson,
    readiness,
    publication,
    versions,
    content_types,
    can,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.adminOverview'), href: dashboard() },
            { title: t('nav.lessons'), href: index() },
            { title: lesson.title, href: edit(lesson.slug) },
        ],
    });

    const id = useId();
    const { errors: pageErrors } = usePage().props;
    const form = useForm<LessonFields>({
        title: lesson.title,
        summary: lesson.summary,
        why_it_matters: lesson.why_it_matters,
        learning_objectives: lesson.learning_objectives,
        content_type: lesson.content_type,
        difficulty: lesson.difficulty,
        estimated_minutes: lesson.estimated_minutes,
    });
    // The body stays outside useForm (its attrs are `unknown`, not form
    // data) and is null until the editor reports a real edit.
    const [body, setBody] = useState<RichContent | null>(null);
    const dirty = form.isDirty || body !== null;
    const errors = form.errors as Partial<Record<string, string>>;

    useUnsavedChangesGuard(dirty);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, body: body ?? lesson.body }));
        form.put(update.url(lesson.slug), {
            preserveScroll: true,
            onSuccess: () => {
                form.setDefaults();
                setBody(null);
            },
        });
    };

    return (
        <>
            <Head title={t('cms.edit.head', { lesson: lesson.title })} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={lesson.title}
                    description={t('cms.edit.location', {
                        track: lesson.track,
                        module: lesson.module,
                    })}
                    actions={
                        publication.visible_to_learners && (
                            <Button asChild variant="outline" size="sm">
                                <Link href={showLesson(lesson.slug)}>
                                    <Eye aria-hidden="true" />
                                    {t('cms.edit.viewAsLearner')}
                                </Link>
                            </Button>
                        )
                    }
                />

                <div className="grid gap-8 xl:grid-cols-[minmax(0,1fr)_20rem]">
                    <form onSubmit={submit} className="min-w-0 space-y-8">
                        <section
                            aria-labelledby={`${id}-details`}
                            className="space-y-5"
                        >
                            <h2 id={`${id}-details`} className="font-medium">
                                {t('cms.edit.details')}
                            </h2>

                            <Field
                                id={`${id}-title`}
                                label={t('cms.edit.fields.title')}
                                error={errors.title}
                            >
                                <Input
                                    id={`${id}-title`}
                                    name="title"
                                    value={form.data.title}
                                    maxLength={200}
                                    required
                                    aria-invalid={errors.title !== undefined}
                                    onChange={(event) =>
                                        form.setData(
                                            'title',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>

                            <Field
                                id={`${id}-summary`}
                                label={t('cms.edit.fields.summary')}
                                help={t('cms.edit.fields.summaryHelp')}
                                error={errors.summary}
                            >
                                <Textarea
                                    id={`${id}-summary`}
                                    name="summary"
                                    value={form.data.summary}
                                    maxLength={2000}
                                    rows={3}
                                    aria-describedby={`${id}-summary-help`}
                                    aria-invalid={errors.summary !== undefined}
                                    onChange={(event) =>
                                        form.setData(
                                            'summary',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>

                            <Field
                                id={`${id}-why`}
                                label={t('cms.edit.fields.whyItMatters')}
                                help={t('cms.edit.fields.whyItMattersHelp')}
                                error={errors.why_it_matters}
                            >
                                <Textarea
                                    id={`${id}-why`}
                                    name="why_it_matters"
                                    value={form.data.why_it_matters}
                                    maxLength={2000}
                                    rows={3}
                                    aria-describedby={`${id}-why-help`}
                                    aria-invalid={
                                        errors.why_it_matters !== undefined
                                    }
                                    onChange={(event) =>
                                        form.setData(
                                            'why_it_matters',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>

                            <fieldset className="space-y-2">
                                <legend className="text-sm font-medium">
                                    {t('cms.edit.fields.objectives')}
                                </legend>
                                <p className="text-xs text-muted-foreground">
                                    {t('cms.edit.fields.objectivesHelp')}
                                </p>
                                <ObjectivesField
                                    id={`${id}-objective`}
                                    value={form.data.learning_objectives}
                                    errors={(position) =>
                                        errors[
                                            `learning_objectives.${position}`
                                        ]
                                    }
                                    onChange={(objectives) =>
                                        form.setData(
                                            'learning_objectives',
                                            objectives,
                                        )
                                    }
                                />
                                <InputError
                                    message={errors.learning_objectives}
                                />
                            </fieldset>

                            <div className="grid gap-5 sm:grid-cols-3">
                                <Field
                                    id={`${id}-type`}
                                    label={t('cms.edit.fields.contentType')}
                                    error={errors.content_type}
                                >
                                    <Select
                                        value={form.data.content_type}
                                        onValueChange={(value) =>
                                            form.setData(
                                                'content_type',
                                                value as ContentType,
                                            )
                                        }
                                    >
                                        <SelectTrigger
                                            id={`${id}-type`}
                                            className="w-full"
                                            aria-invalid={
                                                errors.content_type !==
                                                undefined
                                            }
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {content_types.map((type) => (
                                                <SelectItem
                                                    key={type}
                                                    value={type}
                                                >
                                                    {t(`contentType.${type}`)}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </Field>

                                <Field
                                    id={`${id}-difficulty`}
                                    label={t('cms.edit.fields.difficulty')}
                                    error={errors.difficulty}
                                >
                                    <Select
                                        value={form.data.difficulty}
                                        onValueChange={(value) =>
                                            form.setData(
                                                'difficulty',
                                                value as Difficulty,
                                            )
                                        }
                                    >
                                        <SelectTrigger
                                            id={`${id}-difficulty`}
                                            className="w-full"
                                            aria-invalid={
                                                errors.difficulty !== undefined
                                            }
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.values(Difficulty).map(
                                                (level) => (
                                                    <SelectItem
                                                        key={level}
                                                        value={level}
                                                    >
                                                        {t(
                                                            `difficulty.${level}`,
                                                        )}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                </Field>

                                <Field
                                    id={`${id}-minutes`}
                                    label={t('cms.edit.fields.minutes')}
                                    error={errors.estimated_minutes}
                                >
                                    <Input
                                        id={`${id}-minutes`}
                                        name="estimated_minutes"
                                        type="number"
                                        inputMode="numeric"
                                        min={1}
                                        max={600}
                                        value={form.data.estimated_minutes}
                                        aria-invalid={
                                            errors.estimated_minutes !==
                                            undefined
                                        }
                                        onChange={(event) =>
                                            form.setData(
                                                'estimated_minutes',
                                                event.target.valueAsNumber,
                                            )
                                        }
                                    />
                                </Field>
                            </div>
                        </section>

                        <section className="space-y-2">
                            <h2 id={`${id}-body`} className="font-medium">
                                {t('cms.edit.fields.body')}
                            </h2>
                            <p
                                id={`${id}-body-help`}
                                className="text-xs text-muted-foreground"
                            >
                                {t('cms.edit.fields.bodyHelp')}
                            </p>
                            <Suspense
                                fallback={
                                    <div className="min-h-96 rounded-lg border p-4 text-sm text-muted-foreground">
                                        {t('editor.loading')}
                                    </div>
                                }
                            >
                                <RichContentEditor
                                    value={lesson.body}
                                    labelledBy={`${id}-body`}
                                    describedBy={`${id}-body-help`}
                                    invalid={errors.body !== undefined}
                                    onChange={setBody}
                                />
                            </Suspense>
                            <InputError message={errors.body} />
                        </section>

                        <SaveBar dirty={dirty} processing={form.processing} />
                    </form>

                    <aside className="xl:sticky xl:top-4 xl:self-start">
                        <PublishPanel
                            lessonSlug={lesson.slug}
                            status={lesson.status}
                            readiness={readiness}
                            publication={publication}
                            versions={versions}
                            canPublish={can.publish}
                            dirty={dirty}
                            error={pageErrors.publish}
                        />
                    </aside>
                </div>
            </div>
        </>
    );
}
