import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { ArrowLeft, BookOpen, Clock, Signal } from 'lucide-react';
import { useMemo } from 'react';
import type { LessonLink } from '@/features/lesson/lesson-pager';
import { LessonPager } from '@/features/lesson/lesson-pager';
import type { ResourceLink } from '@/features/lesson/resource-list';
import { ResourceList } from '@/features/lesson/resource-list';
import { TableOfContents } from '@/features/lesson/table-of-contents';
import { collectHeadings, RichContentRenderer } from '@/features/rich-content';
import type { RichContent } from '@/features/rich-content';
import { t } from '@/i18n';
import { formatDate, formatMinutes } from '@/lib/format';
import { dashboard } from '@/routes';
import { show as showLesson } from '@/routes/lessons';
import { show as showTrack } from '@/routes/tracks';
import type { ContentType, DependencyKind, Difficulty } from '@/types/enums';

type Props = {
    roadmap: { slug: string; title: string };
    track: { slug: string; title: string };
    module: { slug: string; title: string };
    lesson: {
        slug: string;
        title: string;
        summary: string;
        why_it_matters: string;
        learning_objectives: string[];
        content_type: ContentType;
        difficulty: Difficulty;
        estimated_minutes: number;
        body: RichContent;
        version: number;
        published_at: string;
    };
    prerequisites: (LessonLink & { kind: DependencyKind })[];
    skills: { slug: string; name: string }[];
    resources: ResourceLink[];
    previous: LessonLink | null;
    next: LessonLink | null;
};

export default function LessonShow({
    roadmap,
    track,
    module,
    lesson,
    prerequisites,
    skills,
    resources,
    previous,
    next,
}: Props) {
    const trackHref = showTrack({ roadmap: roadmap.slug, track: track.slug });

    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.dashboard'), href: dashboard() },
            { title: track.title, href: trackHref },
            { title: lesson.title, href: showLesson(lesson.slug) },
        ],
    });

    const headings = useMemo(
        () => collectHeadings(lesson.body.doc),
        [lesson.body],
    );

    return (
        <>
            <Head title={lesson.title} />

            <div className="mx-auto w-full max-w-6xl px-4 py-8 md:px-6 xl:grid xl:grid-cols-[minmax(0,1fr)_15rem] xl:gap-12">
                <article className="min-w-0 space-y-10">
                    <header className="max-w-[72ch] space-y-4">
                        <Link
                            href={`${trackHref.url}#${module.slug}`}
                            className="text-sm text-muted-foreground underline-offset-4 hover:underline"
                        >
                            {t('lesson.module', { module: module.title })}
                        </Link>
                        <h1 className="text-3xl font-semibold tracking-tight text-balance">
                            {lesson.title}
                        </h1>
                        <ul className="flex flex-wrap gap-x-5 gap-y-2 text-sm text-muted-foreground">
                            <li className="flex items-center gap-1.5">
                                <BookOpen
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                {t(`contentType.${lesson.content_type}`)}
                            </li>
                            <li className="flex items-center gap-1.5">
                                <Signal className="size-4" aria-hidden="true" />
                                {t(`difficulty.${lesson.difficulty}`)}
                            </li>
                            <li className="flex items-center gap-1.5">
                                <Clock className="size-4" aria-hidden="true" />
                                {formatMinutes(lesson.estimated_minutes)}
                            </li>
                        </ul>
                        <p className="text-lg text-muted-foreground">
                            {lesson.summary}
                        </p>
                    </header>

                    <div className="grid max-w-[72ch] gap-4 md:grid-cols-2">
                        <section
                            aria-labelledby="why"
                            className="rounded-xl border bg-muted/40 p-5"
                        >
                            <h2 id="why" className="mb-2 font-medium">
                                {t('lesson.whyItMatters')}
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                {lesson.why_it_matters}
                            </p>
                        </section>
                        <section
                            aria-labelledby="objectives"
                            className="rounded-xl border p-5"
                        >
                            <h2 id="objectives" className="mb-2 font-medium">
                                {t('lesson.objectives')}
                            </h2>
                            <ul className="list-disc space-y-1 pl-5 text-sm text-muted-foreground marker:text-muted-foreground">
                                {lesson.learning_objectives.map((objective) => (
                                    <li key={objective}>{objective}</li>
                                ))}
                            </ul>
                        </section>
                    </div>

                    {prerequisites.length > 0 && (
                        <section
                            aria-labelledby="prerequisites"
                            className="max-w-[72ch] space-y-2"
                        >
                            <h2
                                id="prerequisites"
                                className="text-sm font-medium"
                            >
                                {t('lesson.prerequisites')}
                            </h2>
                            <ul className="flex flex-wrap gap-2">
                                {prerequisites.map((prerequisite) => (
                                    <li key={prerequisite.slug}>
                                        <Link
                                            href={showLesson(prerequisite.slug)}
                                            className="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm hover:bg-muted/50"
                                        >
                                            {prerequisite.title}
                                            <span className="text-xs text-muted-foreground">
                                                {t(
                                                    `dependencyKind.${prerequisite.kind}`,
                                                )}
                                            </span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}

                    <details className="max-w-[72ch] rounded-xl border p-4 xl:hidden">
                        <summary className="cursor-pointer text-sm font-medium">
                            {t('richContent.tableOfContents')}
                        </summary>
                        <TableOfContents
                            headings={headings}
                            className="mt-3 [&>p]:hidden"
                        />
                    </details>

                    <RichContentRenderer content={lesson.body} />

                    {skills.length > 0 && (
                        <section
                            aria-labelledby="skills"
                            className="max-w-[72ch] space-y-3"
                        >
                            <h2 id="skills" className="text-lg font-semibold">
                                {t('lesson.skills')}
                            </h2>
                            <ul className="flex flex-wrap gap-2">
                                {skills.map((skill) => (
                                    <li
                                        key={skill.slug}
                                        className="rounded-md bg-muted px-2.5 py-1 text-sm"
                                    >
                                        {skill.name}
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}

                    {resources.length > 0 && (
                        <section
                            aria-labelledby="resources"
                            className="max-w-[72ch] space-y-3"
                        >
                            <h2
                                id="resources"
                                className="text-lg font-semibold"
                            >
                                {t('lesson.resources')}
                            </h2>
                            <ResourceList resources={resources} />
                        </section>
                    )}

                    <footer className="max-w-[72ch] space-y-6 border-t pt-6">
                        <LessonPager previous={previous} next={next} />
                        <div className="flex flex-wrap items-center justify-between gap-3 text-sm text-muted-foreground">
                            <Link
                                href={trackHref}
                                className="inline-flex items-center gap-1.5 hover:text-foreground"
                            >
                                <ArrowLeft
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                {t('lesson.backToTrack', {
                                    track: track.title,
                                })}
                            </Link>
                            <span>
                                {t('lesson.version', {
                                    version: lesson.version,
                                    date: formatDate(lesson.published_at),
                                })}
                            </span>
                        </div>
                    </footer>
                </article>

                <aside className="hidden xl:block">
                    <TableOfContents
                        headings={headings}
                        className="sticky top-20"
                    />
                </aside>
            </div>
        </>
    );
}
