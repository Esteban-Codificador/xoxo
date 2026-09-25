import { Head, setLayoutProps } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import { edit as editAppearance } from '@/routes/appearance';
import { t } from '@/i18n';

export default function Appearance() {
    setLayoutProps({
        breadcrumbs: [
            { title: t('settings.appearance.head'), href: editAppearance() },
        ],
    });

    return (
        <>
            <Head title={t('settings.appearance.head')} />

            <h1 className="sr-only">{t('settings.appearance.head')}</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('settings.appearance.title')}
                    description={t('settings.appearance.description')}
                />
                <AppearanceTabs />
            </div>
        </>
    );
}
