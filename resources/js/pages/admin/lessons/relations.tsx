import { Head, Link, setLayoutProps, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useId } from 'react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { LessonTabs } from '@/features/cms/lesson-tabs';
import type { ResourceOption } from '@/features/cms/resources-editor';
import { ResourcesEditor } from '@/features/cms/resources-editor';
import { SaveBar } from '@/features/cms/save-bar';
import type { SkillOption, SkillRow } from '@/features/cms/skills-editor';
import { SkillsEditor } from '@/features/cms/skills-editor';
import { useUnsavedChangesGuard } from '@/features/cms/use-unsaved-changes-guard';
import type {
    DependencyOption,
    DependencyRow,
} from '@/features/dependencies/dependency-editor';
import { DependencyEditor } from '@/features/dependencies/dependency-editor';
import { t } from '@/i18n';
import { dashboard } from '@/routes/admin';
import { edit, index } from '@/routes/admin/lessons';
import { update } from '@/routes/admin/lessons/relations';
import { create as createResource } from '@/routes/admin/resources';

type Props = {
    lesson: { slug: string; title: string; track: string; module: string };
    skills: SkillRow[];
    skill_options: SkillOption[];
    prerequisites: DependencyRow[];
    prerequisite_options: DependencyOption[];
    resources: number[];
    resource_options: ResourceOption[];
    /** False while in review for its author (ADR-032). */
    can: { save: boolean };
};

export default function AdminLessonRelations({
    lesson,
    skills,
    skill_options,
    prerequisites,
    prerequisite_options,
    resources,
    resource_options,
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
    const form = useForm({ skills, prerequisites, resources });
    const errors = form.errors as Partial<Record<string, string>>;

    useUnsavedChangesGuard(form.isDirty);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            prerequisites: data.prerequisites.map((row) => ({
                id: row.id,
                kind: row.kind,
            })),
        }));
        form.put(update.url(lesson.slug), {
            preserveScroll: true,
            onSuccess: () => form.setDefaults(),
        });
    };

    // Any edit makes the previous server errors stale.
    const changed = () => form.clearErrors();

    return (
        <>
            <Head title={t('cms.relations.head', { lesson: lesson.title })} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={lesson.title}
                    description={t('cms.edit.location', {
                        track: lesson.track,
                        module: lesson.module,
                    })}
                />
                <LessonTabs slug={lesson.slug} current="relations" />

                <form
                    onSubmit={submit}
                    className="max-w-3xl min-w-0 space-y-10"
                >
                    <p
                        role={can.save ? undefined : 'status'}
                        className="rounded-lg border bg-muted/40 px-4 py-3 text-sm text-muted-foreground"
                    >
                        {can.save
                            ? t('cms.relations.liveNotice')
                            : t('cms.review.frozen')}
                    </p>

                    <fieldset
                        disabled={!can.save}
                        className="min-w-0 space-y-10 [&_:disabled]:cursor-not-allowed [&_:disabled]:opacity-50"
                    >
                        <section
                            aria-labelledby={`${id}-skills`}
                            className="space-y-3"
                        >
                            <div className="space-y-1">
                                <h2 id={`${id}-skills`} className="font-medium">
                                    {t('cms.relations.skills')}
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    {t('cms.relations.skillsHelp')}
                                </p>
                            </div>
                            <SkillsEditor
                                options={skill_options}
                                value={form.data.skills}
                                errorFor={(position) =>
                                    errors[`skills.${position}.id`] ??
                                    errors[`skills.${position}.weight`]
                                }
                                onChange={(rows) => {
                                    form.setData('skills', rows);
                                    changed();
                                }}
                            />
                            <InputError message={errors.skills} />
                        </section>

                        <section
                            aria-labelledby={`${id}-prerequisites`}
                            className="space-y-3"
                        >
                            <div className="space-y-1">
                                <h2
                                    id={`${id}-prerequisites`}
                                    className="font-medium"
                                >
                                    {t('cms.relations.prerequisites')}
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    {t('cms.relations.prerequisitesHelp')}
                                </p>
                            </div>
                            <DependencyEditor
                                options={prerequisite_options}
                                value={form.data.prerequisites}
                                withMinProgress={false}
                                errorFor={(position) =>
                                    errors[`prerequisites.${position}.id`]
                                }
                                onChange={(rows) => {
                                    form.setData('prerequisites', rows);
                                    changed();
                                }}
                            />
                            <InputError message={errors.prerequisites} />
                        </section>

                        <section
                            aria-labelledby={`${id}-resources`}
                            className="space-y-3"
                        >
                            <div className="space-y-1">
                                <h2
                                    id={`${id}-resources`}
                                    className="font-medium"
                                >
                                    {t('cms.relations.resources')}
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    {t('cms.relations.resourcesHelp')}
                                </p>
                            </div>
                            <ResourcesEditor
                                options={resource_options}
                                value={form.data.resources}
                                errorFor={(position) =>
                                    errors[`resources.${position}`]
                                }
                                onChange={(ids) => {
                                    form.setData('resources', ids);
                                    changed();
                                }}
                            />
                            <InputError message={errors.resources} />
                            <p className="text-xs text-muted-foreground">
                                {t('cms.resources.newHint')}{' '}
                                <Link
                                    href={createResource()}
                                    className="underline underline-offset-4"
                                >
                                    {t('cms.resources.createAction')}
                                </Link>
                            </p>
                        </section>
                    </fieldset>

                    {can.save && (
                        <SaveBar
                            dirty={form.isDirty}
                            processing={form.processing}
                        />
                    )}
                </form>
            </div>
        </>
    );
}
