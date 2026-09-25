import { Head, Link, router } from '@inertiajs/react';
import { Inbox } from 'lucide-react';
import type { ReactNode } from 'react';
import AppearanceToggleTab from '@/components/appearance-tabs';
import { EmptyState } from '@/components/empty-state';
import { ErrorState } from '@/components/error-state';
import { PageHeader } from '@/components/page-header';
import { CardGridSkeleton, ListSkeleton } from '@/components/skeletons';
import { Button } from '@/components/ui/button';
import { ProgressBar } from '@/features/progress/progress-bar';
import { StateBadge } from '@/features/progress/state-badge';
import {
    ContentStatusBadge,
    LinkStatusBadge,
} from '@/features/publishing/status-badges';
import { RichContentRenderer } from '@/features/rich-content';
import type { RichContent } from '@/features/rich-content';
import { t } from '@/i18n';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { ContentStatus, LinkStatus, NodeState } from '@/types/enums';

type Props = {
    lessons: { slug: string; title: string }[];
    lesson: { slug: string; title: string; body: RichContent } | null;
};

const states = Object.values(NodeState);
const tokenGroups = [
    [
        'state-locked',
        'state-available',
        'state-in-progress',
        'state-completed',
        'state-mastered',
    ],
    [
        'callout-note',
        'callout-tip',
        'callout-important',
        'callout-warning',
        'callout-caution',
    ],
];

function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="space-y-4 border-t pt-8">
            <h2 className="text-lg font-semibold">{title}</h2>
            {children}
        </section>
    );
}

export default function DesignSystem({ lessons, lesson }: Props) {
    return (
        <>
            <Head title={t('designSystem.head')} />

            <main className="mx-auto flex max-w-5xl flex-col gap-8 px-4 py-8 md:px-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <PageHeader
                        title={t('designSystem.title')}
                        description={t('designSystem.description')}
                    />
                    <AppearanceToggleTab />
                </div>

                <Section title={t('designSystem.tokens')}>
                    {tokenGroups.map((group) => (
                        <ul
                            key={group[0]}
                            className="grid grid-cols-2 gap-3 sm:grid-cols-5"
                        >
                            {group.map((token) => (
                                <li key={token} className="space-y-1.5">
                                    <div
                                        className="h-10 rounded-md border"
                                        style={{
                                            background: `var(--${token})`,
                                        }}
                                    />
                                    <code className="text-xs text-muted-foreground">
                                        {token}
                                    </code>
                                </li>
                            ))}
                        </ul>
                    ))}
                </Section>

                <Section title={t('designSystem.states')}>
                    <div className="flex flex-wrap gap-2">
                        {states.map((state) => (
                            <StateBadge key={state} state={state} />
                        ))}
                    </div>
                </Section>

                <Section title={t('designSystem.progress')}>
                    <div className="grid max-w-md gap-3">
                        {states.map((state, index) => (
                            <ProgressBar
                                key={state}
                                state={state}
                                value={index * 25}
                                showValue
                            />
                        ))}
                    </div>
                </Section>

                <Section title={t('designSystem.statuses')}>
                    <div className="flex flex-wrap gap-2">
                        {Object.values(ContentStatus).map((status) => (
                            <ContentStatusBadge key={status} status={status} />
                        ))}
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {Object.values(LinkStatus).map((status) => (
                            <LinkStatusBadge key={status} status={status} />
                        ))}
                    </div>
                </Section>

                <Section title={t('designSystem.feedback')}>
                    <div className="grid gap-4 md:grid-cols-2">
                        <EmptyState
                            icon={Inbox}
                            title={t('designSystem.emptyExample')}
                            description={t(
                                'designSystem.emptyExampleDescription',
                            )}
                            action={
                                <Button asChild variant="outline" size="sm">
                                    <Link href={dashboard()}>
                                        {t('errors.goHome')}
                                    </Link>
                                </Button>
                            }
                        />
                        <ErrorState onRetry={() => router.reload()} />
                    </div>
                    <CardGridSkeleton count={3} />
                    <ListSkeleton rows={3} />
                </Section>

                <Section title={t('designSystem.lesson')}>
                    {lesson === null ? (
                        <p className="text-sm text-muted-foreground">
                            {t('designSystem.noLesson')}
                        </p>
                    ) : (
                        <div className="flex flex-col gap-8 lg:flex-row-reverse lg:items-start">
                            <nav
                                aria-label={t('designSystem.chooseLesson')}
                                className="lg:w-56 lg:shrink-0"
                            >
                                <p className="mb-2 text-sm font-medium">
                                    {t('designSystem.chooseLesson')}
                                </p>
                                <ul className="space-y-1 text-sm">
                                    {lessons.map((item) => (
                                        <li key={item.slug}>
                                            <Link
                                                href={`?lesson=${encodeURIComponent(item.slug)}`}
                                                preserveScroll
                                                aria-current={
                                                    item.slug === lesson.slug
                                                        ? 'page'
                                                        : undefined
                                                }
                                                className={cn(
                                                    'block rounded-md px-2 py-1 hover:bg-muted',
                                                    item.slug === lesson.slug
                                                        ? 'bg-muted font-medium'
                                                        : 'text-muted-foreground',
                                                )}
                                            >
                                                {item.title}
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </nav>
                            <article className="min-w-0 flex-1">
                                <h1 className="mb-6 text-3xl font-semibold tracking-tight">
                                    {lesson.title}
                                </h1>
                                <RichContentRenderer
                                    key={lesson.slug}
                                    content={lesson.body}
                                />
                            </article>
                        </div>
                    )}
                </Section>
            </main>
        </>
    );
}
