import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { t } from '@/i18n';

/**
 * Asks before leaving a form with unsaved changes: closing the tab, and
 * Inertia navigations (not prefetches on hover, not the form's own save).
 */
export function useUnsavedChangesGuard(dirty: boolean): void {
    useEffect(() => {
        if (!dirty) {
            return;
        }

        const onBeforeUnload = (event: BeforeUnloadEvent) => {
            event.preventDefault();
        };

        const removeBeforeListener = router.on('before', (event) => {
            const { visit } = event.detail;

            if (
                visit.method === 'get' &&
                !visit.prefetch &&
                !window.confirm(t('cms.edit.leaveConfirm'))
            ) {
                event.preventDefault();
            }
        });

        window.addEventListener('beforeunload', onBeforeUnload);

        return () => {
            window.removeEventListener('beforeunload', onBeforeUnload);
            removeBeforeListener();
        };
    }, [dirty]);
}
