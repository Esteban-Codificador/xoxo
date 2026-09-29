import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { ArrowLeft, BookOpen, Clock, Signal } from 'lucide-react';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import type { VersionEntry } from '@/features/cms/publish-panel';
import type { Changes } from '@/features/cms/lesson-changes';
import { LessonChanges } from '@/features/cms/lesson-changes';
import { RichContentRenderer } from '@/features/rich-content';
import type { RichContent } from '@/features/rich-content';
import { t } from '@/i18n';
import { formatDate, formatMinutes } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import { edit, index } from '@/routes/admin/lessons';
import { show } from '@/routes/admin/lessons/versions';
import type { ContentType, Difficulty } from '@/types/enums';

type Props = {
    lesson: { slug: string; title: string };
    version: VersionEntry;
    /** The version this one is compared with; null for the first. */
    previous: number | null;
    changes: Changes | null;
    content: {
        title: string;
        summary: string;
        why_it_matters: string;
        learning_objectives: string[];
        content_type: ContentType;
        difficulty: Difficulty;
        estimated_minutes: number;
        body: RichContent;
    };
};

export default function AdminLessonVersion({
    lesson,
    version,
    previous,
    changes,
    content,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.adminOverview'), href: dashboard() },
            { title: t('nav.lessons'), href: index() },
            { title: lesson.title, href: edit(lesson.slug) },
            {
                title: t('cms.versions.title', { version: version.version }),
                href: show({ lesson: lesson.slug, version: version.version }),
            },
        ],
    });

    const published =
        version.published_by === null
            ? t('cms.edit.publishedOn', {
                  date: formatDate(version.published_at),
              })
            : t('cms.edit.publishedBy', {
                  date: formatDate(version.published_at),
                  user: version.published_by,
              });

    return (
        <>
            <Head
                title={t('cms.versions.head', {
                    version: version.version,
                    lesson: lesson.title,
                })}
            />

            <div className="flex flex-1 flex-col gap-8 p-4 md:p-6">
                <div className="space-y-3">
                    <PageHeader
                        title={t('cms.versions.title', {
                            version: version.version,
                        })}
                        description={published}
                        actions={
                            <Button asChild variant="outline" size="sm">
                                <Link href={edit(lesson.slug)}>
                                    <ArrowLeft aria-hidden="true" />
                                    {t('cms.versions.backToEditor')}
                                </Link>
                            </Button>
                        }
                    />
                    <div className="flex flex-wrap items-center gap-2 text-sm">
                        {version.current && (
                            <span className="rounded-md bg-state-completed-soft px-1.5 py-0.5 text-xs text-state-completed">
                                {t('cms.edit.current')}
                            </span>
                        )}
                        <p className="text-muted-foreground">
                            {version.change_note ?? t('cms.edit.noNote')}
                        </p>
                    </div>
                </div>

                <section aria-labelledby="changes" className="space-y-4">
                    <h2 id="changes" className="text-lg font-semibold">
                        {previous === null
                            ? t('cms.versions.changes')
                            : t('cms.versions.comparedTo', {
                                  version: previous,
                              })}
                    </h2>
                    {changes === null ? (
                        <p className="text-sm text-muted-foreground">
                            {t('cms.versions.firstVersion')}
                        </p>
                    ) : (
                        <LessonChanges changes={changes} />
                    )}
                </section>

                <section
                    aria-labelledby="content"
                    className="space-y-6 border-t pt-8"
                >
                    <h2 id="content" className="text-lg font-semibold">
                        {t('cms.versions.content')}
                    </h2>
                    <header className="max-w-[72ch] space-y-3">
                        <p className="text-2xl font-semibold tracking-tight text-balance">
                            {content.title}
                        </p>
                        <ul className="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-muted-foreground">
                            <li className="flex items-center gap-1.5">
                                <BookOpen
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                {t(`contentType.${content.content_type}`)}
                            </li>
                            <li className="flex items-center gap-1.5">
                                <Signal className="size-4" aria-hidden="true" />
                                {t(`difficulty.${content.difficulty}`)}
                            </li>
                            <li className="flex items-center gap-1.5">
                                <Clock className="size-4" aria-hidden="true" />
                                {formatMinutes(content.estimated_minutes)}
                            </li>
                        </ul>
                        <p className="text-muted-foreground">
                            {content.summary}
                        </p>
                    </header>
                    <div className="grid max-w-[72ch] gap-4 md:grid-cols-2">
                        <div className="rounded-xl border bg-muted/40 p-5">
                            <h3 className="mb-2 font-medium">
                                {t('lesson.whyItMatters')}
                            </h3>
                            <p className="text-sm text-muted-foreground">
                                {content.why_it_matters}
                            </p>
                        </div>
                        <div className="rounded-xl border p-5">
                            <h3 className="mb-2 font-medium">
                                {t('lesson.objectives')}
                            </h3>
                            <ul className="list-disc space-y-1 pl-5 text-sm text-muted-foreground">
                                {content.learning_objectives.map(
                                    (objective) => (
                                        <li key={objective}>{objective}</li>
                                    ),
                                )}
                            </ul>
                        </div>
                    </div>
                    <RichContentRenderer content={content.body} />
                </section>
            </div>
        </>
    );
}
