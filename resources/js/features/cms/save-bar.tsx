import { Save } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { t } from '@/i18n';

/** Sticky footer of a CMS form: what is unsaved and the save button. */
export function SaveBar({
    dirty,
    processing,
}: {
    dirty: boolean;
    processing: boolean;
}) {
    return (
        <div className="sticky bottom-0 z-20 -mx-4 flex flex-wrap items-center justify-between gap-3 border-t bg-background px-4 py-3 md:-mx-6 md:px-6">
            <p role="status" className="text-sm text-muted-foreground">
                {dirty ? t('cms.edit.unsaved') : t('cms.edit.saved')}
            </p>
            <Button type="submit" disabled={processing || !dirty}>
                <Save aria-hidden="true" />
                {processing ? t('cms.edit.saving') : t('cms.edit.save')}
            </Button>
        </div>
    );
}
