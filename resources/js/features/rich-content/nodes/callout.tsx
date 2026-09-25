import type { LucideIcon } from 'lucide-react';
import {
    Info,
    Lightbulb,
    MessageSquareWarning,
    OctagonAlert,
    TriangleAlert,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { t } from '@/i18n';
import { cn } from '@/lib/utils';

export type CalloutVariant =
    | 'note'
    | 'tip'
    | 'important'
    | 'warning'
    | 'caution';

const variants: Record<
    CalloutVariant,
    { icon: LucideIcon; className: string }
> = {
    note: {
        icon: Info,
        className: 'border-callout-note [&_.callout-label]:text-callout-note',
    },
    tip: {
        icon: Lightbulb,
        className: 'border-callout-tip [&_.callout-label]:text-callout-tip',
    },
    important: {
        icon: MessageSquareWarning,
        className:
            'border-callout-important [&_.callout-label]:text-callout-important',
    },
    warning: {
        icon: TriangleAlert,
        className:
            'border-callout-warning [&_.callout-label]:text-callout-warning',
    },
    caution: {
        icon: OctagonAlert,
        className:
            'border-callout-caution [&_.callout-label]:text-callout-caution',
    },
};

export function isCalloutVariant(value: unknown): value is CalloutVariant {
    return typeof value === 'string' && value in variants;
}

/** Icon and label carry the meaning; the color only reinforces it. */
export function Callout({
    variant,
    children,
}: {
    variant: CalloutVariant;
    children: ReactNode;
}) {
    const { icon: Icon, className } = variants[variant];

    return (
        <aside
            data-variant={variant}
            className={cn(
                'not-prose my-6 rounded-r-lg border-l-4 bg-muted/60 px-4 py-3',
                className,
            )}
        >
            <p className="callout-label mb-1 flex items-center gap-2 text-sm font-semibold">
                <Icon className="size-4 shrink-0" aria-hidden="true" />
                {t(`callout.${variant}`)}
            </p>
            <div className="rich-content prose max-w-none text-[0.95em] [&>:first-child]:mt-0 [&>:last-child]:mb-0">
                {children}
            </div>
        </aside>
    );
}
