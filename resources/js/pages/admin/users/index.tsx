import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { Users } from 'lucide-react';
import { useId } from 'react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import type { PaginationData } from '@/components/pagination';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { t } from '@/i18n';
import { formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import { edit, index } from '@/routes/admin/users';
import { Role } from '@/types/enums';

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: Role | null;
    verified: boolean;
    last_active_at: string | null;
    created_at: string | null;
};

type Props = {
    users: UserRow[];
    pagination: PaginationData;
    filters: { q: string; role: Role | null };
};

const selectClass =
    'h-9 rounded-md border border-input bg-transparent px-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

export default function AdminUsersIndex({ users, pagination, filters }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.adminOverview'), href: dashboard() },
            { title: t('nav.users'), href: index() },
        ],
    });

    const id = useId();

    return (
        <>
            <Head title={t('admin.users.head')} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('admin.users.title')}
                    description={t('admin.users.description')}
                />

                {/* A GET form: the filters live in the URL and survive a reload. */}
                <Form
                    {...index.form()}
                    options={{ preserveScroll: true }}
                    className="flex flex-wrap items-end gap-3"
                >
                    <div className="grid gap-1">
                        <label
                            htmlFor={`${id}-q`}
                            className="text-xs text-muted-foreground"
                        >
                            {t('admin.users.searchLabel')}
                        </label>
                        <Input
                            id={`${id}-q`}
                            name="q"
                            type="search"
                            defaultValue={filters.q}
                            placeholder={t('admin.users.searchPlaceholder')}
                            className="w-64"
                        />
                    </div>
                    <div className="grid gap-1">
                        <label
                            htmlFor={`${id}-role`}
                            className="text-xs text-muted-foreground"
                        >
                            {t('admin.users.roleFilter')}
                        </label>
                        <select
                            id={`${id}-role`}
                            name="role"
                            defaultValue={filters.role ?? ''}
                            className={selectClass}
                        >
                            <option value="">
                                {t('admin.users.allRoles')}
                            </option>
                            {Object.values(Role).map((role) => (
                                <option key={role} value={role}>
                                    {t(`admin.roles.${role}`)}
                                </option>
                            ))}
                        </select>
                    </div>
                    <Button type="submit" variant="outline">
                        {t('admin.users.filter')}
                    </Button>
                </Form>

                {users.length === 0 ? (
                    <EmptyState icon={Users} title={t('admin.users.empty')} />
                ) : (
                    <div className="space-y-3">
                        <p className="text-sm text-muted-foreground">
                            {t('admin.users.count', {
                                count: pagination.total,
                            })}
                        </p>
                        <div className="overflow-x-auto rounded-xl border">
                            <table className="w-full min-w-[40rem] table-fixed text-sm">
                                <thead className="bg-muted/50 text-left">
                                    <tr>
                                        <th
                                            scope="col"
                                            className="px-4 py-2 font-medium"
                                        >
                                            {t('admin.users.person')}
                                        </th>
                                        <th
                                            scope="col"
                                            className="w-32 px-4 py-2 font-medium"
                                        >
                                            {t('admin.users.role')}
                                        </th>
                                        <th
                                            scope="col"
                                            className="w-48 px-4 py-2 font-medium"
                                        >
                                            {t('admin.users.lastActive')}
                                        </th>
                                        <th
                                            scope="col"
                                            className="w-24 px-4 py-2"
                                        >
                                            <span className="sr-only">
                                                {t('admin.users.open')}
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {users.map((user) => (
                                        <tr
                                            key={user.id}
                                            className="border-t align-top"
                                        >
                                            <th
                                                scope="row"
                                                className="px-4 py-3 text-left font-normal"
                                            >
                                                <span className="block font-medium">
                                                    {user.name}
                                                </span>
                                                <span className="block truncate text-xs text-muted-foreground">
                                                    {user.email}
                                                </span>
                                                {!user.verified && (
                                                    <span className="block text-xs text-muted-foreground">
                                                        {t(
                                                            'admin.users.unverified',
                                                        )}
                                                    </span>
                                                )}
                                            </th>
                                            <td className="px-4 py-3">
                                                {user.role === null ? (
                                                    <span className="text-xs text-muted-foreground">
                                                        {t(
                                                            'admin.users.noRole',
                                                        )}
                                                    </span>
                                                ) : (
                                                    <Badge variant="secondary">
                                                        {t(
                                                            `admin.roles.${user.role}`,
                                                        )}
                                                    </Badge>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {user.last_active_at === null
                                                    ? t('admin.users.never')
                                                    : formatDateTime(
                                                          user.last_active_at,
                                                      )}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <Button
                                                    asChild
                                                    size="sm"
                                                    variant="outline"
                                                >
                                                    <Link
                                                        href={edit(user.id)}
                                                        aria-label={t(
                                                            'admin.users.openLabel',
                                                            { user: user.name },
                                                        )}
                                                    >
                                                        {t('admin.users.open')}
                                                    </Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <Pagination pagination={pagination} />
                    </div>
                )}
            </div>
        </>
    );
}
