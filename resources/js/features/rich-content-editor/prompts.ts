import { safeHref } from '@/features/rich-content/safe-href';
import { t } from '@/i18n';
import type { Ask } from './prompt-dialog';

/** An empty answer means "remove the link". */
export function askLink(ask: Ask, initial: string): Promise<string | null> {
    return ask({
        title: t('editor.prompts.linkTitle'),
        label: t('editor.prompts.linkLabel'),
        help: t('editor.prompts.linkHelp'),
        initial,
        validate: (value) =>
            value.trim() === '' || safeHref(value.trim()) !== null
                ? null
                : t('editor.prompts.linkInvalid'),
    });
}

export function askMath(
    ask: Ask,
    initial: string,
    block: boolean,
): Promise<string | null> {
    return ask({
        title: t('editor.prompts.mathTitle'),
        label: t('editor.prompts.mathLabel'),
        help: t('editor.prompts.mathHelp'),
        initial,
        multiline: block,
        validate: (value) =>
            value.trim() === '' ? t('editor.prompts.mathEmpty') : null,
    });
}
