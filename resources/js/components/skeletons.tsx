import { Skeleton } from '@/components/ui/skeleton';
import { t } from '@/i18n';

/** Placeholder while deferred props load; announced once to screen readers. */
export function CardGridSkeleton({ count = 3 }: { count?: number }) {
    return (
        <div
            aria-busy="true"
            className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
            <span className="sr-only">{t('common.loading')}</span>
            {Array.from({ length: count }, (_, index) => (
                <div key={index} className="space-y-3 rounded-xl border p-5">
                    <Skeleton className="h-5 w-2/3" />
                    <Skeleton className="h-4 w-full" />
                    <Skeleton className="h-4 w-5/6" />
                    <Skeleton className="h-2 w-full" />
                </div>
            ))}
        </div>
    );
}

export function ListSkeleton({ rows = 5 }: { rows?: number }) {
    return (
        <div aria-busy="true" className="space-y-3">
            <span className="sr-only">{t('common.loading')}</span>
            {Array.from({ length: rows }, (_, index) => (
                <Skeleton key={index} className="h-10 w-full" />
            ))}
        </div>
    );
}
