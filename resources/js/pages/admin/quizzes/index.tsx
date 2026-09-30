import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { ListChecks, Pencil } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { ContentStatusBadge } from '@/components/publishing/status-badges';
import { t } from '@/i18n';
import { dashboard } from '@/routes/admin';
import { edit as editQuiz } from '@/routes/admin/lessons/quiz';
import { index } from '@/routes/admin/quizzes';
import type { ContentStatus } from '@/types/enums';

type QuizRow = {
    id: number;
    title: string;
    lesson: { slug: string; title: string };
    track: string;
    status: ContentStatus;
    questions: number;
    attempts: number;
    average_score: number | null;
};

export default function AdminQuizzesIndex({ quizzes }: { quizzes: QuizRow[] }) {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.adminOverview'), href: dashboard() },
            { title: t('nav.quizzes'), href: index() },
        ],
    });

    return (
        <>
            <Head title={t('cms.quizzes.head')} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('cms.quizzes.title')}
                    description={t('cms.quizzes.description')}
                />

                {quizzes.length === 0 ? (
                    <EmptyState
                        icon={ListChecks}
                        title={t('cms.quizzes.empty')}
                    />
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full min-w-[48rem] table-fixed text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th
                                        scope="col"
                                        className="px-4 py-2 font-medium"
                                    >
                                        {t('cms.quizzes.quiz')}
                                    </th>
                                    <th
                                        scope="col"
                                        className="w-32 px-4 py-2 font-medium"
                                    >
                                        {t('cms.lessons.status')}
                                    </th>
                                    <th
                                        scope="col"
                                        className="w-24 px-4 py-2 text-right font-medium"
                                    >
                                        {t('cms.quizzes.questions')}
                                    </th>
                                    <th
                                        scope="col"
                                        className="w-24 px-4 py-2 text-right font-medium"
                                    >
                                        {t('cms.quizzes.attempts')}
                                    </th>
                                    <th
                                        scope="col"
                                        className="w-24 px-4 py-2 text-right font-medium"
                                    >
                                        {t('cms.quizzes.average')}
                                    </th>
                                    <th scope="col" className="w-16 px-4 py-2">
                                        <span className="sr-only">
                                            {t('cms.lessons.edit')}
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {quizzes.map((quiz) => (
                                    <tr
                                        key={quiz.id}
                                        className="border-t align-top"
                                    >
                                        <th
                                            scope="row"
                                            className="px-4 py-3 text-left font-normal"
                                        >
                                            <span className="block font-medium">
                                                {quiz.title}
                                            </span>
                                            <span className="block text-xs text-muted-foreground">
                                                {quiz.track} ·{' '}
                                                {quiz.lesson.title}
                                            </span>
                                        </th>
                                        <td className="px-4 py-3">
                                            <ContentStatusBadge
                                                status={quiz.status}
                                            />
                                        </td>
                                        <td className="px-4 py-3 text-right tabular-nums">
                                            {quiz.questions}
                                        </td>
                                        <td className="px-4 py-3 text-right tabular-nums">
                                            {quiz.attempts}
                                        </td>
                                        <td className="px-4 py-3 text-right tabular-nums">
                                            {quiz.average_score === null
                                                ? '—'
                                                : `${quiz.average_score}\u00a0%`}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Link
                                                href={editQuiz(
                                                    quiz.lesson.slug,
                                                )}
                                                aria-label={t(
                                                    'cms.quizzes.edit',
                                                    {
                                                        lesson: quiz.lesson
                                                            .title,
                                                    },
                                                )}
                                                className="inline-flex size-8 items-center justify-center rounded-md hover:bg-muted"
                                            >
                                                <Pencil
                                                    className="size-4"
                                                    aria-hidden="true"
                                                />
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}
