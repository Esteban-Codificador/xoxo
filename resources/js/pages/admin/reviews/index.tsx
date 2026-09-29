import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { ClipboardCheck } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { t } from '@/i18n';
import { formatDate } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import { changes, edit } from '@/routes/admin/lessons';
import { index } from '@/routes/admin/reviews';

type ReviewRow = {
    id: number;
    lesson: {
        slug: string;
        title: string;
        track: string;
        module: string;
        published_version: number | null;
    };
    submitted_by: string | null;
    submitted_at: string;
    note: string | null;
    edited_since: boolean;
};

type Props = { reviews: ReviewRow[] };

export default function AdminReviewsIndex({ reviews }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.adminOverview'), href: dashboard() },
            { title: t('nav.reviews'), href: index() },
        ],
    });

    return (
        <>
            <Head title={t('cms.reviews.head')} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('cms.reviews.title')}
                    description={t('cms.reviews.description')}
                />

                {reviews.length === 0 ? (
                    <EmptyState
                        icon={ClipboardCheck}
                        title={t('cms.reviews.empty')}
                    />
                ) : (
                    <ul className="divide-y rounded-xl border">
                        {reviews.map((review) => (
                            <li
                                key={review.id}
                                className="flex flex-col gap-3 p-4 sm:flex-row sm:items-start sm:justify-between"
                            >
                                <div className="min-w-0 space-y-1">
                                    <p className="font-medium">
                                        {review.lesson.title}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {t('cms.edit.location', {
                                            track: review.lesson.track,
                                            module: review.lesson.module,
                                        })}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {review.submitted_by === null
                                            ? t('cms.review.submittedOn', {
                                                  date: formatDate(
                                                      review.submitted_at,
                                                  ),
                                              })
                                            : t('cms.review.submittedBy', {
                                                  user: review.submitted_by,
                                                  date: formatDate(
                                                      review.submitted_at,
                                                  ),
                                              })}
                                    </p>
                                    <p className="text-sm whitespace-pre-line">
                                        {review.note ?? (
                                            <span className="text-muted-foreground">
                                                {t('cms.reviews.noNote')}
                                            </span>
                                        )}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {review.lesson.published_version ===
                                        null
                                            ? t('cms.reviews.newLesson')
                                            : review.edited_since &&
                                              t('cms.reviews.editedSince')}
                                    </p>
                                </div>
                                <div className="flex shrink-0 gap-2">
                                    {review.lesson.published_version !==
                                        null && (
                                        <Button
                                            asChild
                                            size="sm"
                                            variant="outline"
                                        >
                                            <Link
                                                href={changes(
                                                    review.lesson.slug,
                                                )}
                                            >
                                                {t('cms.reviews.changes')}
                                            </Link>
                                        </Button>
                                    )}
                                    <Button asChild size="sm">
                                        <Link
                                            href={edit(review.lesson.slug)}
                                            aria-label={t(
                                                'cms.reviews.openLabel',
                                                {
                                                    lesson: review.lesson.title,
                                                },
                                            )}
                                        >
                                            {t('cms.reviews.open')}
                                        </Link>
                                    </Button>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}
