import { router } from '@inertiajs/react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { Button } from '@/components/ui/button';
import { t } from '@/i18n';
import { cn } from '@/lib/utils';
import type { ContentStatus } from '@/types/enums';

type Action = ContentStatus | 'restore';

/**
 * Buttons for the status changes the server allows this user (the page
 * receives them as `status_actions`). Each one asks for confirmation and
 * explains what learners will see.
 */
export function StatusActions({
    entity,
    name,
    current,
    actions,
    url,
    className,
}: {
    entity: 'track' | 'module';
    name: string;
    current: ContentStatus;
    actions: ContentStatus[];
    url: string;
    className?: string;
}) {
    const [pending, setPending] = useState<ContentStatus | null>(null);
    const [processing, setProcessing] = useState(false);

    // Leaving ARCHIVED for DRAFT reads as "restore".
    const actionOf = (target: ContentStatus): Action =>
        target === 'DRAFT' && current === 'ARCHIVED' ? 'restore' : target;

    const confirm = () => {
        if (pending === null) {
            return;
        }

        router.put(
            url,
            { status: pending },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => {
                    setProcessing(false);
                    setPending(null);
                },
            },
        );
    };

    if (actions.length === 0) {
        return null;
    }

    const action = pending === null ? null : actionOf(pending);

    return (
        <>
            <div className={cn('flex flex-wrap gap-2', className)}>
                {actions.map((target) => (
                    <Button
                        key={target}
                        type="button"
                        size="sm"
                        variant={target === 'PUBLISHED' ? 'default' : 'outline'}
                        onClick={() => setPending(target)}
                    >
                        {t(`cms.status.actions.${actionOf(target)}`)}
                    </Button>
                ))}
            </div>
            <ConfirmDialog
                open={action !== null}
                title={
                    action === null
                        ? ''
                        : t(`cms.status.confirm.${action}`, { name })
                }
                description={
                    action === null
                        ? ''
                        : t(`cms.status.consequence.${entity}.${action}`)
                }
                confirmLabel={
                    action === null ? '' : t(`cms.status.actions.${action}`)
                }
                destructive={action === 'ARCHIVED'}
                processing={processing}
                onConfirm={confirm}
                onCancel={() => setPending(null)}
            />
        </>
    );
}
