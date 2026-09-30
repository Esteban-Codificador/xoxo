import { Link } from '@inertiajs/react';
import { t } from '@/i18n';
import { cn } from '@/lib/utils';
import { edit } from '@/routes/admin/lessons';
import { edit as editQuiz } from '@/routes/admin/lessons/quiz';
import { edit as editRelations } from '@/routes/admin/lessons/relations';

type Section = 'content' | 'relations' | 'quiz';

/**
 * Content (versioned, published from the panel), relations and the quiz
 * (both live) are saved separately, so they live on their own pages.
 */
export function LessonTabs({
    slug,
    current,
}: {
    slug: string;
    current: Section;
}) {
    const sections: { key: Section; href: ReturnType<typeof edit> }[] = [
        { key: 'content', href: edit(slug) },
        { key: 'relations', href: editRelations(slug) },
        { key: 'quiz', href: editQuiz(slug) },
    ];

    return (
        <nav aria-label={t('cms.lessonTabs.label')} className="border-b">
            <ul className="-mb-px flex gap-1">
                {sections.map(({ key, href }) => (
                    <li key={key}>
                        <Link
                            href={href}
                            aria-current={current === key ? 'page' : undefined}
                            className={cn(
                                'inline-block border-b-2 px-3 py-2 text-sm font-medium transition-colors',
                                current === key
                                    ? 'border-foreground text-foreground'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {t(`cms.lessonTabs.${key}`)}
                        </Link>
                    </li>
                ))}
            </ul>
        </nav>
    );
}
