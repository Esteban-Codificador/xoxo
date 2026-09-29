import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { t } from '@/i18n';

/** A page of a server-side list, as the admin controllers send it. */
export type PaginationData = {
    page: number;
    pages: number;
    total: number;
    /** URLs with the current filters; null at either end. */
    previous: string | null;
    next: string | null;
};

export function Pagination({ pagination }: { pagination: PaginationData }) {
    if (pagination.pages <= 1) {
        return null;
    }

    return (
        <nav
            aria-label={t('common.pagination.label')}
            className="flex items-center justify-between gap-3 text-sm"
        >
            <p className="text-muted-foreground">
                {t('common.pagination.status', {
                    page: pagination.page,
                    pages: pagination.pages,
                })}
            </p>
            <div className="flex gap-2">
                <PageLink href={pagination.previous} rel="prev">
                    <ChevronLeft aria-hidden="true" />
                    {t('common.pagination.previous')}
                </PageLink>
                <PageLink href={pagination.next} rel="next">
                    {t('common.pagination.next')}
                    <ChevronRight aria-hidden="true" />
                </PageLink>
            </div>
        </nav>
    );
}

function PageLink({
    href,
    rel,
    children,
}: {
    href: string | null;
    rel: 'prev' | 'next';
    children: ReactNode;
}) {
    if (href === null) {
        return (
            <Button variant="outline" size="sm" disabled>
                {children}
            </Button>
        );
    }

    return (
        <Button asChild variant="outline" size="sm">
            <Link href={href} rel={rel}>
                {children}
            </Link>
        </Button>
    );
}
