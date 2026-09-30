import { useHttp } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect, useId, useState } from 'react';
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
import type { MediaSource } from '@/features/rich-content/types';
import { t } from '@/i18n';
import { store } from '@/routes/admin/media';
import { altTextError, MAX_ALT_LENGTH } from './nodes/image';

export type UploadedImage = MediaSource & { id: number };

/** Mirrors ImageProcessor: the server checks the bytes again. */
const TYPES = ['image/png', 'image/jpeg', 'image/webp'];
const MAX_BYTES = 5 * 1024 * 1024;

function fileError(file: File | null): string | null {
    if (file === null) {
        return t('editor.image.fileRequired');
    }

    if (!TYPES.includes(file.type)) {
        return t('editor.image.typeError');
    }

    return file.size > MAX_BYTES ? t('editor.image.sizeError') : null;
}

/**
 * Upload an image and describe it. The file is re-encoded on the server
 * (ADR-034); the editor inserts the image once it is stored.
 */
export function ImageDialog({
    onClose,
    onInsert,
}: {
    onClose: () => void;
    onInsert: (image: UploadedImage, alt: string) => void;
}) {
    const id = useId();
    const http = useHttp<{ image: File | null }, UploadedImage>({
        image: null,
    });
    const [alt, setAlt] = useState('');
    const [preview, setPreview] = useState<string | null>(null);
    const [errors, setErrors] = useState<{ image?: string; alt?: string }>({});
    const imageError = errors.image ?? http.errors.image;

    useEffect(
        () => () => {
            if (preview !== null) {
                URL.revokeObjectURL(preview);
            }
        },
        [preview],
    );

    const choose = (file: File | null) => {
        http.clearErrors();
        http.setData('image', file);
        setPreview(file === null ? null : URL.createObjectURL(file));
        setErrors((current) => ({
            ...current,
            image: file === null ? undefined : (fileError(file) ?? undefined),
        }));
    };

    const failed = (message: string) =>
        setErrors((current) => ({ ...current, image: message }));

    const submit = (event: FormEvent) => {
        event.preventDefault();
        // Not the page form around the editor (see PromptDialog).
        event.stopPropagation();
        const next = {
            image: fileError(http.data.image) ?? undefined,
            alt: altTextError(alt) ?? undefined,
        };
        setErrors(next);

        if (next.image !== undefined || next.alt !== undefined) {
            return;
        }

        http.post(store.url(), {
            onSuccess: (image) => onInsert(image, alt.trim()),
            onHttpException: (response) =>
                failed(
                    response.status === 429
                        ? t('editor.image.tooMany')
                        : response.status === 413
                          ? t('editor.image.sizeError')
                          : t('editor.image.failed'),
                ),
            onNetworkError: () => failed(t('editor.image.failed')),
        }).catch(() => undefined);
    };

    return (
        <Dialog
            open
            onOpenChange={(open) => !open && !http.processing && onClose()}
        >
            <DialogContent onCloseAutoFocus={(event) => event.preventDefault()}>
                <form onSubmit={submit} className="space-y-4" noValidate>
                    <DialogHeader>
                        <DialogTitle>{t('editor.image.title')}</DialogTitle>
                        <DialogDescription>
                            {t('editor.image.description')}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor={`${id}-file`}>
                            {t('editor.image.fileLabel')}
                        </Label>
                        <Input
                            id={`${id}-file`}
                            type="file"
                            accept={TYPES.join(',')}
                            autoFocus
                            aria-invalid={imageError !== undefined}
                            aria-describedby={`${id}-file-help ${id}-file-error`}
                            onChange={(event) =>
                                choose(event.target.files?.[0] ?? null)
                            }
                        />
                        <p
                            id={`${id}-file-help`}
                            className="text-xs text-muted-foreground"
                        >
                            {t('editor.image.fileHelp')}
                        </p>
                        <InputError
                            id={`${id}-file-error`}
                            message={imageError}
                        />
                    </div>

                    {preview !== null && (
                        <img
                            src={preview}
                            alt=""
                            className="mx-auto max-h-48 max-w-full rounded-md border bg-white object-contain"
                        />
                    )}

                    <div className="grid gap-2">
                        <Label htmlFor={`${id}-alt`}>
                            {t('editor.image.altLabel')}
                        </Label>
                        <Input
                            id={`${id}-alt`}
                            value={alt}
                            maxLength={MAX_ALT_LENGTH}
                            aria-invalid={errors.alt !== undefined}
                            aria-describedby={`${id}-alt-help ${id}-alt-error`}
                            onChange={(event) => {
                                setAlt(event.target.value);
                                setErrors((current) => ({
                                    ...current,
                                    alt: undefined,
                                }));
                            }}
                        />
                        <p
                            id={`${id}-alt-help`}
                            className="text-xs text-muted-foreground"
                        >
                            {t('editor.image.altHelp')}
                        </p>
                        <InputError
                            id={`${id}-alt-error`}
                            message={errors.alt}
                        />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={http.processing}
                            onClick={onClose}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button type="submit" disabled={http.processing}>
                            {http.processing
                                ? t('editor.image.uploading')
                                : t('editor.image.insert')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
