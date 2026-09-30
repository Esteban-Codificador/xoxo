import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { BlockerNotice } from '@/features/progress/blocker-notice';
import type { ResourceLink } from '@/features/lesson/resource-list';
import { ResourceList } from '@/features/lesson/resource-list';
import { ProgressBar } from '@/features/progress/progress-bar';
import { StateBadge } from '@/features/progress/state-badge';
import type { SkillProgress } from '@/features/skills/progress';
import { lessonsDone } from '@/features/skills/progress';
import { t } from '@/i18n';
import { dashboard } from '@/routes';
import { show as showLesson } from '@/routes/lessons';
import { index, show } from '@/routes/skills';
import type { DependencyKind, Difficulty, NodeState } from '@/types/enums';

type Props = {
    roadmap: { slug: string; title: string };
    skill: {
        slug: string;
        name: string;
        description: string;
        difficulty: Difficulty;
    };
    progress: SkillProgress;
    prerequisites: {
        slug: string;
        name: string;
        kind: DependencyKind;
        min_progress: number;
        state: NodeState;
        progress: number;
    }[];
    enables: { slug: string; name: string; state: NodeState }[];
    lessons: { slug: string; title: string; track: string; state: NodeState }[];
    resources: ResourceLink[];
};

export default function SkillShow({
    roadmap,
    skill,
    progress,
    prerequisites,
    enables,
    lessons,
    resources,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.dashboard'), href: dashboard() },
            { title: t('skills.title'), href: index() },
            { title: skill.name, href: show(skill.slug) },
        ],
    });

    return (
        <>
            <Head title={skill.name} />

            <div className="flex flex-1 flex-col gap-8 p-4 md:p-6">
                <header className="max-w-3xl space-y-3">
                    <p className="text-sm text-muted-foreground">
                        {t('skills.title')} ·{' '}
                        {t(`difficulty.${skill.difficulty}`)}
                    </p>
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {skill.name}
                        </h1>
                        <StateBadge state={progress.state} />
                    </div>
                    <p className="text-muted-foreground">{skill.description}</p>
                    <div className="space-y-1.5 pt-1">
                        <ProgressBar
                            value={progress.progress}
                            state={progress.state}
                            label={t('skills.progressLabel', {
                                skill: skill.name,
                                value: progress.progress,
                            })}
                            showValue
                        />
                        <p className="text-sm text-muted-foreground">
                            {lessonsDone(progress)}
                        </p>
                    </div>
                </header>

                <BlockerNotice
                    blockers={progress.blockers}
                    roadmapSlug={roadmap.slug}
                    strict={false}
                    scope="skill"
                    className="max-w-3xl"
                />

                <section
                    aria-labelledby="lessons"
                    className="max-w-3xl space-y-3"
                >
                    <div className="space-y-1">
                        <h2 id="lessons" className="text-lg font-semibold">
                            {t('skills.lessonsTitle')}
                        </h2>
                        {lessons.length > 1 && (
                            <p className="text-sm text-muted-foreground">
                                {t('skills.lessonsHelp')}
                            </p>
                        )}
                    </div>
                    {lessons.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            {t('skills.noLessonsYet')}
                        </p>
                    ) : (
                        <ol className="divide-y rounded-xl border">
                            {lessons.map((lesson) => (
                                <li
                                    key={lesson.slug}
                                    className="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="min-w-0">
                                        <Link
                                            href={showLesson(lesson.slug)}
                                            className="font-medium underline-offset-4 hover:underline"
                                        >
                                            {lesson.title}
                                        </Link>
                                        <p className="text-sm text-muted-foreground">
                                            {lesson.track}
                                        </p>
                                    </div>
                                    <StateBadge
                                        state={lesson.state}
                                        className="shrink-0 self-start sm:self-center"
                                    />
                                </li>
                            ))}
                        </ol>
                    )}
                </section>

                {prerequisites.length > 0 && (
                    <section
                        aria-labelledby="prerequisites"
                        className="max-w-3xl space-y-3"
                    >
                        <h2
                            id="prerequisites"
                            className="text-lg font-semibold"
                        >
                            {t('skills.prerequisites')}
                        </h2>
                        <ul className="divide-y rounded-xl border">
                            {prerequisites.map((prerequisite) => (
                                <li
                                    key={prerequisite.slug}
                                    className="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <Link
                                            href={show(prerequisite.slug)}
                                            className="font-medium underline-offset-4 hover:underline"
                                        >
                                            {prerequisite.name}
                                        </Link>
                                        <p className="text-sm text-muted-foreground">
                                            {prerequisite.kind === 'REQUIRED'
                                                ? t('skills.required', {
                                                      required:
                                                          prerequisite.min_progress,
                                                  })
                                                : t('skills.recommended')}
                                            {' · '}
                                            {t('progress.label', {
                                                value: prerequisite.progress,
                                            })}
                                        </p>
                                    </div>
                                    <StateBadge
                                        state={prerequisite.state}
                                        className="shrink-0 self-start sm:self-center"
                                    />
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                {enables.length > 0 && (
                    <section
                        aria-labelledby="enables"
                        className="max-w-3xl space-y-3"
                    >
                        <h2 id="enables" className="text-lg font-semibold">
                            {t('skills.enables')}
                        </h2>
                        <ul className="flex flex-wrap gap-2">
                            {enables.map((dependent) => (
                                <li key={dependent.slug}>
                                    <Link
                                        href={show(dependent.slug)}
                                        className="block rounded-md bg-muted px-2.5 py-1 text-sm underline-offset-4 hover:bg-muted/70 hover:underline"
                                    >
                                        {dependent.name}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                {resources.length > 0 && (
                    <section
                        aria-labelledby="resources"
                        className="max-w-3xl space-y-3"
                    >
                        <h2 id="resources" className="text-lg font-semibold">
                            {t('skills.resources')}
                        </h2>
                        <ResourceList resources={resources} />
                    </section>
                )}
            </div>
        </>
    );
}
