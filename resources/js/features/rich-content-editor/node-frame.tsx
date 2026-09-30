import { NodeViewWrapper } from '@tiptap/react';
import { Pencil, Trash2 } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { t } from '@/i18n';
import { cn } from '@/lib/utils';

/**
 * Frame for atom nodes (diagram, video, image): the preview uses the same
 * component the learner sees, plus explicit Edit and Remove buttons so the
 * node can be managed without selecting it with the mouse.
 */
export function NodeFrame({
    label,
    editLabel = t('editor.edit'),
    selected,
    onEdit,
    onRemove,
    children,
}: {
    label: string;
    /** What the edit button changes, when "Editar" says too little. */
    editLabel?: string;
    selected: boolean;
    onEdit: () => void;
    onRemove: () => void;
    children: ReactNode;
}) {
    return (
        <NodeViewWrapper
            className={cn(
                'not-prose my-6 rounded-lg border bg-card',
                selected && 'ring-2 ring-ring',
            )}
        >
            <div
                contentEditable={false}
                className="flex items-center justify-between gap-2 border-b px-3 py-1 text-xs text-muted-foreground"
            >
                <span className="truncate font-medium">{label}</span>
                <span className="flex shrink-0 gap-1">
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        aria-label={`${editLabel}: ${label}`}
                        onClick={onEdit}
                    >
                        <Pencil aria-hidden="true" />
                        {/* Icons only on phones: the label keeps its room. */}
                        <span className="hidden sm:inline">{editLabel}</span>
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        aria-label={`${t('editor.remove')}: ${label}`}
                        onClick={onRemove}
                    >
                        <Trash2 aria-hidden="true" />
                        <span className="hidden sm:inline">
                            {t('editor.remove')}
                        </span>
                    </Button>
                </span>
            </div>
            <div contentEditable={false} className="p-3 [&>*]:my-0">
                {children}
            </div>
        </NodeViewWrapper>
    );
}
