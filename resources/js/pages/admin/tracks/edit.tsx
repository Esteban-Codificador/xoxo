import { Head, Link, setLayoutProps, useForm } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import type { FormEvent } from 'react';
import { lazy, Suspense, useId, useState } from 'react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { StatusActions } from '@/components/publishing/status-actions';
import { ContentStatusBadge } from '@/components/publishing/status-badges';
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
import type { ModuleRow } from '@/features/cms/module-list';
import { ModuleList } from '@/features/cms/module-list';
import { SaveBar } from '@/features/cms/save-bar';
import { useUnsavedChangesGuard } from '@/features/cms/use-unsaved-changes-guard';
import type {
    DependencyOption,
    DependencyRow,
} from '@/features/dependencies/dependency-editor';
import { DependencyEditor } from '@/features/dependencies/dependency-editor';
import type { RichContent } from '@/features/rich-content';
import { t } from '@/i18n';
import { dashboard } from '@/routes/admin';
import {
    dependencies as dependenciesRoute,
    edit,
    index,
    status,
    update,
} from '@/routes/admin/tracks';
import { show as showTrack } from '@/routes/tracks';
import type { ContentStatus } from '@/types/enums';
import { Difficulty } from '@/types/enums';

const RichContentEditor = lazy(
    () => import('@/features/rich-content-editor/rich-content-editor'),
);

const EMPTY_DOCUMENT: RichContent = {
    version: 1,
    doc: { type: 'doc', content: [{ type: 'paragraph' }] },
};

/** Only empty paragraphs: saved as "no description". */
function isEmptyDocument(content: RichContent): boolean {
    return (content.doc.content ?? []).every(
        (node) =>
            node.type === 'paragraph' && (node.content ?? []).length === 0,
    );
}

type TrackFields = {
    title: string;
    slug: string;
    summary: string;
    why_it_matters: string;
    difficulty: Difficulty;
    estimated_hours: number | null;
};

type Props = {
    track: TrackFields & {
        id: number;
        description: RichContent | null;
        status: ContentStatus;
        published_at: string | null;
        roadmap: { slug: string; title: string };
    };
    visible_to_learners: boolean;
    status_actions: ContentStatus[];
    dependencies: DependencyRow[];
    dependency_options: DependencyOption[];
    modules: ModuleRow[];
};

export default function AdminTrackEdit({
    track,
    visible_to_learners,
    status_actions,
    dependencies,
    dependency_options,
    modules,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.adminOverview'), href: dashboard() },
            { title: t('nav.tracks'), href: index() },
            { title: track.title, href: edit(track.id) },
        ],
    });

    const id = useId();
    const form = useForm<TrackFields>({
        title: track.title,
        slug: track.slug,
        summary: track.summary,
        why_it_matters: track.why_it_matters,
        difficulty: track.difficulty,
        estimated_hours: track.estimated_hours,
    });
    // Like the lesson body: outside useForm, undefined until edited.
    const [description, setDescription] = useState<RichContent | undefined>();
    const prerequisites = useForm({ dependencies });
    const dirty =
        form.isDirty || description !== undefined || prerequisites.isDirty;
    const errors = form.errors as Partial<Record<string, string>>;
    const dependencyErrors = prerequisites.errors as Partial<
        Record<string, string>
    >;

    useUnsavedChangesGuard(dirty);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            description:
                description === undefined
                    ? track.description
                    : isEmptyDocument(description)
                      ? null
                      : description,
        }));
        form.put(update.url(track.id), {
            preserveScroll: true,
            onSuccess: () => {
                form.setDefaults();
                setDescription(undefined);
            },
        });
    };

    const savePrerequisites = () => {
        prerequisites.transform((data) => ({
            dependencies: data.dependencies.map((row) => ({
                track_id: row.id,
                kind: row.kind,
                min_progress: row.min_progress,
            })),
        }));
        prerequisites.put(dependenciesRoute.url(track.id), {
            preserveScroll: true,
            onSuccess: () => prerequisites.setDefaults(),
        });
    };

    const visibility = visible_to_learners
        ? t('cms.trackEdit.visible')
        : track.status === 'PUBLISHED'
          ? t('cms.trackEdit.roadmapHidden')
          : t('cms.trackEdit.hidden');

    return (
        <>
            <Head title={t('cms.trackEdit.head', { track: track.title })} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={track.title}
                    description={t('cms.trackEdit.roadmap', {
                        roadmap: track.roadmap.title,
                    })}
                    actions={
                        visible_to_learners && (
                            <Button asChild variant="outline" size="sm">
                                <Link
                                    href={showTrack({
                                        roadmap: track.roadmap.slug,
                                        track: track.slug,
                                    })}
                                >
                                    <Eye aria-hidden="true" />
                                    {t('cms.edit.viewAsLearner')}
                                </Link>
                            </Button>
                        )
                    }
                />

                <div className="grid gap-8 xl:grid-cols-[minmax(0,1fr)_20rem]">
                    <div className="min-w-0 space-y-10">
                        <form onSubmit={submit} className="space-y-8">
                            <section
                                aria-labelledby={`${id}-details`}
                                className="space-y-5"
                            >
                                <h2
                                    id={`${id}-details`}
                                    className="font-medium"
                                >
                                    {t('cms.trackEdit.details')}
                                </h2>

                                <div className="grid gap-5 sm:grid-cols-2">
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
                                            onChange={(event) =>
                                                form.setData(
                                                    'title',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                    <Field
                                        id={`${id}-slug`}
                                        label={t('cms.fields.slug')}
                                        help={t(
                                            'cms.trackEdit.fields.slugHelp',
                                        )}
                                        error={errors.slug}
                                    >
                                        <Input
                                            id={`${id}-slug`}
                                            name="slug"
                                            value={form.data.slug}
                                            maxLength={120}
                                            spellCheck={false}
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
                                </div>

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
                                        id={`${id}-hours`}
                                        label={t('cms.trackEdit.fields.hours')}
                                        help={t(
                                            'cms.trackEdit.fields.hoursHelp',
                                        )}
                                        error={errors.estimated_hours}
                                    >
                                        <Input
                                            id={`${id}-hours`}
                                            name="estimated_hours"
                                            type="number"
                                            inputMode="numeric"
                                            min={1}
                                            max={1000}
                                            value={
                                                form.data.estimated_hours ?? ''
                                            }
                                            aria-describedby={`${id}-hours-help`}
                                            onChange={(event) =>
                                                form.setData(
                                                    'estimated_hours',
                                                    event.target.value === ''
                                                        ? null
                                                        : event.target
                                                              .valueAsNumber,
                                                )
                                            }
                                        />
                                    </Field>
                                </div>
                            </section>

                            <section className="space-y-2">
                                <h2
                                    id={`${id}-description`}
                                    className="font-medium"
                                >
                                    {t('cms.trackEdit.fields.description')}
                                </h2>
                                <p
                                    id={`${id}-description-help`}
                                    className="text-xs text-muted-foreground"
                                >
                                    {t('cms.trackEdit.fields.descriptionHelp')}
                                </p>
                                <Suspense
                                    fallback={
                                        <div className="min-h-48 rounded-lg border p-4 text-sm text-muted-foreground">
                                            {t('editor.loading')}
                                        </div>
                                    }
                                >
                                    <RichContentEditor
                                        value={
                                            track.description ?? EMPTY_DOCUMENT
                                        }
                                        labelledBy={`${id}-description`}
                                        describedBy={`${id}-description-help`}
                                        invalid={
                                            errors.description !== undefined
                                        }
                                        onChange={setDescription}
                                        compact
                                    />
                                </Suspense>
                                <InputError message={errors.description} />
                            </section>

                            <SaveBar
                                dirty={
                                    form.isDirty || description !== undefined
                                }
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
                                    {t('cms.trackEdit.prerequisitesHelp')}
                                </p>
                            </div>
                            <DependencyEditor
                                options={dependency_options}
                                value={prerequisites.data.dependencies}
                                withMinProgress
                                errorFor={(position) =>
                                    dependencyErrors[
                                        `dependencies.${position}.track_id`
                                    ] ??
                                    dependencyErrors[
                                        `dependencies.${position}.min_progress`
                                    ]
                                }
                                onChange={(rows) => {
                                    prerequisites.setData('dependencies', rows);
                                    prerequisites.clearErrors();
                                }}
                            />
                            <InputError
                                message={dependencyErrors.dependencies}
                            />
                            <div className="flex flex-wrap items-center gap-3">
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
                                {prerequisites.isDirty && (
                                    <p className="text-sm text-muted-foreground">
                                        {t('cms.dependencies.unsaved')}
                                    </p>
                                )}
                            </div>
                        </section>

                        <section
                            aria-labelledby={`${id}-modules`}
                            className="space-y-3"
                        >
                            <div className="space-y-1">
                                <h2
                                    id={`${id}-modules`}
                                    className="font-medium"
                                >
                                    {t('cms.trackEdit.modules')}
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    {t('cms.trackEdit.modulesHelp')}
                                </p>
                            </div>
                            <ModuleList trackId={track.id} modules={modules} />
                        </section>
                    </div>

                    <aside className="space-y-4 xl:sticky xl:top-4 xl:self-start">
                        <section
                            aria-labelledby={`${id}-status`}
                            className="space-y-3 rounded-xl border p-4"
                        >
                            <div className="flex items-center justify-between gap-2">
                                <h2 id={`${id}-status`} className="font-medium">
                                    {t('cms.trackEdit.status')}
                                </h2>
                                <ContentStatusBadge status={track.status} />
                            </div>
                            <p className="text-sm text-muted-foreground">
                                {visibility}
                            </p>
                            <StatusActions
                                entity="track"
                                name={track.title}
                                current={track.status}
                                actions={status_actions}
                                url={status.url(track.id)}
                            />
                            <p className="text-xs text-muted-foreground">
                                {t('cms.trackEdit.liveNotice')}
                            </p>
                        </section>
                    </aside>
                </div>
            </div>
        </>
    );
}
