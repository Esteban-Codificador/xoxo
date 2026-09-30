import { Link } from '@inertiajs/react';
import { ProgressBar } from '@/features/progress/progress-bar';
import { StateBadge } from '@/features/progress/state-badge';
import { t } from '@/i18n';
import { show as showSkill } from '@/routes/skills';
import type { SkillSummary } from './progress';
import { lessonsDone } from './progress';

/** One skill in the list: state, progress and how many lessons build it. */
export function SkillCard({ skill }: { skill: SkillSummary }) {
    const { progress } = skill;

    return (
        <li className="relative flex flex-col gap-3 rounded-xl border bg-card p-5 transition-colors hover:bg-muted/40">
            <div className="flex items-start justify-between gap-3">
                <h2 className="font-medium">
                    <Link
                        href={showSkill(skill.slug)}
                        className="after:absolute after:inset-0 focus-visible:outline-none"
                        aria-label={t('skills.open', { skill: skill.name })}
                    >
                        {skill.name}
                    </Link>
                </h2>
                <StateBadge state={progress.state} className="shrink-0" />
            </div>
            <p className="line-clamp-3 text-sm text-muted-foreground">
                {skill.description}
            </p>
            <div className="mt-auto space-y-1.5">
                <ProgressBar
                    value={progress.progress}
                    state={progress.state}
                    label={t('skills.progressLabel', {
                        skill: skill.name,
                        value: progress.progress,
                    })}
                    showValue
                />
                <p className="text-xs text-muted-foreground">
                    {lessonsDone(progress)}
                    {' · '}
                    {t(`difficulty.${skill.difficulty}`)}
                </p>
            </div>
        </li>
    );
}
