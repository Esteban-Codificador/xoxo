import type { NodeState, UnlockPolicy } from '@/types/enums';

/** A REQUIRED dependency not met yet (app/Domain/Learning/State/Blocker.php). */
export type Blocker = {
    type: 'lesson' | 'track';
    slug: string;
    title: string;
    progress: number | null;
    required: number | null;
};

export type TrackProgress = {
    state: NodeState;
    progress: number;
    completed: number;
    total: number;
    blockers: Blocker[];
};

export type LessonProgress = {
    state: NodeState;
    blockers: Blocker[];
    policy: UnlockPolicy;
    can_progress: boolean;
    completed_at: string | null;
};
