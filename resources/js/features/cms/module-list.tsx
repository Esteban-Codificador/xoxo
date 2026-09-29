import { router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Pencil } from 'lucide-react';
import type { FormEvent } from 'react';
import { useId, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { StatusActions } from '@/components/publishing/status-actions';
import { ContentStatusBadge } from '@/components/publishing/status-badges';
import { t } from '@/i18n';
import { status, update } from '@/routes/admin/modules';
import { moduleOrder } from '@/routes/admin/tracks';
import type { ContentStatus } from '@/types/enums';
import { Field } from './field';

export type ModuleRow = {
    id: number;
    slug: string;
    title: string;
    summary: string;
    status: ContentStatus;
    lessons_count: number;
    status_actions: ContentStatus[];
};

function ModuleDialog({
    module,
    onClose,
}: {
    module: ModuleRow;
    onClose: () => void;
}) {
    const id = useId();
    const form = useForm({
        title: module.title,
        slug: module.slug,
        summary: module.summary,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(update.url(module.id), {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>
                            {t('cms.modules.dialogTitle')}
                        </DialogTitle>
                        <DialogDescription>
                            {t('cms.modules.dialogDescription')}
                        </DialogDescription>
                    </DialogHeader>
                    <Field
                        id={`${id}-title`}
                        label={t('cms.edit.fields.title')}
                        error={form.errors.title}
                    >
                        <Input
                            id={`${id}-title`}
                            name="title"
                            value={form.data.title}
                            maxLength={200}
                            required
                            onChange={(event) =>
                                form.setData('title', event.target.value)
                            }
                        />
                    </Field>
                    <Field
                        id={`${id}-slug`}
                        label={t('cms.fields.slug')}
                        help={t('cms.modules.slugHelp')}
                        error={form.errors.slug}
                    >
                        <Input
                            id={`${id}-slug`}
                            name="slug"
                            value={form.data.slug}
                            maxLength={120}
                            spellCheck={false}
                            className="font-mono"
                            aria-describedby={`${id}-slug-help`}
                            onChange={(event) =>
                                form.setData('slug', event.target.value)
                            }
                        />
                    </Field>
                    <Field
                        id={`${id}-summary`}
                        label={t('cms.edit.fields.summary')}
                        error={form.errors.summary}
                    >
                        <Textarea
                            id={`${id}-summary`}
                            name="summary"
                            value={form.data.summary}
                            maxLength={2000}
                            rows={3}
                            onChange={(event) =>
                                form.setData('summary', event.target.value)
                            }
                        />
                    </Field>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing
                                ? t('cms.edit.saving')
                                : t('common.save')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/**
 * Modules of a track in study order: move them up or down and save the
 * order, edit their details in a dialog and change their status.
 */
export function ModuleList({
    trackId,
    modules,
}: {
    trackId: number;
    modules: ModuleRow[];
}) {
    const ids = modules.map((module) => module.id);
    const [order, setOrder] = useState(ids);
    const [editing, setEditing] = useState<ModuleRow | null>(null);
    const [saving, setSaving] = useState(false);

    // A module added or removed elsewhere resets the local order.
    const current =
        order.length === ids.length && order.every((id) => ids.includes(id))
            ? order
            : ids;
    const changed = current.join() !== ids.join();
    const byId = new Map(modules.map((module) => [module.id, module]));

    const move = (index: number, delta: -1 | 1) => {
        const next = [...current];
        [next[index], next[index + delta]] = [next[index + delta], next[index]];
        setOrder(next);
    };

    const saveOrder = () =>
        router.put(
            moduleOrder.url(trackId),
            { modules: current },
            {
                preserveScroll: true,
                onStart: () => setSaving(true),
                onFinish: () => setSaving(false),
            },
        );

    if (modules.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                {t('cms.trackEdit.noModules')}
            </p>
        );
    }

    return (
        <div className="space-y-3">
            <ol className="divide-y rounded-lg border">
                {current.map((id, index) => {
                    const module = byId.get(id);

                    if (module === undefined) {
                        return null;
                    }

                    return (
                        <li
                            key={id}
                            className="flex flex-col gap-3 p-3 sm:flex-row sm:items-start"
                        >
                            <div className="flex shrink-0 gap-1 sm:flex-col">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="size-8"
                                    disabled={index === 0}
                                    aria-label={t('cms.modules.moveUp', {
                                        module: module.title,
                                    })}
                                    onClick={() => move(index, -1)}
                                >
                                    <ArrowUp aria-hidden="true" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="size-8"
                                    disabled={index === current.length - 1}
                                    aria-label={t('cms.modules.moveDown', {
                                        module: module.title,
                                    })}
                                    onClick={() => move(index, 1)}
                                >
                                    <ArrowDown aria-hidden="true" />
                                </Button>
                            </div>
                            <div className="min-w-0 flex-1 space-y-1">
                                <p className="flex flex-wrap items-center gap-2 font-medium">
                                    <span className="text-muted-foreground tabular-nums">
                                        {index + 1}.
                                    </span>
                                    {module.title}
                                    <ContentStatusBadge
                                        status={module.status}
                                    />
                                </p>
                                <p className="font-mono text-xs text-muted-foreground">
                                    #{module.slug} ·{' '}
                                    {module.lessons_count === 1
                                        ? t('cms.modules.oneLesson')
                                        : t('cms.modules.lessons', {
                                              count: module.lessons_count,
                                          })}
                                </p>
                                <p className="line-clamp-2 text-sm text-muted-foreground">
                                    {module.summary}
                                </p>
                            </div>
                            <div className="flex shrink-0 flex-wrap items-start gap-2">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    aria-label={t('cms.modules.edit', {
                                        module: module.title,
                                    })}
                                    onClick={() => setEditing(module)}
                                >
                                    <Pencil aria-hidden="true" />
                                    {t('editor.edit')}
                                </Button>
                                <StatusActions
                                    entity="module"
                                    name={module.title}
                                    current={module.status}
                                    actions={module.status_actions}
                                    url={status.url(module.id)}
                                />
                            </div>
                        </li>
                    );
                })}
            </ol>

            {changed && (
                <div className="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-dashed p-3">
                    <p className="text-sm text-muted-foreground">
                        {t('cms.modules.orderChanged')}
                    </p>
                    <Button
                        type="button"
                        size="sm"
                        disabled={saving}
                        onClick={saveOrder}
                    >
                        {t('cms.modules.saveOrder')}
                    </Button>
                </div>
            )}

            {editing !== null && (
                <ModuleDialog
                    key={editing.id}
                    module={editing}
                    onClose={() => setEditing(null)}
                />
            )}
        </div>
    );
}
