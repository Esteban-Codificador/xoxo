import { Head, Link, setLayoutProps, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useId } from 'react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { StatusActions } from '@/components/publishing/status-actions';
import { ContentStatusBadge } from '@/components/publishing/status-badges';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Field } from '@/features/cms/field';
import { SaveBar } from '@/features/cms/save-bar';
import { useUnsavedChangesGuard } from '@/features/cms/use-unsaved-changes-guard';
import type {
    DependencyOption,
    DependencyRow,
} from '@/features/dependencies/dependency-editor';
import { DependencyEditor } from '@/features/dependencies/dependency-editor';
import { t } from '@/i18n';
import { dashboard } from '@/routes/admin';
import { edit as editLessonRelations } from '@/routes/admin/lessons/relations';
import {
    create,
    dependencies as dependenciesRoute,
    edit,
    index,
    status,
    store,
    update,
} from '@/routes/admin/skills';
import type { ContentStatus } from '@/types/enums';
import { Difficulty } from '@/types/enums';

type SkillFields = {
    name: string;
    slug: string;
    description: string;
    difficulty: Difficulty;
};

type Props = {
    skill: (SkillFields & { id: number; status: ContentStatus }) | null;
    dependencies: DependencyRow[];
    dependency_options: DependencyOption[];
    lessons: { slug: string; title: string }[];
    status_actions: ContentStatus[];
};

const selectClass =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

export default function AdminSkillForm({
    skill,
    dependencies,
    dependency_options,
    lessons,
    status_actions,
}: Props) {
    const heading =
        skill === null
            ? t('cms.skills.createHead')
            : t('cms.skills.editHead', { skill: skill.name });

    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.adminOverview'), href: dashboard() },
            { title: t('nav.skills'), href: index() },
            skill === null
                ? { title: t('cms.skills.createHead'), href: create() }
                : { title: skill.name, href: edit(skill.id) },
        ],
    });

    const id = useId();
    const form = useForm<SkillFields>({
        name: skill?.name ?? '',
        slug: skill?.slug ?? '',
        description: skill?.description ?? '',
        difficulty: skill?.difficulty ?? 'BEGINNER',
    });
    const prerequisites = useForm({ dependencies });
    const dependencyErrors = prerequisites.errors as Partial<
        Record<string, string>
    >;

    useUnsavedChangesGuard(form.isDirty || prerequisites.isDirty);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (skill === null) {
            form.post(store.url());
        } else {
            form.put(update.url(skill.id), {
                preserveScroll: true,
                onSuccess: () => form.setDefaults(),
            });
        }
    };

    const savePrerequisites = () => {
        if (skill === null) {
            return;
        }

        prerequisites.transform((data) => ({
            dependencies: data.dependencies.map((row) => ({
                skill_id: row.id,
                kind: row.kind,
                min_progress: row.min_progress,
            })),
        }));
        prerequisites.put(dependenciesRoute.url(skill.id), {
            preserveScroll: true,
            onSuccess: () => prerequisites.setDefaults(),
        });
    };

    return (
        <>
            <Head title={heading} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={skill === null ? heading : skill.name}
                    description={t('cms.skills.description')}
                />

                <div className="grid gap-8 xl:grid-cols-[minmax(0,1fr)_20rem]">
                    <div className="min-w-0 space-y-10">
                        <form onSubmit={submit} className="space-y-5">
                            <h2 className="font-medium">
                                {t('cms.skills.details')}
                            </h2>
                            <div className="grid gap-5 sm:grid-cols-2">
                                <Field
                                    id={`${id}-name`}
                                    label={t('cms.skills.fields.name')}
                                    error={form.errors.name}
                                >
                                    <Input
                                        id={`${id}-name`}
                                        name="name"
                                        value={form.data.name}
                                        maxLength={160}
                                        required
                                        onChange={(event) =>
                                            form.setData(
                                                'name',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </Field>
                                <Field
                                    id={`${id}-slug`}
                                    label={t('cms.fields.slug')}
                                    help={t('cms.skills.fields.slugHelp')}
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
                                        onChange={(event) =>
                                            form.setData(
                                                'slug',
                                                event.target.value,
                                            )
                                        }
                                    />
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
                                                event.target
                                                    .value as Difficulty,
                                            )
                                        }
                                    >
                                        {Object.values(Difficulty).map(
                                            (level) => (
                                                <option
                                                    key={level}
                                                    value={level}
                                                >
                                                    {t(`difficulty.${level}`)}
                                                </option>
                                            ),
                                        )}
                                    </select>
                                </Field>
                            </div>
                            <Field
                                id={`${id}-description`}
                                label={t('cms.skills.fields.description')}
                                error={form.errors.description}
                            >
                                <Textarea
                                    id={`${id}-description`}
                                    name="description"
                                    value={form.data.description}
                                    maxLength={2000}
                                    rows={4}
                                    required
                                    onChange={(event) =>
                                        form.setData(
                                            'description',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <SaveBar
                                dirty={form.isDirty}
                                processing={form.processing}
                            />
                        </form>

                        <section
                            aria-labelledby={`${id}-prerequisites`}
                            className="space-y-3"
                        >
                            <div className="space-y-1">
                                <h2
                                    id={`${id}-prerequisites`}
                                    className="font-medium"
                                >
                                    {t('cms.trackEdit.prerequisites')}
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    {t('cms.skills.prerequisitesHelp')}
                                </p>
                            </div>
                            {skill === null ? (
                                <p className="text-sm text-muted-foreground">
                                    {t('cms.skills.saveFirst')}
                                </p>
                            ) : (
                                <>
                                    <DependencyEditor
                                        options={dependency_options}
                                        value={prerequisites.data.dependencies}
                                        withMinProgress
                                        errorFor={(position) =>
                                            dependencyErrors[
                                                `dependencies.${position}.skill_id`
                                            ] ??
                                            dependencyErrors[
                                                `dependencies.${position}.min_progress`
                                            ]
                                        }
                                        onChange={(rows) => {
                                            prerequisites.setData(
                                                'dependencies',
                                                rows,
                                            );
                                            prerequisites.clearErrors();
                                        }}
                                    />
                                    <InputError
                                        message={dependencyErrors.dependencies}
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={
                                            !prerequisites.isDirty ||
                                            prerequisites.processing
                                        }
                                        onClick={savePrerequisites}
                                    >
                                        {t('cms.trackEdit.savePrerequisites')}
                                    </Button>
                                </>
                            )}
                        </section>
                    </div>

                    {skill !== null && (
                        <aside className="space-y-4 xl:sticky xl:top-4 xl:self-start">
                            <section className="space-y-3 rounded-xl border p-4">
                                <div className="flex items-center justify-between gap-2">
                                    <h2 className="font-medium">
                                        {t('cms.resources.status')}
                                    </h2>
                                    <ContentStatusBadge status={skill.status} />
                                </div>
                                <StatusActions
                                    entity="skill"
                                    name={skill.name}
                                    current={skill.status}
                                    actions={status_actions}
                                    url={status.url(skill.id)}
                                />
                            </section>
                            <section className="space-y-2 rounded-xl border p-4">
                                <h2 className="font-medium">
                                    {t('cms.skills.developedBy')}
                                </h2>
                                {lessons.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        {t('cms.skills.notUsed')}
                                    </p>
                                ) : (
                                    <ul className="space-y-1 text-sm">
                                        {lessons.map((lesson) => (
                                            <li key={lesson.slug}>
                                                <Link
                                                    href={editLessonRelations(
                                                        lesson.slug,
                                                    )}
                                                    className="underline-offset-4 hover:underline"
                                                >
                                                    {lesson.title}
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </section>
                        </aside>
                    )}
                </div>
            </div>
        </>
    );
}
