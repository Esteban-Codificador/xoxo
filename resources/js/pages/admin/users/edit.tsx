import { Head, Link, setLayoutProps, useForm } from '@inertiajs/react';
import { Lock, ScrollText } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { useId } from 'react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { t } from '@/i18n';
import { formatDate, formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes/admin';
import { index as auditIndex } from '@/routes/admin/audit';
import { edit, index, role as roleRoute } from '@/routes/admin/users';
import type { Role } from '@/types/enums';

type Props = {
    user: {
        id: number;
        name: string;
        email: string;
        username: string | null;
        role: Role | null;
        verified: boolean;
        two_factor: boolean;
        last_active_at: string | null;
        created_at: string | null;
        lessons_authored: number;
    };
    roles: Role[];
    can: { change_role: boolean; view_audit: boolean };
    /** Why the role cannot be changed here (their own account). */
    role_locked: string | null;
};

export default function AdminUserEdit({
    user,
    roles,
    can,
    role_locked,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.adminOverview'), href: dashboard() },
            { title: t('nav.users'), href: index() },
            { title: user.name, href: edit(user.id) },
        ],
    });

    const id = useId();
    const form = useForm({ role: user.role ?? ('STUDENT' as Role) });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(roleRoute.url(user.id), {
            preserveScroll: true,
            // The saved role is the new starting point.
            onSuccess: () => form.setDefaults(),
        });
    };

    const yesNo = (value: boolean) =>
        value ? t('admin.user.yes') : t('admin.user.no');

    return (
        <>
            <Head title={user.name} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={user.name}
                    description={user.email}
                    actions={
                        can.view_audit && (
                            <Button asChild size="sm" variant="outline">
                                <Link
                                    href={auditIndex({
                                        query: { user: user.id },
                                    })}
                                >
                                    <ScrollText aria-hidden="true" />
                                    {t('admin.user.activity')}
                                </Link>
                            </Button>
                        )
                    }
                />

                <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)]">
                    <section
                        aria-labelledby={`${id}-details`}
                        className="space-y-3"
                    >
                        <h2 id={`${id}-details`} className="font-medium">
                            {t('admin.user.details')}
                        </h2>
                        <dl className="divide-y rounded-xl border text-sm">
                            <Detail label={t('admin.user.username')}>
                                {user.username ?? '—'}
                            </Detail>
                            <Detail label={t('admin.user.verified')}>
                                {yesNo(user.verified)}
                            </Detail>
                            <Detail label={t('admin.user.twoFactor')}>
                                {yesNo(user.two_factor)}
                            </Detail>
                            <Detail label={t('admin.user.created')}>
                                {user.created_at === null
                                    ? '—'
                                    : formatDate(user.created_at)}
                            </Detail>
                            <Detail label={t('admin.user.lastActive')}>
                                {user.last_active_at === null
                                    ? t('admin.users.never')
                                    : formatDateTime(user.last_active_at)}
                            </Detail>
                            <Detail label={t('admin.user.lessonsAuthored')}>
                                <span className="tabular-nums">
                                    {user.lessons_authored}
                                </span>
                            </Detail>
                        </dl>
                    </section>

                    <section
                        aria-labelledby={`${id}-role`}
                        className="space-y-3"
                    >
                        <div className="space-y-1">
                            <h2 id={`${id}-role`} className="font-medium">
                                {t('admin.user.roleTitle')}
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                {t('admin.user.roleDescription')}
                            </p>
                        </div>

                        {role_locked !== null && (
                            <p className="flex items-start gap-2 rounded-lg border bg-muted/40 px-3 py-2 text-sm">
                                <Lock
                                    className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                {role_locked}
                            </p>
                        )}

                        <form onSubmit={submit} className="space-y-4">
                            <fieldset
                                disabled={!can.change_role || form.processing}
                                className="space-y-2 [&_:disabled]:cursor-not-allowed"
                            >
                                <legend className="sr-only">
                                    {t('admin.user.roleTitle')}
                                </legend>
                                {roles.map((role) => (
                                    <label
                                        key={role}
                                        className="flex cursor-pointer items-start gap-3 rounded-lg border p-3 has-checked:border-primary has-checked:bg-primary/5 has-disabled:cursor-not-allowed has-disabled:opacity-60"
                                    >
                                        <input
                                            type="radio"
                                            name="role"
                                            value={role}
                                            checked={form.data.role === role}
                                            onChange={() =>
                                                form.setData('role', role)
                                            }
                                            aria-describedby={`${id}-${role}-help`}
                                            className="mt-1 size-4 accent-primary"
                                        />
                                        <span className="space-y-0.5">
                                            <span className="block text-sm font-medium">
                                                {t(`admin.roles.${role}`)}
                                            </span>
                                            <span
                                                id={`${id}-${role}-help`}
                                                className="block text-xs text-muted-foreground"
                                            >
                                                {t(`admin.roleHelp.${role}`)}
                                            </span>
                                        </span>
                                    </label>
                                ))}
                            </fieldset>
                            <InputError message={form.errors.role} />
                            {can.change_role && (
                                <Button
                                    type="submit"
                                    disabled={!form.isDirty || form.processing}
                                >
                                    {form.processing
                                        ? t('admin.user.saving')
                                        : t('admin.user.saveRole')}
                                </Button>
                            )}
                        </form>
                    </section>
                </div>
            </div>
        </>
    );
}

function Detail({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="flex items-baseline justify-between gap-4 px-4 py-2.5">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="text-right">{children}</dd>
        </div>
    );
}
