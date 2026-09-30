import { t } from '@/i18n';
import type { TrackProgress } from '@/features/progress/types';
import type { Difficulty } from '@/types/enums';

/** Same shape as a track's progress (SkillState::toArray). */
export type SkillProgress = TrackProgress;

export type SkillSummary = {
    slug: string;
    name: string;
    description: string;
    difficulty: Difficulty;
    progress: SkillProgress;
};

/** "1 de 3 lecciones completadas", "0 de 1 lección completada" or "no lessons yet". */
export function lessonsDone(progress: SkillProgress): string {
    if (progress.total === 0) {
        return t('skills.noLessons');
    }

    return progress.total === 1
        ? t('skills.completedOfOne', { completed: progress.completed })
        : t('skills.completedOf', {
              completed: progress.completed,
              total: progress.total,
          });
}
