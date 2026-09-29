import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';

/**
 * Label, control, optional help (id `${id}-help`, for aria-describedby)
 * and the server error of one form field.
 */
export function Field({
    id,
    label,
    help,
    error,
    children,
}: {
    id: string;
    label: string;
    help?: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div className="grid content-start gap-2">
            <Label htmlFor={id}>{label}</Label>
            {children}
            {help !== undefined && (
                <p id={`${id}-help`} className="text-xs text-muted-foreground">
                    {help}
                </p>
            )}
            <InputError message={error} />
        </div>
    );
}
