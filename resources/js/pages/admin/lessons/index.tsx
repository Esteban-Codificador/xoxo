import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { BookOpen, Pencil } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { ContentStatusBadge } from '@/features/publishing/status-badges';
import { t } from '@/i18n';
import { formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import { edit, index } from '@/routes/admin/lessons';
import type { ContentStatus } from '@/types/enums';

type LessonRow = {
    slug: string;
    title: string;
    track: string;
    module: string;
    status: ContentStatus;
    version: number | null;
    has_unpublished_changes: boolean;
    updated_at: string | null;
    can_edit: boolean;
};

type Props = { lessons: LessonRow[] };

function groupByTrack(lessons: LessonRow[]): [string, LessonRow[]][] {
    const groups = new Map<string, LessonRow[]>();
    lessons.forEach((lesson) => {
        groups.set(lesson.track, [...(groups.get(lesson.track) ?? []), lesson]);
    });

    return [...groups];
}

export default function AdminLessonsIndex({ lessons }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.adminOverview'), href: dashboard() },
            { title: t('nav.lessons'), href: index() },
        ],
    });

    return (
        <>
            <Head title={t('cms.lessons.head')} />

            <div className="flex flex-1 flex-col gap-8 p-4 md:p-6">
                <PageHeader
                    title={t('cms.lessons.title')}
                    description={t('cms.lessons.description')}
                />

                {lessons.length === 0 ? (
                    <EmptyState
                        icon={BookOpen}
                        title={t('cms.lessons.empty')}
                        description={t('cms.lessons.emptyDescription')}
                    />
                ) : (
                    groupByTrack(lessons).map(([track, rows]) => (
                        <section
                            key={track}
                            aria-label={track}
                            className="space-y-3"
                        >
                            <h2 className="font-medium">{track}</h2>
                            <div className="overflow-x-auto rounded-xl border">
                                <table className="w-full min-w-[40rem] table-fixed text-sm">
                                    <thead className="bg-muted/50 text-left">
                                        <tr>
                                            <th
                                                scope="col"
                                                className="px-4 py-2 font-medium"
                                            >
                                                {t('cms.lessons.lesson')}
                                            </th>
                                            <th
                                                scope="col"
                                                className="w-36 px-4 py-2 font-medium"
                                            >
                                                {t('cms.lessons.status')}
                                            </th>
                                            <th
                                                scope="col"
                                                className="w-28 px-4 py-2 font-medium"
                                            >
                                                {t('cms.lessons.version')}
                                            </th>
                                            <th
                                                scope="col"
                                                className="w-44 px-4 py-2 font-medium"
                                            >
                                                {t('cms.lessons.updated')}
                                            </th>
                                            <th
                                                scope="col"
                                                className="w-32 px-4 py-2"
                                            >
                                                <span className="sr-only">
                                                    {t('cms.lessons.edit')}
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {rows.map((lesson) => (
                                            <tr
                                                key={lesson.slug}
                                                className="border-t align-top"
                                            >
                                                <th
                                                    scope="row"
                                                    className="min-w-56 px-4 py-3 text-left font-normal"
                                                >
                                                    <span className="block font-medium">
                                                        {lesson.title}
                                                    </span>
                                                    <span className="text-xs text-muted-foreground">
                                                        {lesson.module}
                                                    </span>
                                                </th>
                                                <td className="px-4 py-3">
                                                    <div className="flex flex-col items-start gap-1">
                                                        <ContentStatusBadge
                                                            status={
                                                                lesson.status
                                                            }
                                                        />
                                                        {lesson.has_unpublished_changes && (
                                                            <span className="text-xs whitespace-nowrap text-muted-foreground">
                                                                {t(
                                                                    'cms.lessons.unpublishedChanges',
                                                                )}
                                                            </span>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap text-muted-foreground tabular-nums">
                                                    {lesson.version === null
                                                        ? t(
                                                              'cms.lessons.notPublished',
                                                          )
                                                        : t(
                                                              'cms.edit.version',
                                                              {
                                                                  version:
                                                                      lesson.version,
                                                              },
                                                          )}
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap text-muted-foreground">
                                                    {lesson.updated_at &&
                                                        formatDateTime(
                                                            lesson.updated_at,
                                                        )}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    {lesson.can_edit ? (
                                                        <Button
                                                            asChild
                                                            size="sm"
                                                            variant="outline"
                                                        >
                                                            <Link
                                                                href={edit(
                                                                    lesson.slug,
                                                                )}
                                                                aria-label={t(
                                                                    'cms.lessons.editLesson',
                                                                    {
                                                                        lesson: lesson.title,
                                                                    },
                                                                )}
                                                            >
                                                                <Pencil aria-hidden="true" />
                                                                {t(
                                                                    'cms.lessons.edit',
                                                                )}
                                                            </Link>
                                                        </Button>
                                                    ) : (
                                                        <span className="text-xs whitespace-nowrap text-muted-foreground">
                                                            {t(
                                                                'cms.lessons.readOnly',
                                                            )}
                                                        </span>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    ))
                )}
            </div>
        </>
    );
}
