import { Link, router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, FilePlus, Pencil, Plus } from 'lucide-react';
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
import { create as createLesson } from '@/routes/admin/lessons';
import { status, update } from '@/routes/admin/modules';
import { moduleOrder } from '@/routes/admin/tracks';
import { store } from '@/routes/admin/tracks/modules';
import { slugify } from '@/lib/slug';
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

/** Edits a module, or creates one at the end of the track (module null). */
function ModuleDialog({
    trackId,
    module,
    onClose,
}: {
    trackId: number;
    module: ModuleRow | null;
    onClose: () => void;
}) {
    const id = useId();
    // A new module's slug follows its title until the author edits it.
    const [slugEdited, setSlugEdited] = useState(module !== null);
    const form = useForm({
        title: module?.title ?? '',
        slug: module?.slug ?? '',
        summary: module?.summary ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };

        if (module === null) {
            form.post(store.url(trackId), options);
        } else {
            form.put(update.url(module.id), options);
        }
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>
                            {module === null
                                ? t('cms.modules.createTitle')
                                : t('cms.modules.dialogTitle')}
                        </DialogTitle>
                        <DialogDescription>
                            {module === null
                                ? t('cms.modules.createDescription')
                                : t('cms.modules.dialogDescription')}
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
                                form.setData((data) => ({
                                    ...data,
                                    title: event.target.value,
                                    slug: slugEdited
                                        ? data.slug
                                        : slugify(event.target.value),
                                }))
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
                            required
                            aria-describedby={`${id}-slug-help`}
                            onChange={(event) => {
                                setSlugEdited(true);
                                form.setData('slug', event.target.value);
                            }}
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
                            required
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
                                : module === null
                                  ? t('cms.modules.create')
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
 * order, edit their details in a dialog, change their status, add a module
 * at the end and start a lesson in one.
 */
export function ModuleList({
    trackId,
    modules,
    canCreate = false,
    canCreateLesson = false,
}: {
    trackId: number;
    modules: ModuleRow[];
    canCreate?: boolean;
    canCreateLesson?: boolean;
}) {
    const ids = modules.map((module) => module.id);
    const [order, setOrder] = useState(ids);
    // A module row to edit, 'new' to create one, null when closed.
    const [editing, setEditing] = useState<ModuleRow | 'new' | null>(null);
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

    const dialog = editing !== null && (
        <ModuleDialog
            key={editing === 'new' ? 'new' : editing.id}
            trackId={trackId}
            module={editing === 'new' ? null : editing}
            onClose={() => setEditing(null)}
        />
    );

    const addModule = canCreate && (
        <Button
            type="button"
            size="sm"
            variant="outline"
            onClick={() => setEditing('new')}
        >
            <Plus aria-hidden="true" />
            {t('cms.modules.create')}
        </Button>
    );

    if (modules.length === 0) {
        return (
            <div className="space-y-3">
                <p className="text-sm text-muted-foreground">
                    {t('cms.trackEdit.noModules')}
                </p>
                {addModule}
                {dialog}
            </div>
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
                                {/* Under the text: four actions beside it crushed the title. */}
                                <div className="flex flex-wrap items-start gap-2 pt-2">
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
                                    {canCreateLesson && (
                                        <Button
                                            asChild
                                            size="sm"
                                            variant="outline"
                                        >
                                            <Link
                                                href={createLesson({
                                                    query: {
                                                        module: module.id,
                                                    },
                                                })}
                                                aria-label={t(
                                                    'cms.modules.addLessonLabel',
                                                    { module: module.title },
                                                )}
                                            >
                                                <FilePlus aria-hidden="true" />
                                                {t('cms.modules.addLesson')}
                                            </Link>
                                        </Button>
                                    )}
                                    <StatusActions
                                        entity="module"
                                        name={module.title}
                                        current={module.status}
                                        actions={module.status_actions}
                                        url={status.url(module.id)}
                                    />
                                </div>
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

            {addModule}
            {dialog}
        </div>
    );
}
