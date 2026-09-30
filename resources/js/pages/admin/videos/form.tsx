import { Head, Link, router, setLayoutProps, useForm } from '@inertiajs/react';
import { ExternalLink, RefreshCw, Search } from 'lucide-react';
import type { FormEvent } from 'react';
import { useId, useState } from 'react';
import { PageHeader } from '@/components/page-header';
import { StatusActions } from '@/components/publishing/status-actions';
import {
    ContentStatusBadge,
    VideoAvailabilityBadge,
} from '@/components/publishing/status-badges';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Field } from '@/features/cms/field';
import { SaveBar } from '@/features/cms/save-bar';
import { useUnsavedChangesGuard } from '@/features/cms/use-unsaved-changes-guard';
import {
    useVideoLookup,
    VideoLookupResult,
} from '@/features/videos/video-lookup';
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
} from '@/routes/admin/videos';
import type { ContentStatus, Difficulty, LinkStatus } from '@/types/enums';
import { Difficulty as Difficulties } from '@/types/enums';

type VideoFields = {
    url: string;
    title: string;
    instructor: string;
    description: string;
    duration: string;
    difficulty: Difficulty | null;
    language: 'es' | 'en';
};

type Props = {
    video: {
        id: number;
        external_id: string;
        url: string;
        title: string;
        instructor: string | null;
        description: string | null;
        duration: string | null;
        difficulty: Difficulty | null;
        language: 'es' | 'en';
        thumbnail_url: string | null;
        status: ContentStatus;
        link_status: LinkStatus;
        last_checked_at: string | null;
        last_http_status: number | null;
    } | null;
    lessons: { slug: string; title: string }[];
    status_actions: ContentStatus[];
};

const selectClass =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

/**
 * Creating redirects to the edit page, which renders this same component:
 * the key gives the saved video a fresh form instead of the create one.
 */
export default function AdminVideoForm(props: Props) {
    return <VideoForm key={props.video?.id ?? 'new'} {...props} />;
}

function VideoForm({ video, lessons, status_actions }: Props) {
    const heading =
        video === null
            ? t('cms.videos.createHead')
            : t('cms.videos.editHead', { video: video.title });

    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.adminOverview'), href: dashboard() },
            { title: t('nav.videos'), href: index() },
            video === null
                ? { title: t('cms.videos.createHead'), href: create() }
                : { title: video.title, href: edit(video.id) },
        ],
    });

    const id = useId();
    const [verifying, setVerifying] = useState(false);
    const found = useVideoLookup();
    const form = useForm<VideoFields>({
        url: video?.url ?? '',
        title: video?.title ?? '',
        instructor: video?.instructor ?? '',
        description: video?.description ?? '',
        duration: video?.duration ?? '',
        difficulty: video?.difficulty ?? null,
        language: video?.language ?? 'en',
    });

    useUnsavedChangesGuard(form.isDirty);

    const lookUp = () => {
        void found.lookup(form.data.url).then((result) => {
            // A new video takes YouTube's title and channel while the fields are empty.
            if (result?.title && form.data.title.trim() === '') {
                form.setData((data) => ({
                    ...data,
                    title: result.title ?? data.title,
                    instructor: result.instructor ?? data.instructor,
                }));
            }
        });
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (video === null) {
            form.post(store.url());
        } else {
            form.put(update.url(video.id), {
                preserveScroll: true,
                onSuccess: () => form.setDefaults(),
            });
        }
    };

    const verifyNow = () => {
        if (video !== null) {
            router.post(
                verify.url(video.id),
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
                    title={video === null ? heading : video.title}
                    description={t('cms.videos.description')}
                />

                <div className="grid gap-8 xl:grid-cols-[minmax(0,1fr)_20rem]">
                    <form onSubmit={submit} className="min-w-0 space-y-5">
                        <h2 className="font-medium">
                            {t('cms.videos.details')}
                        </h2>

                        <Field
                            id={`${id}-url`}
                            label={t('cms.videos.fields.url')}
                            help={t('cms.videos.fields.urlHelp')}
                            error={form.errors.url ?? found.error ?? undefined}
                        >
                            <div className="flex flex-wrap gap-2">
                                <Input
                                    id={`${id}-url`}
                                    name="url"
                                    inputMode="url"
                                    value={form.data.url}
                                    maxLength={2048}
                                    spellCheck={false}
                                    required
                                    placeholder="https://www.youtube.com/watch?v=…"
                                    className="min-w-0 flex-1 font-mono"
                                    aria-describedby={`${id}-url-help`}
                                    onChange={(event) => {
                                        form.setData('url', event.target.value);
                                        found.reset();
                                    }}
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={
                                        found.processing ||
                                        form.data.url.trim() === ''
                                    }
                                    onClick={lookUp}
                                >
                                    <Search aria-hidden="true" />
                                    {found.processing
                                        ? t('cms.videos.lookup.searching')
                                        : t('cms.videos.fields.lookup')}
                                </Button>
                            </div>
                        </Field>

                        {found.result !== null && (
                            <VideoLookupResult
                                result={found.result}
                                onUse={() =>
                                    form.setData((data) => ({
                                        ...data,
                                        title:
                                            found.result?.title ?? data.title,
                                        instructor:
                                            found.result?.instructor ??
                                            data.instructor,
                                    }))
                                }
                            />
                        )}

                        <Field
                            id={`${id}-title`}
                            label={t('cms.videos.fields.title')}
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

                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field
                                id={`${id}-instructor`}
                                label={t('cms.videos.fields.instructor')}
                                error={form.errors.instructor}
                            >
                                <Input
                                    id={`${id}-instructor`}
                                    name="instructor"
                                    value={form.data.instructor}
                                    maxLength={160}
                                    onChange={(event) =>
                                        form.setData(
                                            'instructor',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                id={`${id}-duration`}
                                label={t('cms.videos.fields.duration')}
                                help={t('cms.videos.fields.durationHelp')}
                                error={form.errors.duration}
                            >
                                <Input
                                    id={`${id}-duration`}
                                    name="duration"
                                    value={form.data.duration}
                                    maxLength={8}
                                    inputMode="numeric"
                                    placeholder="12:34"
                                    className="tabular-nums"
                                    aria-describedby={`${id}-duration-help`}
                                    onChange={(event) =>
                                        form.setData(
                                            'duration',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                id={`${id}-language`}
                                label={t('cms.videos.fields.language')}
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
                                label={t('cms.videos.fields.difficulty')}
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
                                        {t('cms.videos.fields.noDifficulty')}
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

                        <Field
                            id={`${id}-description`}
                            label={t('cms.videos.fields.description')}
                            help={t('cms.videos.fields.descriptionHelp')}
                            error={form.errors.description}
                        >
                            <Textarea
                                id={`${id}-description`}
                                name="description"
                                value={form.data.description}
                                maxLength={2000}
                                rows={4}
                                aria-describedby={`${id}-description-help`}
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

                    {video !== null && (
                        <aside className="space-y-4 xl:sticky xl:top-4 xl:self-start">
                            <section className="space-y-3 rounded-xl border p-4">
                                <div className="flex items-center justify-between gap-2">
                                    <h2 className="font-medium">
                                        {t('cms.videos.status')}
                                    </h2>
                                    <ContentStatusBadge status={video.status} />
                                </div>
                                <StatusActions
                                    entity="video"
                                    name={video.title}
                                    current={video.status}
                                    actions={status_actions}
                                    url={status.url(video.id)}
                                />
                            </section>

                            <section className="space-y-3 rounded-xl border p-4">
                                <div className="flex items-center justify-between gap-2">
                                    <h2 className="font-medium">
                                        {t('cms.videos.availabilityTitle')}
                                    </h2>
                                    <VideoAvailabilityBadge
                                        status={video.link_status}
                                    />
                                </div>
                                {video.thumbnail_url !== null && (
                                    <img
                                        src={video.thumbnail_url}
                                        alt=""
                                        width={288}
                                        height={162}
                                        className="aspect-video w-full rounded-md border object-cover"
                                    />
                                )}
                                <p className="text-sm text-muted-foreground">
                                    {video.last_checked_at === null
                                        ? t('cms.videos.neverChecked')
                                        : t('cms.videos.checkedAt', {
                                              date: formatDateTime(
                                                  video.last_checked_at,
                                              ),
                                          })}
                                </p>
                                {video.link_status !== 'OK' && (
                                    <p className="text-sm">
                                        {t('cms.videos.hiddenUntilChecked')}
                                    </p>
                                )}
                                <div className="flex flex-wrap gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        disabled={verifying}
                                        onClick={verifyNow}
                                    >
                                        <RefreshCw aria-hidden="true" />
                                        {t('cms.videos.verifyNow')}
                                    </Button>
                                    <Button asChild variant="ghost" size="sm">
                                        <a
                                            href={video.url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            <ExternalLink aria-hidden="true" />
                                            {t('cms.videos.watch')}
                                            <span className="sr-only">
                                                {t('richContent.opensInNewTab')}
                                            </span>
                                        </a>
                                    </Button>
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    {t('cms.videos.verifyHelp')}
                                </p>
                            </section>

                            <section className="space-y-2 rounded-xl border p-4">
                                <h2 className="font-medium">
                                    {t('cms.videos.usedInTitle')}
                                </h2>
                                {lessons.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        {t('cms.videos.notUsed')}
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
