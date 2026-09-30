import { Head, Link, setLayoutProps, useForm } from '@inertiajs/react';
import { Eye, Layers } from 'lucide-react';
import type { FormEvent } from 'react';
import { lazy, Suspense, useId, useState } from 'react';
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
import type { MediaMap, RichContent } from '@/features/rich-content';
import { t } from '@/i18n';
import { dashboard } from '@/routes/admin';
import { edit, status, update } from '@/routes/admin/roadmaps';
import { index as tracksIndex } from '@/routes/admin/tracks';
import { show as showRoadmap } from '@/routes/roadmaps';
import type { ContentStatus, UnlockPolicy } from '@/types/enums';

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

type RoadmapFields = {
    title: string;
    slug: string;
    summary: string;
    unlock_policy: UnlockPolicy;
};

type Props = {
    /** Images of the content, by media id (MediaSources). */
    media: MediaMap;
    roadmap: RoadmapFields & {
        id: number;
        description: RichContent | null;
        status: ContentStatus;
        published_at: string | null;
        tracks: number;
        published_tracks: number;
    };
    unlock_policies: UnlockPolicy[];
    status_actions: ContentStatus[];
};

export default function AdminRoadmapEdit({
    roadmap,
    media,
    unlock_policies,
    status_actions,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.adminOverview'), href: dashboard() },
            { title: t('nav.tracks'), href: tracksIndex() },
            { title: roadmap.title, href: edit(roadmap.id) },
        ],
    });

    const id = useId();
    const form = useForm<RoadmapFields>({
        title: roadmap.title,
        slug: roadmap.slug,
        summary: roadmap.summary,
        unlock_policy: roadmap.unlock_policy,
    });
    // Like the track description: outside useForm, undefined until edited.
    const [description, setDescription] = useState<RichContent | undefined>();
    const dirty = form.isDirty || description !== undefined;
    const errors = form.errors as Partial<Record<string, string>>;
    const published = roadmap.status === 'PUBLISHED';

    useUnsavedChangesGuard(dirty);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            description:
                description === undefined
                    ? roadmap.description
                    : isEmptyDocument(description)
                      ? null
                      : description,
        }));
        form.put(update.url(roadmap.id), {
            preserveScroll: true,
            onSuccess: () => {
                form.setDefaults();
                setDescription(undefined);
            },
        });
    };

    return (
        <>
            <Head
                title={t('cms.roadmapEdit.head', { roadmap: roadmap.title })}
            />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={roadmap.title}
                    description={t('cms.roadmapEdit.tracks', {
                        count: roadmap.tracks,
                        published: roadmap.published_tracks,
                    })}
                    actions={
                        <div className="flex flex-wrap gap-2">
                            <Button asChild variant="outline" size="sm">
                                <Link href={tracksIndex()}>
                                    <Layers aria-hidden="true" />
                                    {t('cms.roadmapEdit.tracksLink')}
                                </Link>
                            </Button>
                            {published && (
                                <Button asChild variant="outline" size="sm">
                                    <Link href={showRoadmap(roadmap.slug)}>
                                        <Eye aria-hidden="true" />
                                        {t('cms.edit.viewAsLearner')}
                                    </Link>
                                </Button>
                            )}
                        </div>
                    }
                />

                <div className="grid gap-8 xl:grid-cols-[minmax(0,1fr)_20rem]">
                    <form onSubmit={submit} className="min-w-0 space-y-8">
                        <section
                            aria-labelledby={`${id}-details`}
                            className="space-y-5"
                        >
                            <h2 id={`${id}-details`} className="font-medium">
                                {t('cms.roadmapEdit.details')}
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
                                    help={t('cms.roadmapEdit.fields.slugHelp')}
                                    error={errors.slug}
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
                            </div>

                            <Field
                                id={`${id}-summary`}
                                label={t('cms.edit.fields.summary')}
                                help={t('cms.roadmapEdit.fields.summaryHelp')}
                                error={errors.summary}
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
                                        form.setData(
                                            'summary',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                        </section>

                        <fieldset className="space-y-3">
                            <legend className="font-medium">
                                {t('cms.roadmapEdit.policy')}
                            </legend>
                            <p className="text-sm text-muted-foreground">
                                {t('cms.roadmapEdit.policyHelp')}
                            </p>
                            <div className="grid gap-2 sm:grid-cols-2">
                                {unlock_policies.map((policy) => (
                                    <label
                                        key={policy}
                                        className="flex cursor-pointer items-start gap-3 rounded-lg border p-3 has-checked:border-primary has-checked:bg-primary/5"
                                    >
                                        <input
                                            type="radio"
                                            name="unlock_policy"
                                            value={policy}
                                            checked={
                                                form.data.unlock_policy ===
                                                policy
                                            }
                                            onChange={() =>
                                                form.setData(
                                                    'unlock_policy',
                                                    policy,
                                                )
                                            }
                                            aria-describedby={`${id}-${policy}-help`}
                                            className="mt-1 size-4 accent-primary"
                                        />
                                        <span className="space-y-0.5">
                                            <span className="block text-sm font-medium">
                                                {t(
                                                    `cms.roadmapEdit.policies.${policy}`,
                                                )}
                                            </span>
                                            <span
                                                id={`${id}-${policy}-help`}
                                                className="block text-xs text-muted-foreground"
                                            >
                                                {t(
                                                    `cms.roadmapEdit.policyHelps.${policy}`,
                                                )}
                                            </span>
                                        </span>
                                    </label>
                                ))}
                            </div>
                            <InputError message={errors.unlock_policy} />
                        </fieldset>

                        <section className="space-y-2">
                            <h2
                                id={`${id}-description`}
                                className="font-medium"
                            >
                                {t('cms.roadmapEdit.fields.description')}
                            </h2>
                            <p
                                id={`${id}-description-help`}
                                className="text-xs text-muted-foreground"
                            >
                                {t('cms.roadmapEdit.fields.descriptionHelp')}
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
                                        roadmap.description ?? EMPTY_DOCUMENT
                                    }
                                    media={media}
                                    labelledBy={`${id}-description`}
                                    describedBy={`${id}-description-help`}
                                    invalid={errors.description !== undefined}
                                    onChange={setDescription}
                                    compact
                                />
                            </Suspense>
                            <InputError message={errors.description} />
                        </section>

                        <SaveBar dirty={dirty} processing={form.processing} />
                    </form>

                    <aside className="space-y-4 xl:sticky xl:top-4 xl:self-start">
                        <section
                            aria-labelledby={`${id}-status`}
                            className="space-y-3 rounded-xl border p-4"
                        >
                            <div className="flex items-center justify-between gap-2">
                                <h2 id={`${id}-status`} className="font-medium">
                                    {t('cms.roadmapEdit.status')}
                                </h2>
                                <ContentStatusBadge status={roadmap.status} />
                            </div>
                            <p className="text-sm text-muted-foreground">
                                {published
                                    ? t('cms.roadmapEdit.visible')
                                    : t('cms.roadmapEdit.hidden')}
                            </p>
                            <StatusActions
                                entity="roadmap"
                                name={roadmap.title}
                                current={roadmap.status}
                                actions={status_actions}
                                url={status.url(roadmap.id)}
                            />
                            <p className="text-xs text-muted-foreground">
                                {t('cms.roadmapEdit.liveNotice')}
                            </p>
                        </section>
                    </aside>
                </div>
            </div>
        </>
    );
}
