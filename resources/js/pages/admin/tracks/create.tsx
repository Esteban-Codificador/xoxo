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
import { create, index, store } from '@/routes/admin/tracks';
import { Difficulty } from '@/types/enums';

type Props = { roadmap: { id: number; slug: string; title: string } };

const selectClass =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

export default function AdminTrackCreate({ roadmap }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.adminOverview'), href: dashboard() },
            { title: t('nav.tracks'), href: index() },
            { title: t('cms.trackCreate.head'), href: create() },
        ],
    });

    const id = useId();
    // The slug follows the title until the author edits it.
    const [slugEdited, setSlugEdited] = useState(false);
    const form = useForm({
        roadmap_id: roadmap.id,
        title: '',
        slug: '',
        summary: '',
        why_it_matters: '',
        difficulty: 'BEGINNER' as Difficulty,
        estimated_hours: null as number | null,
    });

    useUnsavedChangesGuard(form.isDirty && !form.processing);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(store.url());
    };

    return (
        <>
            <Head title={t('cms.trackCreate.head')} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('cms.trackCreate.head')}
                    description={t('cms.trackCreate.description', {
                        roadmap: roadmap.title,
                    })}
                />

                <form onSubmit={submit} className="max-w-3xl space-y-5">
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
                                autoFocus
                                onChange={(event) =>
                                    form.setData((data) => ({
                                        ...data,
                                        title: event.target.value,
                                        slug: slugEdited
                                            ? data.slug
                                            : slugify(event.target.value),
                                    }))
                                }
                            />
                        </Field>
                        <Field
                            id={`${id}-slug`}
                            label={t('cms.fields.slug')}
                            help={t('cms.trackEdit.fields.slugHelp')}
                            error={form.errors.slug}
                        >
                            <Input
                                id={`${id}-slug`}
                                name="slug"
                                value={form.data.slug}
                                maxLength={120}
                                spellCheck={false}
                                required
                                className="font-mono"
                                aria-describedby={`${id}-slug-help`}
                                onChange={(event) => {
                                    setSlugEdited(true);
                                    form.setData('slug', event.target.value);
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

                    <div className="grid gap-5 sm:grid-cols-2">
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
                            id={`${id}-hours`}
                            label={t('cms.trackEdit.fields.hours')}
                            help={t('cms.trackEdit.fields.hoursHelp')}
                            error={form.errors.estimated_hours}
                        >
                            <Input
                                id={`${id}-hours`}
                                name="estimated_hours"
                                type="number"
                                inputMode="numeric"
                                min={1}
                                max={1000}
                                value={form.data.estimated_hours ?? ''}
                                aria-describedby={`${id}-hours-help`}
                                onChange={(event) =>
                                    form.setData(
                                        'estimated_hours',
                                        event.target.value === ''
                                            ? null
                                            : event.target.valueAsNumber,
                                    )
                                }
                            />
                        </Field>
                    </div>

                    <Button type="submit" disabled={form.processing}>
                        <Plus aria-hidden="true" />
                        {form.processing
                            ? t('cms.trackCreate.creating')
                            : t('cms.trackCreate.submit')}
                    </Button>
                </form>
            </div>
        </>
    );
}
