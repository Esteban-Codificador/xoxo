import { Head, Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import type { TranslationKey } from '@/i18n';
import { t } from '@/i18n';
import { home } from '@/routes';

type Status = 403 | 404 | 419 | 500 | 503;

const copy: Record<
    Status,
    { title: TranslationKey; description: TranslationKey }
> = {
    403: {
        title: 'errors.forbidden.title',
        description: 'errors.forbidden.description',
    },
    404: {
        title: 'errors.notFound.title',
        description: 'errors.notFound.description',
    },
    419: {
        title: 'errors.expired.title',
        description: 'errors.expired.description',
    },
    500: {
        title: 'errors.serverError.title',
        description: 'errors.serverError.description',
    },
    503: {
        title: 'errors.unavailable.title',
        description: 'errors.unavailable.description',
    },
};

/**
 * Rendered by the exception handler, sometimes before any middleware ran:
 * it must not rely on shared props (auth, locale) — only on `status`.
 */
export default function ErrorPage({ status }: { status: Status }) {
    const { title, description } = copy[status] ?? copy[500];

    return (
        <>
            <Head title={t('errors.head', { status })} />

            <main className="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 text-center">
                <AppLogoIcon className="size-10 fill-current text-muted-foreground" />
                <div className="space-y-2">
                    <p className="text-sm font-medium text-muted-foreground tabular-nums">
                        {status}
                    </p>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {t(title)}
                    </h1>
                    <p className="max-w-md text-muted-foreground">
                        {t(description)}
                    </p>
                </div>
                {status === 419 ? (
                    <Button onClick={() => window.location.reload()}>
                        {t('common.retry')}
                    </Button>
                ) : (
                    <Button asChild>
                        <Link href={home()}>{t('errors.goHome')}</Link>
                    </Button>
                )}
            </main>
        </>
    );
}
