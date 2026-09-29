import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import type { Changes } from '@/features/cms/lesson-changes';
import { LessonChanges } from '@/features/cms/lesson-changes';
import { t } from '@/i18n';
import { dashboard } from '@/routes/admin';
import { changes as changesRoute, edit, index } from '@/routes/admin/lessons';

type Props = {
    lesson: { slug: string; title: string };
    /** The published version the working copy is compared with. */
    published: number | null;
    changes: Changes | null;
};

export default function AdminLessonChanges({
    lesson,
    published,
    changes,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.adminOverview'), href: dashboard() },
            { title: t('nav.lessons'), href: index() },
            { title: lesson.title, href: edit(lesson.slug) },
            {
                title: t('cms.versions.changesTitle'),
                href: changesRoute(lesson.slug),
            },
        ],
    });

    return (
        <>
            <Head
                title={t('cms.versions.changesHead', { lesson: lesson.title })}
            />

            <div className="flex flex-1 flex-col gap-8 p-4 md:p-6">
                <PageHeader
                    title={t('cms.versions.changesTitle')}
                    description={
                        published === null
                            ? undefined
                            : t('cms.versions.changesDescription', {
                                  version: published,
                              })
                    }
                    actions={
                        <Button asChild variant="outline" size="sm">
                            <Link href={edit(lesson.slug)}>
                                <ArrowLeft aria-hidden="true" />
                                {t('cms.versions.backToEditor')}
                            </Link>
                        </Button>
                    }
                />

                {changes === null ? (
                    <p className="text-sm text-muted-foreground">
                        {t('cms.versions.neverPublished')}
                    </p>
                ) : (
                    <LessonChanges changes={changes} />
                )}
            </div>
        </>
    );
}
