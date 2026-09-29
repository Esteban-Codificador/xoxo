import type { TrackProgress } from '@/features/progress/types';
import type { DependencyKind, Difficulty, NodeState } from '@/types/enums';

/** A track as the roadmap page receives it (ShowRoadmapController). */
export type RoadmapTrack = {
    slug: string;
    title: string;
    summary: string;
    position: number;
    difficulty: Difficulty;
    estimated_hours: number | null;
    lessons_count: number;
    progress: TrackProgress;
    lessons: {
        slug: string;
        title: string;
        module_slug: string;
        module_title: string;
        state: NodeState;
    }[];
    continue: { slug: string; title: string } | null;
};

export type RoadmapEdge = {
    /** Prerequisite track slug. */
    from: string;
    /** Dependent track slug. */
    to: string;
    kind: DependencyKind;
    min_progress: number;
};
