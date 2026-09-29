import { t } from '@/i18n';
import { cn } from '@/lib/utils';
import type { ContentStatus, LinkStatus } from '@/types/enums';

const base =
    'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium whitespace-nowrap';

// Publishing statuses reuse the state palette: neutral → review (amber) → live (green).
const contentStatus: Record<ContentStatus, string> = {
    DRAFT: 'bg-state-locked-soft text-state-locked',
    REVIEW: 'bg-state-mastered-soft text-state-mastered',
    PUBLISHED: 'bg-state-completed-soft text-state-completed',
    ARCHIVED: 'bg-muted text-muted-foreground line-through',
};

const linkStatus: Record<LinkStatus, string> = {
    UNCHECKED: 'bg-state-locked-soft text-state-locked',
    OK: 'bg-state-completed-soft text-state-completed',
    REDIRECTED: 'bg-state-mastered-soft text-state-mastered',
    BROKEN: 'bg-destructive/10 text-destructive',
};

export function ContentStatusBadge({ status }: { status: ContentStatus }) {
    return (
        <span className={cn(base, contentStatus[status])}>
            {t(`contentStatus.${status}`)}
        </span>
    );
}

export function LinkStatusBadge({ status }: { status: LinkStatus }) {
    return (
        <span className={cn(base, linkStatus[status])}>
            {t(`linkStatus.${status}`)}
        </span>
    );
}
