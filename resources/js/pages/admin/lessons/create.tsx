import { Head, setLayoutProps, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import type { FormEvent } from 'react';
import { useId, useState } from 'react';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Field } from '@/features/cms/field';
import { useUnsavedChangesGuard } from '@/features/cms/use-unsaved-changes-guard';
import { t } from '@/i18n';
import { slugify } from '@/lib/slug';
import { dashboard } from '@/routes/admin';
import { create, index, store } from '@/routes/admin/lessons';
import type { ContentType } from '@/types/enums';
import { Difficulty } from '@/types/enums';

type Props = {
    tracks: { title: string; modules: { id: number; title: string }[] }[];
    /** Preselected when the lesson is added from a module. */
    module_id: number | null;
    content_types: ContentType[];
};

const selectClass =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

export default function AdminLessonCreate({
    tracks,
    module_id,
    content_types,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.adminOverview'), href: dashboard() },
            { title: t('nav.lessons'), href: index() },
            { title: t('cms.lessonCreate.head'), href: create() },
        ],
    });

    const id = useId();
    // The slug follows the title until the author edits it.
    const [slugEdited, setSlugEdited] = useState(false);
    const form = useForm({
        module_id: module_id === null ? '' : String(module_id),
        title: '',
        slug: '',
        summary: '',
        why_it_matters: '',
        content_type: (content_types[0] ?? 'CONCEPT') as ContentType,
        difficulty: 'BEGINNER' as Difficulty,
        estimated_minutes: 30,
    });

    useUnsavedChangesGuard(form.isDirty && !form.processing);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(store.url());
    };

    return (
        <>
            <Head title={t('cms.lessonCreate.head')} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('cms.lessonCreate.head')}
                    description={t('cms.lessonCreate.description')}
                />

                {tracks.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {t('cms.lessonCreate.noModules')}
                    </p>
                ) : (
                    <form onSubmit={submit} className="max-w-3xl space-y-5">
                        <Field
                            id={`${id}-module`}
                            label={t('cms.lessonCreate.module')}
                            error={form.errors.module_id}
                        >
                            <select
                                id={`${id}-module`}
                                name="module_id"
                                value={form.data.module_id}
                                required
                                className={selectClass}
                                onChange={(event) =>
                                    form.setData(
                                        'module_id',
                                        event.target.value,
                                    )
                                }
                            >
                                <option value="" disabled>
                                    {t('cms.lessonCreate.chooseModule')}
                                </option>
                                {tracks.map((track) => (
                                    <optgroup
                                        key={track.title}
                                        label={track.title}
                                    >
                                        {track.modules.map((module) => (
                                            <option
                                                key={module.id}
                                                value={module.id}
                                            >
                                                {module.title}
                                            </option>
                                        ))}
                                    </optgroup>
                                ))}
                            </select>
                        </Field>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field
                                id={`${id}-title`}
                                label={t('cms.edit.fields.title')}
                                error={form.errors.title}
                            >
                                <Input
                                    id={`${id}-title`}
                                    name="title"
                                    value={form.data.title}
                                    maxLength={200}
                                    required
                                    onChange={(event) =>
                                        form.setData((data) => ({
                                            ...data,
                                            title: event.target.value,
                                            slug: slugEdited
                                                ? data.slug
                                                : slugify(
                                                      event.target.value,
                                                      160,
                                                  ),
                                        }))
                                    }
                                />
                            </Field>
                            <Field
                                id={`${id}-slug`}
                                label={t('cms.fields.slug')}
                                help={t('cms.edit.fields.slugHelp')}
                                error={form.errors.slug}
                            >
                                <Input
                                    id={`${id}-slug`}
                                    name="slug"
                                    value={form.data.slug}
                                    maxLength={160}
                                    spellCheck={false}
                                    required
                                    className="font-mono"
                                    aria-describedby={`${id}-slug-help`}
                                    onChange={(event) => {
                                        setSlugEdited(true);
                                        form.setData(
                                            'slug',
                                            event.target.value,
                                        );
                                    }}
                                />
                            </Field>
                        </div>

                        <Field
                            id={`${id}-summary`}
                            label={t('cms.edit.fields.summary')}
                            help={t('cms.edit.fields.summaryHelp')}
                            error={form.errors.summary}
                        >
                            <Textarea
                                id={`${id}-summary`}
                                name="summary"
                                value={form.data.summary}
                                maxLength={2000}
                                rows={3}
                                required
                                aria-describedby={`${id}-summary-help`}
                                onChange={(event) =>
                                    form.setData('summary', event.target.value)
                                }
                            />
                        </Field>

                        <Field
                            id={`${id}-why`}
                            label={t('cms.edit.fields.whyItMatters')}
                            help={t('cms.edit.fields.whyItMattersHelp')}
                            error={form.errors.why_it_matters}
                        >
                            <Textarea
                                id={`${id}-why`}
                                name="why_it_matters"
                                value={form.data.why_it_matters}
                                maxLength={2000}
                                rows={3}
                                required
                                aria-describedby={`${id}-why-help`}
                                onChange={(event) =>
                                    form.setData(
                                        'why_it_matters',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>

                        <div className="grid gap-5 sm:grid-cols-3">
                            <Field
                                id={`${id}-type`}
                                label={t('cms.edit.fields.contentType')}
                                error={form.errors.content_type}
                            >
                                <select
                                    id={`${id}-type`}
                                    name="content_type"
                                    value={form.data.content_type}
                                    className={selectClass}
                                    onChange={(event) =>
                                        form.setData(
                                            'content_type',
                                            event.target.value as ContentType,
                                        )
                                    }
                                >
                                    {content_types.map((type) => (
                                        <option key={type} value={type}>
                                            {t(`contentType.${type}`)}
                                        </option>
                                    ))}
                                </select>
                            </Field>
                            <Field
                                id={`${id}-difficulty`}
                                label={t('cms.edit.fields.difficulty')}
                                error={form.errors.difficulty}
                            >
                                <select
                                    id={`${id}-difficulty`}
                                    name="difficulty"
                                    value={form.data.difficulty}
                                    className={selectClass}
                                    onChange={(event) =>
                                        form.setData(
                                            'difficulty',
                                            event.target.value as Difficulty,
                                        )
                                    }
                                >
                                    {Object.values(Difficulty).map((level) => (
                                        <option key={level} value={level}>
                                            {t(`difficulty.${level}`)}
                                        </option>
                                    ))}
                                </select>
                            </Field>
                            <Field
                                id={`${id}-minutes`}
                                label={t('cms.edit.fields.minutes')}
                                error={form.errors.estimated_minutes}
                            >
                                <Input
                                    id={`${id}-minutes`}
                                    name="estimated_minutes"
                                    type="number"
                                    inputMode="numeric"
                                    min={1}
                                    max={600}
                                    required
                                    value={form.data.estimated_minutes}
                                    onChange={(event) =>
                                        form.setData(
                                            'estimated_minutes',
                                            event.target.valueAsNumber,
                                        )
                                    }
                                />
                            </Field>
                        </div>

                        <Button type="submit" disabled={form.processing}>
                            <Plus aria-hidden="true" />
                            {form.processing
                                ? t('cms.lessonCreate.creating')
                                : t('cms.lessonCreate.submit')}
                        </Button>
                    </form>
                )}
            </div>
        </>
    );
}
