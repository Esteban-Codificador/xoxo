import { t } from '@/i18n';
import { cn } from '@/lib/utils';
import type { NodeState } from '@/types/enums';

const fill: Record<NodeState, string> = {
    LOCKED: 'bg-state-locked',
    AVAILABLE: 'bg-state-available',
    IN_PROGRESS: 'bg-state-in-progress',
    COMPLETED: 'bg-state-completed',
    MASTERED: 'bg-state-mastered',
};

type Props = {
    /** Percentage computed by the server (0-100). */
    value: number;
    state?: NodeState;
    /** Accessible name; defaults to "Progress: N %". */
    label?: string;
    showValue?: boolean;
    className?: string;
};

export function ProgressBar({
    value,
    state = 'IN_PROGRESS',
    label,
    showValue = false,
    className,
}: Props) {
    const percentage = Math.round(Math.min(100, Math.max(0, value)));

    return (
        <div className={cn('flex items-center gap-3', className)}>
            <div
                role="progressbar"
                aria-valuemin={0}
                aria-valuemax={100}
                aria-valuenow={percentage}
                aria-label={label ?? t('progress.label', { value: percentage })}
                className="h-2 w-full overflow-hidden rounded-full bg-muted"
            >
                <div
                    className={cn(
                        'h-full rounded-full transition-[width] duration-200 motion-reduce:transition-none',
                        fill[state],
                    )}
                    style={{ width: `${percentage}%` }}
                />
            </div>
            {showValue && (
                <span className="w-10 shrink-0 text-right text-xs text-muted-foreground tabular-nums">
                    {percentage} %
                </span>
            )}
        </div>
    );
}
