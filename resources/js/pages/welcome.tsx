import { Head, Link, usePage } from '@inertiajs/react';
import { Award, FolderGit2, Network } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { t } from '@/i18n';
import { dashboard, login, register } from '@/routes';

const pillars = [
    {
        icon: Network,
        title: 'welcome.pillars.roadmapTitle',
        text: 'welcome.pillars.roadmapText',
    },
    {
        icon: Award,
        title: 'welcome.pillars.evidenceTitle',
        text: 'welcome.pillars.evidenceText',
    },
    {
        icon: FolderGit2,
        title: 'welcome.pillars.portfolioTitle',
        text: 'welcome.pillars.portfolioText',
    },
] as const;

export default function Welcome() {
    const { auth, name } = usePage().props;

    return (
        <>
            <Head title={t('welcome.head')} />

            <div className="flex min-h-svh flex-col bg-background">
                <header className="mx-auto flex w-full max-w-5xl items-center justify-between px-4 py-6 sm:px-6">
                    <span className="flex items-center gap-2 font-semibold">
                        <AppLogoIcon className="size-7 fill-current" />
                        {name}
                    </span>

                    <nav className="flex items-center gap-2">
                        {auth.user ? (
                            <Button asChild>
                                <Link href={dashboard()}>
                                    {t('welcome.goToDashboard')}
                                </Link>
                            </Button>
                        ) : (
                            <>
                                <Button variant="ghost" asChild>
                                    <Link href={login()}>
                                        {t('welcome.logIn')}
                                    </Link>
                                </Button>
                                <Button asChild>
                                    <Link href={register()}>
                                        {t('welcome.register')}
                                    </Link>
                                </Button>
                            </>
                        )}
                    </nav>
                </header>

                <main className="mx-auto flex w-full max-w-5xl flex-1 flex-col justify-center gap-14 px-4 py-12 sm:px-6">
                    <section className="max-w-2xl space-y-5">
                        <h1 className="text-4xl font-semibold tracking-tight text-balance sm:text-5xl">
                            {t('welcome.title')}
                        </h1>
                        <p className="text-lg text-pretty text-muted-foreground">
                            {t('welcome.subtitle')}
                        </p>
                        {!auth.user && (
                            <div className="flex flex-wrap gap-3 pt-2">
                                <Button size="lg" asChild>
                                    <Link href={register()}>
                                        {t('welcome.register')}
                                    </Link>
                                </Button>
                                <Button size="lg" variant="outline" asChild>
                                    <Link href={login()}>
                                        {t('welcome.logIn')}
                                    </Link>
                                </Button>
                            </div>
                        )}
                    </section>

                    <section className="grid gap-6 sm:grid-cols-3">
                        {pillars.map(({ icon: Icon, title, text }) => (
                            <div key={title} className="space-y-2">
                                <Icon
                                    className="size-5 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <h2 className="font-medium">{t(title)}</h2>
                                <p className="text-sm text-muted-foreground">
                                    {t(text)}
                                </p>
                            </div>
                        ))}
                    </section>
                </main>
            </div>
        </>
    );
}
