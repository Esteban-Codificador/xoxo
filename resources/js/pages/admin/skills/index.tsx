import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { Pencil, Plus, Sparkles } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { ContentStatusBadge } from '@/components/publishing/status-badges';
import { Button } from '@/components/ui/button';
import { t } from '@/i18n';
import { dashboard } from '@/routes/admin';
import { create, edit, index } from '@/routes/admin/skills';
import type { ContentStatus, Difficulty } from '@/types/enums';

type SkillRow = {
    id: number;
    name: string;
    slug: string;
    difficulty: Difficulty;
    status: ContentStatus;
    lessons_count: number;
    prerequisites: string[];
    can_edit: boolean;
};

type Props = { skills: SkillRow[]; can: { create: boolean } };

export default function AdminSkillsIndex({ skills, can }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.adminOverview'), href: dashboard() },
            { title: t('nav.skills'), href: index() },
        ],
    });

    return (
        <>
            <Head title={t('cms.skills.head')} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('cms.skills.title')}
                    description={t('cms.skills.description')}
                    actions={
                        can.create && (
                            <Button asChild size="sm">
                                <Link href={create()}>
                                    <Plus aria-hidden="true" />
                                    {t('cms.skills.create')}
                                </Link>
                            </Button>
                        )
                    }
                />

                {skills.length === 0 ? (
                    <EmptyState icon={Sparkles} title={t('cms.skills.empty')} />
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full min-w-[40rem] table-fixed text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th
                                        scope="col"
                                        className="px-4 py-2 font-medium"
                                    >
                                        {t('cms.skills.skill')}
                                    </th>
                                    <th
                                        scope="col"
                                        className="w-32 px-4 py-2 font-medium"
                                    >
                                        {t('cms.lessons.status')}
                                    </th>
                                    <th
                                        scope="col"
                                        className="w-36 px-4 py-2 font-medium"
                                    >
                                        {t('cms.edit.fields.difficulty')}
                                    </th>
                                    <th
                                        scope="col"
                                        className="w-24 px-4 py-2 text-right font-medium"
                                    >
                                        {t('cms.skills.lessons')}
                                    </th>
                                    <th scope="col" className="w-28 px-4 py-2">
                                        <span className="sr-only">
                                            {t('cms.lessons.edit')}
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {skills.map((skill) => (
                                    <tr
                                        key={skill.id}
                                        className="border-t align-top"
                                    >
                                        <th
                                            scope="row"
                                            className="px-4 py-3 text-left font-normal"
                                        >
                                            <span className="block font-medium">
                                                {skill.name}
                                            </span>
                                            <span className="text-xs text-muted-foreground">
                                                {skill.prerequisites.length ===
                                                0
                                                    ? t(
                                                          'cms.tracks.startingPoint',
                                                      )
                                                    : `${t('cms.tracks.requires')}: ${skill.prerequisites.join(', ')}`}
                                            </span>
                                        </th>
                                        <td className="px-4 py-3">
                                            <ContentStatusBadge
                                                status={skill.status}
                                            />
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {t(
                                                `difficulty.${skill.difficulty}`,
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right text-muted-foreground tabular-nums">
                                            {skill.lessons_count}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {skill.can_edit ? (
                                                <Button
                                                    asChild
                                                    size="sm"
                                                    variant="outline"
                                                >
                                                    <Link
                                                        href={edit(skill.id)}
                                                        aria-label={t(
                                                            'cms.skills.edit',
                                                            {
                                                                skill: skill.name,
                                                            },
                                                        )}
                                                    >
                                                        <Pencil aria-hidden="true" />
                                                        {t('cms.lessons.edit')}
                                                    </Link>
                                                </Button>
                                            ) : (
                                                <span className="text-xs text-muted-foreground">
                                                    {t('cms.lessons.readOnly')}
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}
