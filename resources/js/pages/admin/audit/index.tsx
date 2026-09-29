import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { ScrollText } from 'lucide-react';
import type { ReactNode } from 'react';
import { useId } from 'react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import type { PaginationData } from '@/components/pagination';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { AuditEntry } from '@/features/admin/activity';
import { activitySentence, entityLabel } from '@/features/admin/activity';
import { AuditChanges } from '@/features/admin/audit-changes';
import { t } from '@/i18n';
import { formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/audit';
import type { AuditAction } from '@/types/enums';

type Props = {
    entries: AuditEntry[];
    pagination: PaginationData;
    filters: {
        action: AuditAction | null;
        entity: string | null;
        user: number | null;
        from: string | null;
        to: string | null;
    };
    options: {
        actions: AuditAction[];
        /** Entity types present in the log. */
        entities: string[];
        users: { id: number; name: string }[];
    };
};

const selectClass =
    'h-9 rounded-md border border-input bg-transparent px-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

export default function AdminAuditIndex({
    entries,
    pagination,
    filters,
    options,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.adminOverview'), href: dashboard() },
            { title: t('nav.audit'), href: index() },
        ],
    });

    const id = useId();
    const filtered = Object.values(filters).some((value) => value !== null);

    return (
        <>
            <Head title={t('admin.audit.head')} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('admin.audit.title')}
                    description={t('admin.audit.description')}
                />

                {/* A GET form: the filters live in the URL and survive a reload. */}
                <Form
                    {...index.form()}
                    options={{ preserveScroll: true }}
                    className="flex flex-wrap items-end gap-3"
                >
                    <FilterField
                        id={`${id}-action`}
                        label={t('admin.audit.action')}
                    >
                        <select
                            id={`${id}-action`}
                            name="action"
                            defaultValue={filters.action ?? ''}
                            className={selectClass}
                        >
                            <option value="">
                                {t('admin.audit.allActions')}
                            </option>
                            {options.actions.map((action) => (
                                <option key={action} value={action}>
                                    {t(`admin.actions.${action}`)}
                                </option>
                            ))}
                        </select>
                    </FilterField>
                    <FilterField
                        id={`${id}-entity`}
                        label={t('admin.audit.entity')}
                    >
                        <select
                            id={`${id}-entity`}
                            name="entity"
                            defaultValue={filters.entity ?? ''}
                            className={selectClass}
                        >
                            <option value="">
                                {t('admin.audit.allEntities')}
                            </option>
                            {options.entities.map((entity) => (
                                <option key={entity} value={entity}>
                                    {entityLabel(entity)}
                                </option>
                            ))}
                        </select>
                    </FilterField>
                    <FilterField
                        id={`${id}-user`}
                        label={t('admin.audit.user')}
                    >
                        <select
                            id={`${id}-user`}
                            name="user"
                            defaultValue={filters.user ?? ''}
                            className={selectClass}
                        >
                            <option value="">
                                {t('admin.audit.allUsers')}
                            </option>
                            {options.users.map((user) => (
                                <option key={user.id} value={user.id}>
                                    {user.name}
                                </option>
                            ))}
                        </select>
                    </FilterField>
                    <FilterField
                        id={`${id}-from`}
                        label={t('admin.audit.from')}
                    >
                        <Input
                            id={`${id}-from`}
                            name="from"
                            type="date"
                            defaultValue={filters.from ?? ''}
                            className="w-40"
                        />
                    </FilterField>
                    <FilterField id={`${id}-to`} label={t('admin.audit.to')}>
                        <Input
                            id={`${id}-to`}
                            name="to"
                            type="date"
                            defaultValue={filters.to ?? ''}
                            className="w-40"
                        />
                    </FilterField>
                    <Button type="submit" variant="outline">
                        {t('admin.audit.filter')}
                    </Button>
                    {filtered && (
                        <Button asChild variant="ghost">
                            <Link href={index()}>{t('admin.audit.clear')}</Link>
                        </Button>
                    )}
                </Form>

                {entries.length === 0 ? (
                    <EmptyState
                        icon={ScrollText}
                        title={t('admin.audit.empty')}
                    />
                ) : (
                    <div className="space-y-3">
                        <p className="text-sm text-muted-foreground">
                            {t('admin.audit.count', {
                                count: pagination.total,
                            })}
                        </p>
                        <ol className="divide-y rounded-xl border">
                            {entries.map((entry) => (
                                <li
                                    key={entry.id}
                                    className="space-y-2 px-4 py-3"
                                >
                                    <div className="flex flex-col gap-1 text-sm sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                                        <p>{activitySentence(entry)}</p>
                                        <div className="flex shrink-0 items-center gap-3 text-xs">
                                            {entry.href !== null && (
                                                <Link
                                                    href={entry.href}
                                                    className="font-medium text-primary underline-offset-4 hover:underline"
                                                    aria-label={t(
                                                        'admin.audit.openLabel',
                                                        {
                                                            label:
                                                                entry.label ??
                                                                entityLabel(
                                                                    entry.entity,
                                                                ),
                                                        },
                                                    )}
                                                >
                                                    {t('admin.audit.open')}
                                                </Link>
                                            )}
                                            <time
                                                dateTime={entry.created_at}
                                                className="text-muted-foreground"
                                            >
                                                {formatDateTime(
                                                    entry.created_at,
                                                )}
                                            </time>
                                        </div>
                                    </div>
                                    {entry.changes.length > 0 && (
                                        <details>
                                            <summary className="w-fit cursor-pointer text-xs text-muted-foreground hover:text-foreground">
                                                {t('admin.audit.changes', {
                                                    count: entry.changes.length,
                                                })}
                                            </summary>
                                            <div className="mt-2 rounded-lg bg-muted/40 px-3 py-2">
                                                <AuditChanges
                                                    changes={entry.changes}
                                                />
                                            </div>
                                        </details>
                                    )}
                                </li>
                            ))}
                        </ol>
                        <Pagination pagination={pagination} />
                    </div>
                )}
            </div>
        </>
    );
}

function FilterField({
    id,
    label,
    children,
}: {
    id: string;
    label: string;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-1">
            <label htmlFor={id} className="text-xs text-muted-foreground">
                {label}
            </label>
            {children}
        </div>
    );
}
