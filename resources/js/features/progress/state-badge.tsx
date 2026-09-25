import type { LucideIcon } from 'lucide-react';
import { Award, Circle, CircleCheck, CircleDot, Lock } from 'lucide-react';
import { t } from '@/i18n';
import { cn } from '@/lib/utils';
import type { NodeState } from '@/types/enums';

/** Color, icon and text together: a state is never conveyed by color alone. */
const appearance: Record<NodeState, { icon: LucideIcon; className: string }> = {
    LOCKED: { icon: Lock, className: 'bg-state-locked-soft text-state-locked' },
    AVAILABLE: {
        icon: Circle,
        className: 'bg-state-available-soft text-state-available',
    },
    IN_PROGRESS: {
        icon: CircleDot,
        className: 'bg-state-in-progress-soft text-state-in-progress',
    },
    COMPLETED: {
        icon: CircleCheck,
        className: 'bg-state-completed-soft text-state-completed',
    },
    MASTERED: {
        icon: Award,
        className: 'bg-state-mastered-soft text-state-mastered',
    },
};

export function StateBadge({
    state,
    className,
}: {
    state: NodeState;
    className?: string;
}) {
    const { icon: Icon, className: stateClassName } = appearance[state];

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium whitespace-nowrap',
                stateClassName,
                className,
            )}
        >
            <Icon className="size-3.5" aria-hidden="true" />
            {t(`states.${state}`)}
        </span>
    );
}
