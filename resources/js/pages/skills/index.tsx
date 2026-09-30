import { Head, setLayoutProps } from '@inertiajs/react';
import { Sparkles } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { SkillCard } from '@/features/skills/skill-card';
import type { SkillSummary } from '@/features/skills/progress';
import { t } from '@/i18n';
import { dashboard } from '@/routes';
import { index } from '@/routes/skills';

type Props = {
    skills: SkillSummary[];
    summary: { completed: number; total: number };
};

export default function SkillsIndex({ skills, summary }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.dashboard'), href: dashboard() },
            { title: t('skills.title'), href: index() },
        ],
    });

    return (
        <>
            <Head title={t('skills.head')} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('skills.title')}
                    description={t('skills.description')}
                />

                {skills.length === 0 ? (
                    <EmptyState icon={Sparkles} title={t('skills.empty')} />
                ) : (
                    <div className="space-y-4">
                        <p className="text-sm font-medium">
                            {t('skills.summary', summary)}
                        </p>
                        <ol className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            {skills.map((skill) => (
                                <SkillCard key={skill.slug} skill={skill} />
                            ))}
                        </ol>
                    </div>
                )}
            </div>
        </>
    );
}
