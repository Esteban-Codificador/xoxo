import { TriangleAlert } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { t } from '@/i18n';
import { cn } from '@/lib/utils';

/** Inline failure of one section; full-page errors use pages/errors/error. */
export function ErrorState({
    title,
    description,
    onRetry,
    className,
}: {
    title?: string;
    description?: string;
    onRetry?: () => void;
    className?: string;
}) {
    return (
        <div
            role="alert"
            className={cn(
                'flex flex-col items-start gap-3 rounded-xl border border-destructive/30 bg-destructive/5 p-5',
                className,
            )}
        >
            <div className="flex items-start gap-3">
                <TriangleAlert
                    className="mt-0.5 size-5 shrink-0 text-destructive"
                    aria-hidden="true"
                />
                <div className="space-y-1">
                    <p className="font-medium">
                        {title ?? t('errors.loadFailed')}
                    </p>
                    {description && (
                        <p className="text-sm text-muted-foreground">
                            {description}
                        </p>
                    )}
                </div>
            </div>
            {onRetry && (
                <Button variant="outline" size="sm" onClick={onRetry}>
                    {t('common.retry')}
                </Button>
            )}
        </div>
    );
}
