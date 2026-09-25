import type { Heading } from '@/features/rich-content';
import { t } from '@/i18n';
import { cn } from '@/lib/utils';
import { useActiveHeading } from './use-active-heading';

/** Sections of the lesson body (H2 and H3), with the one being read marked. */
export function TableOfContents({
    headings,
    className,
}: {
    headings: Heading[];
    className?: string;
}) {
    const entries = headings.filter((heading) => heading.level <= 3);
    const active = useActiveHeading(entries.map((heading) => heading.id));

    if (entries.length < 2) {
        return null;
    }

    return (
        <nav
            aria-label={t('richContent.tableOfContents')}
            className={className}
        >
            <p className="mb-3 text-sm font-medium">
                {t('richContent.tableOfContents')}
            </p>
            <ol className="space-y-1 border-l text-sm">
                {entries.map((heading) => (
                    <li key={heading.id}>
                        <a
                            href={`#${heading.id}`}
                            aria-current={
                                active === heading.id ? 'location' : undefined
                            }
                            className={cn(
                                '-ml-px block border-l-2 border-transparent py-1 text-muted-foreground transition-colors hover:text-foreground',
                                heading.level === 3 ? 'pl-6' : 'pl-3',
                                active === heading.id &&
                                    'border-foreground font-medium text-foreground',
                            )}
                        >
                            {heading.text}
                        </a>
                    </li>
                ))}
            </ol>
        </nav>
    );
}
