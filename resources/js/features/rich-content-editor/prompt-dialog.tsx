import type { FormEvent, ReactNode } from 'react';
import { createContext, useCallback, useContext, useId, useState } from 'react';
import InputError from '@/components/input-error';
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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { t } from '@/i18n';

export type PromptRequest = {
    title: string;
    label: string;
    help: string;
    initial?: string;
    multiline?: boolean;
    /** Returns an error message, or null when the value is acceptable. */
    validate?: (value: string) => string | null;
};

/** Resolves with the accepted value, or null when the editor cancels. */
export type Ask = (request: PromptRequest) => Promise<string | null>;

type Pending = PromptRequest & {
    id: number;
    resolve: (value: string | null) => void;
};

const PromptContext = createContext<Ask | null>(null);

export function useAsk(): Ask {
    const ask = useContext(PromptContext);

    if (ask === null) {
        throw new Error('useAsk() must be used inside <PromptProvider>.');
    }

    return ask;
}

/**
 * One dialog for every value the editor needs (link, formula, diagram,
 * video). The caller moves the focus back into the editor afterwards.
 */
export function PromptProvider({ children }: { children: ReactNode }) {
    const [pending, setPending] = useState<Pending | null>(null);

    const ask = useCallback<Ask>(
        (request) =>
            new Promise((resolve) =>
                setPending({ ...request, id: Date.now(), resolve }),
            ),
        [],
    );

    const close = (value: string | null) => {
        pending?.resolve(value);
        setPending(null);
    };

    return (
        <PromptContext value={ask}>
            {children}
            {pending !== null && (
                <PromptDialog
                    key={pending.id}
                    request={pending}
                    onClose={close}
                />
            )}
        </PromptContext>
    );
}

function PromptDialog({
    request,
    onClose,
}: {
    request: PromptRequest;
    onClose: (value: string | null) => void;
}) {
    const id = useId();
    const [value, setValue] = useState(request.initial ?? '');
    const [error, setError] = useState<string | null>(null);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const message = request.validate?.(value) ?? null;

        if (message !== null) {
            setError(message);

            return;
        }

        onClose(value);
    };

    const fieldProps = {
        id,
        value,
        autoFocus: true,
        'aria-invalid': error !== null,
        'aria-describedby': error !== null ? `${id}-error` : undefined,
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose(null)}>
            <DialogContent onCloseAutoFocus={(event) => event.preventDefault()}>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>{request.title}</DialogTitle>
                        <DialogDescription>{request.help}</DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-2">
                        <Label htmlFor={id}>{request.label}</Label>
                        {request.multiline ? (
                            <Textarea
                                {...fieldProps}
                                rows={8}
                                spellCheck={false}
                                className="font-mono text-sm"
                                onChange={(event) =>
                                    setValue(event.target.value)
                                }
                            />
                        ) : (
                            <Input
                                {...fieldProps}
                                onChange={(event) =>
                                    setValue(event.target.value)
                                }
                            />
                        )}
                        <InputError
                            id={`${id}-error`}
                            message={error ?? undefined}
                        />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onClose(null)}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button type="submit">{t('editor.apply')}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
