import { Head, Link, router, setLayoutProps, useForm } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import type { FormEvent } from 'react';
import { useId, useState } from 'react';
import { PageHeader } from '@/components/page-header';
import { StatusActions } from '@/components/publishing/status-actions';
import {
    ContentStatusBadge,
    LinkStatusBadge,
} from '@/components/publishing/status-badges';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Field } from '@/features/cms/field';
import { SaveBar } from '@/features/cms/save-bar';
import { useUnsavedChangesGuard } from '@/features/cms/use-unsaved-changes-guard';
import { t } from '@/i18n';
import { formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import { edit as editLessonRelations } from '@/routes/admin/lessons/relations';
import {
    create,
    edit,
    index,
    status,
    store,
    update,
    verify,
} from '@/routes/admin/resources';
import type {
    ContentStatus,
    Difficulty,
    LinkStatus,
    ResourceType,
} from '@/types/enums';
import {
    Difficulty as Difficulties,
    ResourceType as ResourceTypes,
} from '@/types/enums';

type ResourceFields = {
    title: string;
    url: string;
    type: ResourceType;
    provider: string;
    description: string;
    difficulty: Difficulty | null;
    language: 'es' | 'en';
    is_official: boolean;
};

type Props = {
    resource:
        | (ResourceFields & {
              id: number;
              status: ContentStatus;
              link_status: LinkStatus;
              last_checked_at: string | null;
              last_http_status: number | null;
          })
        | null;
    lessons: { slug: string; title: string }[];
    status_actions: ContentStatus[];
};

const selectClass =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

export default function AdminResourceForm({
    resource,
    lessons,
    status_actions,
}: Props) {
    const heading =
        resource === null
            ? t('cms.resources.createHead')
            : t('cms.resources.editHead', { resource: resource.title });

    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.adminOverview'), href: dashboard() },
            { title: t('nav.resources'), href: index() },
            resource === null
                ? { title: t('cms.resources.createHead'), href: create() }
                : { title: resource.title, href: edit(resource.id) },
        ],
    });

    const id = useId();
    const [verifying, setVerifying] = useState(false);
    const form = useForm<ResourceFields>({
        title: resource?.title ?? '',
        url: resource?.url ?? '',
        type: resource?.type ?? 'DOCUMENTATION',
        provider: resource?.provider ?? '',
        description: resource?.description ?? '',
        difficulty: resource?.difficulty ?? null,
        language: resource?.language ?? 'en',
        is_official: resource?.is_official ?? false,
    });

    useUnsavedChangesGuard(form.isDirty);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (resource === null) {
            form.post(store.url());
        } else {
            form.put(update.url(resource.id), {
                preserveScroll: true,
                onSuccess: () => form.setDefaults(),
            });
        }
    };

    const verifyNow = () => {
        if (resource !== null) {
            router.post(
                verify.url(resource.id),
                {},
                {
                    preserveScroll: true,
                    onStart: () => setVerifying(true),
                    onFinish: () => setVerifying(false),
                },
            );
        }
    };

    return (
        <>
            <Head title={heading} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={resource === null ? heading : resource.title}
                    description={t('cms.resources.description')}
                />

                <div className="grid gap-8 xl:grid-cols-[minmax(0,1fr)_20rem]">
                    <form onSubmit={submit} className="min-w-0 space-y-5">
                        <h2 className="font-medium">
                            {t('cms.resources.details')}
                        </h2>

                        <Field
                            id={`${id}-title`}
                            label={t('cms.resources.fields.title')}
                            error={form.errors.title}
                        >
                            <Input
                                id={`${id}-title`}
                                name="title"
                                value={form.data.title}
                                maxLength={200}
                                required
                                onChange={(event) =>
                                    form.setData('title', event.target.value)
                                }
                            />
                        </Field>

                        <Field
                            id={`${id}-url`}
                            label={t('cms.resources.fields.url')}
                            help={t('cms.resources.fields.urlHelp')}
                            error={form.errors.url}
                        >
                            <Input
                                id={`${id}-url`}
                                name="url"
                                type="url"
                                inputMode="url"
                                value={form.data.url}
                                maxLength={2048}
                                spellCheck={false}
                                required
                                placeholder="https://"
                                className="font-mono"
                                aria-describedby={`${id}-url-help`}
                                onChange={(event) =>
                                    form.setData('url', event.target.value)
                                }
                            />
                        </Field>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field
                                id={`${id}-provider`}
                                label={t('cms.resources.fields.provider')}
                                help={t('cms.resources.fields.providerHelp')}
                                error={form.errors.provider}
                            >
                                <Input
                                    id={`${id}-provider`}
                                    name="provider"
                                    value={form.data.provider}
                                    maxLength={120}
                                    required
                                    aria-describedby={`${id}-provider-help`}
                                    onChange={(event) =>
                                        form.setData(
                                            'provider',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                id={`${id}-type`}
                                label={t('cms.resources.fields.type')}
                                error={form.errors.type}
                            >
                                <select
                                    id={`${id}-type`}
                                    name="type"
                                    value={form.data.type}
                                    className={selectClass}
                                    onChange={(event) =>
                                        form.setData(
                                            'type',
                                            event.target.value as ResourceType,
                                        )
                                    }
                                >
                                    {Object.values(ResourceTypes).map(
                                        (type) => (
                                            <option key={type} value={type}>
                                                {t(`resourceType.${type}`)}
                                            </option>
                                        ),
                                    )}
                                </select>
                            </Field>
                            <Field
                                id={`${id}-language`}
                                label={t('cms.resources.fields.language')}
                                error={form.errors.language}
                            >
                                <select
                                    id={`${id}-language`}
                                    name="language"
                                    value={form.data.language}
                                    className={selectClass}
                                    onChange={(event) =>
                                        form.setData(
                                            'language',
                                            event.target.value as 'es' | 'en',
                                        )
                                    }
                                >
                                    <option value="es">
                                        {t('languages.es')}
                                    </option>
                                    <option value="en">
                                        {t('languages.en')}
                                    </option>
                                </select>
                            </Field>
                            <Field
                                id={`${id}-difficulty`}
                                label={t('cms.resources.fields.difficulty')}
                                error={form.errors.difficulty}
                            >
                                <select
                                    id={`${id}-difficulty`}
                                    name="difficulty"
                                    value={form.data.difficulty ?? ''}
                                    className={selectClass}
                                    onChange={(event) =>
                                        form.setData(
                                            'difficulty',
                                            event.target.value === ''
                                                ? null
                                                : (event.target
                                                      .value as Difficulty),
                                        )
                                    }
                                >
                                    <option value="">
                                        {t('cms.resources.fields.noDifficulty')}
                                    </option>
                                    {Object.values(Difficulties).map(
                                        (level) => (
                                            <option key={level} value={level}>
                                                {t(`difficulty.${level}`)}
                                            </option>
                                        ),
                                    )}
                                </select>
                            </Field>
                        </div>

                        <div className="flex items-start gap-3">
                            <Checkbox
                                id={`${id}-official`}
                                checked={form.data.is_official}
                                aria-describedby={`${id}-official-help`}
                                onCheckedChange={(checked) =>
                                    form.setData(
                                        'is_official',
                                        checked === true,
                                    )
                                }
                            />
                            <div className="grid gap-1">
                                <Label htmlFor={`${id}-official`}>
                                    {t('cms.resources.fields.official')}
                                </Label>
                                <p
                                    id={`${id}-official-help`}
                                    className="text-xs text-muted-foreground"
                                >
                                    {t('cms.resources.fields.officialHelp')}
                                </p>
                            </div>
                        </div>

                        <Field
                            id={`${id}-description`}
                            label={t('cms.resources.fields.description')}
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

                    {resource !== null && (
                        <aside className="space-y-4 xl:sticky xl:top-4 xl:self-start">
                            <section className="space-y-3 rounded-xl border p-4">
                                <div className="flex items-center justify-between gap-2">
                                    <h2 className="font-medium">
                                        {t('cms.resources.status')}
                                    </h2>
                                    <ContentStatusBadge
                                        status={resource.status}
                                    />
                                </div>
                                <StatusActions
                                    entity="resource"
                                    name={resource.title}
                                    current={resource.status}
                                    actions={status_actions}
                                    url={status.url(resource.id)}
                                />
                            </section>

                            <section className="space-y-3 rounded-xl border p-4">
                                <div className="flex items-center justify-between gap-2">
                                    <h2 className="font-medium">
                                        {t('cms.resources.linkTitle')}
                                    </h2>
                                    <LinkStatusBadge
                                        status={resource.link_status}
                                    />
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    {resource.last_checked_at === null
                                        ? t('cms.resources.neverChecked')
                                        : t('cms.resources.checkedAt', {
                                              date: formatDateTime(
                                                  resource.last_checked_at,
                                              ),
                                              status:
                                                  resource.last_http_status ??
                                                  '-',
                                          })}
                                </p>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    disabled={verifying}
                                    onClick={verifyNow}
                                >
                                    <RefreshCw aria-hidden="true" />
                                    {t('cms.resources.verifyNow')}
                                </Button>
                                <p className="text-xs text-muted-foreground">
                                    {t('cms.resources.verifyHelp')}
                                </p>
                            </section>

                            <section className="space-y-2 rounded-xl border p-4">
                                <h2 className="font-medium">
                                    {t('cms.resources.usedInTitle')}
                                </h2>
                                {lessons.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        {t('cms.resources.notUsed')}
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
