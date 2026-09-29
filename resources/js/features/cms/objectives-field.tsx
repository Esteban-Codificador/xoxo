import { Plus, X } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { t } from '@/i18n';

const MAX_OBJECTIVES = 12;

/** Editable list of learning objectives (1 to 12, validated on the server). */
export function ObjectivesField({
    id,
    value,
    errors,
    onChange,
}: {
    id: string;
    value: string[];
    /** Server errors keyed by index ("learning_objectives.2"). */
    errors: (index: number) => string | undefined;
    onChange: (value: string[]) => void;
}) {
    const update = (index: number, text: string) =>
        onChange(value.map((item, i) => (i === index ? text : item)));

    return (
        <div className="space-y-2">
            <ol className="space-y-2">
                {value.map((objective, index) => {
                    const number = index + 1;
                    const inputId = `${id}-${index}`;
                    const error = errors(index);

                    return (
                        <li key={index} className="space-y-1">
                            <div className="flex items-center gap-2">
                                <label
                                    htmlFor={inputId}
                                    className="w-6 shrink-0 text-right text-sm text-muted-foreground tabular-nums"
                                >
                                    <span aria-hidden="true">{number}.</span>
                                    <span className="sr-only">
                                        {t('cms.edit.fields.objective', {
                                            number,
                                        })}
                                    </span>
                                </label>
                                <Input
                                    id={inputId}
                                    value={objective}
                                    maxLength={300}
                                    aria-invalid={error !== undefined}
                                    onChange={(event) =>
                                        update(index, event.target.value)
                                    }
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="shrink-0"
                                    aria-label={t(
                                        'cms.edit.fields.removeObjective',
                                        { number },
                                    )}
                                    disabled={value.length === 1}
                                    onClick={() =>
                                        onChange(
                                            value.filter((_, i) => i !== index),
                                        )
                                    }
                                >
                                    <X aria-hidden="true" />
                                </Button>
                            </div>
                            <InputError message={error} className="pl-8" />
                        </li>
                    );
                })}
            </ol>
            <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={value.length >= MAX_OBJECTIVES}
                onClick={() => onChange([...value, ''])}
            >
                <Plus aria-hidden="true" />
                {t('cms.edit.fields.addObjective')}
            </Button>
        </div>
    );
}
