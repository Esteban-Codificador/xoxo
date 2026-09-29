import type { Editor } from '@tiptap/core';
import { useEditorState } from '@tiptap/react';
import type { LucideIcon } from 'lucide-react';
import {
    Bold,
    Code,
    Heading2,
    Heading3,
    Heading4,
    Info,
    Italic,
    Link,
    List,
    ListOrdered,
    Minus,
    Pilcrow,
    Redo2,
    Sigma,
    SquareCode,
    SquarePlay,
    SquareSigma,
    Strikethrough,
    Table,
    TextQuote,
    Undo2,
    Workflow,
} from 'lucide-react';
import { useId } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { t } from '@/i18n';
import { cn } from '@/lib/utils';
import type { MathKind } from './extensions';
import { askDiagram } from './nodes/diagram';
import { askVideoId } from './nodes/video';
import { useAsk } from './prompt-dialog';
import { askLink, askMath } from './prompts';

function ToolbarButton({
    label,
    icon: Icon,
    pressed,
    disabled,
    onClick,
}: {
    label: string;
    icon: LucideIcon;
    pressed?: boolean;
    disabled?: boolean;
    onClick: () => void;
}) {
    return (
        <Button
            type="button"
            variant="ghost"
            size="icon"
            className={cn(
                'size-8',
                pressed && 'bg-accent text-accent-foreground',
            )}
            aria-label={label}
            title={label}
            aria-pressed={pressed}
            disabled={disabled}
            // Keep the editor's selection while clicking.
            onMouseDown={(event) => event.preventDefault()}
            onClick={onClick}
        >
            <Icon aria-hidden="true" />
        </Button>
    );
}

function Divider() {
    return <span className="mx-1 h-6 w-px self-center bg-border" />;
}

function TextButton({
    children,
    onClick,
}: {
    children: string;
    onClick: () => void;
}) {
    return (
        <Button
            type="button"
            variant="ghost"
            size="sm"
            className="h-7 px-2 text-xs"
            onMouseDown={(event) => event.preventDefault()}
            onClick={onClick}
        >
            {children}
        </Button>
    );
}

export function Toolbar({
    editor,
    controls,
}: {
    editor: Editor;
    controls: string;
}) {
    const ask = useAsk();
    const languageId = useId();

    const state = useEditorState({
        editor,
        selector: ({ editor: current }) => ({
            canUndo: current.can().undo(),
            canRedo: current.can().redo(),
            paragraph: current.isActive('paragraph'),
            h2: current.isActive('heading', { level: 2 }),
            h3: current.isActive('heading', { level: 3 }),
            h4: current.isActive('heading', { level: 4 }),
            bold: current.isActive('bold'),
            italic: current.isActive('italic'),
            strike: current.isActive('strike'),
            code: current.isActive('code'),
            link: current.isActive('link'),
            bulletList: current.isActive('bulletList'),
            orderedList: current.isActive('orderedList'),
            blockquote: current.isActive('blockquote'),
            callout: current.isActive('callout'),
            table: current.isActive('table'),
            language: current.isActive('codeBlock')
                ? String(current.getAttributes('codeBlock').language ?? '')
                : null,
        }),
    });

    const chain = () => editor.chain().focus();

    const editLink = async () => {
        const value = await askLink(
            ask,
            String(editor.getAttributes('link').href ?? ''),
        );
        const href = value?.trim() ?? null;

        if (href === null) {
            editor.commands.focus();
        } else if (href === '') {
            chain().extendMarkRange('link').unsetLink().run();
        } else if (editor.state.selection.empty && !editor.isActive('link')) {
            chain()
                .insertContent({
                    type: 'text',
                    text: href,
                    marks: [{ type: 'link', attrs: { href } }],
                })
                .run();
        } else {
            chain().extendMarkRange('link').setLink({ href }).run();
        }
    };

    const insertMath = async (kind: MathKind) => {
        const latex = await askMath(ask, '', kind === 'blockMath');

        if (latex === null) {
            editor.commands.focus();
        } else if (kind === 'inlineMath') {
            chain().insertInlineMath({ latex }).run();
        } else {
            chain().insertBlockMath({ latex }).run();
        }
    };

    const insertDiagram = async () => {
        const source = await askDiagram(ask);

        if (source === null) {
            editor.commands.focus();
        } else {
            chain()
                .insertContent({
                    type: 'diagram',
                    attrs: { kind: 'mermaid', source },
                })
                .run();
        }
    };

    const insertVideo = async () => {
        const videoId = await askVideoId(ask);

        if (videoId === null) {
            editor.commands.focus();
        } else {
            chain()
                .insertContent({
                    type: 'video',
                    attrs: { provider: 'youtube', videoId },
                })
                .run();
        }
    };

    return (
        <div className="sticky top-0 z-10 rounded-t-lg border-b bg-background">
            <div
                role="toolbar"
                aria-label={t('editor.toolbar')}
                aria-controls={controls}
                className="flex flex-wrap items-center gap-0.5 p-1"
            >
                <ToolbarButton
                    label={t('editor.undo')}
                    icon={Undo2}
                    disabled={!state.canUndo}
                    onClick={() => chain().undo().run()}
                />
                <ToolbarButton
                    label={t('editor.redo')}
                    icon={Redo2}
                    disabled={!state.canRedo}
                    onClick={() => chain().redo().run()}
                />
                <Divider />
                <ToolbarButton
                    label={t('editor.paragraph')}
                    icon={Pilcrow}
                    pressed={state.paragraph}
                    onClick={() => chain().setParagraph().run()}
                />
                <ToolbarButton
                    label={t('editor.heading', { level: 2 })}
                    icon={Heading2}
                    pressed={state.h2}
                    onClick={() => chain().toggleHeading({ level: 2 }).run()}
                />
                <ToolbarButton
                    label={t('editor.heading', { level: 3 })}
                    icon={Heading3}
                    pressed={state.h3}
                    onClick={() => chain().toggleHeading({ level: 3 }).run()}
                />
                <ToolbarButton
                    label={t('editor.heading', { level: 4 })}
                    icon={Heading4}
                    pressed={state.h4}
                    onClick={() => chain().toggleHeading({ level: 4 }).run()}
                />
                <Divider />
                <ToolbarButton
                    label={t('editor.bold')}
                    icon={Bold}
                    pressed={state.bold}
                    onClick={() => chain().toggleBold().run()}
                />
                <ToolbarButton
                    label={t('editor.italic')}
                    icon={Italic}
                    pressed={state.italic}
                    onClick={() => chain().toggleItalic().run()}
                />
                <ToolbarButton
                    label={t('editor.strike')}
                    icon={Strikethrough}
                    pressed={state.strike}
                    onClick={() => chain().toggleStrike().run()}
                />
                <ToolbarButton
                    label={t('editor.code')}
                    icon={Code}
                    pressed={state.code}
                    onClick={() => chain().toggleCode().run()}
                />
                <ToolbarButton
                    label={t('editor.link')}
                    icon={Link}
                    pressed={state.link}
                    onClick={() => void editLink()}
                />
                <Divider />
                <ToolbarButton
                    label={t('editor.bulletList')}
                    icon={List}
                    pressed={state.bulletList}
                    onClick={() => chain().toggleBulletList().run()}
                />
                <ToolbarButton
                    label={t('editor.orderedList')}
                    icon={ListOrdered}
                    pressed={state.orderedList}
                    onClick={() => chain().toggleOrderedList().run()}
                />
                <ToolbarButton
                    label={t('editor.blockquote')}
                    icon={TextQuote}
                    pressed={state.blockquote}
                    onClick={() => chain().toggleBlockquote().run()}
                />
                <ToolbarButton
                    label={t('editor.callout')}
                    icon={Info}
                    pressed={state.callout}
                    onClick={() =>
                        chain().toggleWrap('callout', { variant: 'note' }).run()
                    }
                />
                <ToolbarButton
                    label={t('editor.codeBlock')}
                    icon={SquareCode}
                    pressed={state.language !== null}
                    onClick={() => chain().toggleCodeBlock().run()}
                />
                <ToolbarButton
                    label={t('editor.horizontalRule')}
                    icon={Minus}
                    onClick={() => chain().setHorizontalRule().run()}
                />
                <Divider />
                <ToolbarButton
                    label={t('editor.table')}
                    icon={Table}
                    pressed={state.table}
                    disabled={state.table}
                    onClick={() =>
                        chain()
                            .insertTable({
                                rows: 3,
                                cols: 3,
                                withHeaderRow: true,
                            })
                            .run()
                    }
                />
                <ToolbarButton
                    label={t('editor.inlineMath')}
                    icon={Sigma}
                    onClick={() => void insertMath('inlineMath')}
                />
                <ToolbarButton
                    label={t('editor.blockMath')}
                    icon={SquareSigma}
                    onClick={() => void insertMath('blockMath')}
                />
                <ToolbarButton
                    label={t('editor.diagram')}
                    icon={Workflow}
                    onClick={() => void insertDiagram()}
                />
                <ToolbarButton
                    label={t('editor.video')}
                    icon={SquarePlay}
                    onClick={() => void insertVideo()}
                />
            </div>

            {state.table && (
                <div
                    role="group"
                    aria-label={t('editor.tableTools')}
                    className="flex flex-wrap items-center gap-0.5 border-t px-1 py-0.5"
                >
                    <TextButton onClick={() => chain().addRowAfter().run()}>
                        {t('editor.addRow')}
                    </TextButton>
                    <TextButton onClick={() => chain().addColumnAfter().run()}>
                        {t('editor.addColumn')}
                    </TextButton>
                    <TextButton onClick={() => chain().deleteRow().run()}>
                        {t('editor.deleteRow')}
                    </TextButton>
                    <TextButton onClick={() => chain().deleteColumn().run()}>
                        {t('editor.deleteColumn')}
                    </TextButton>
                    <TextButton onClick={() => chain().toggleHeaderRow().run()}>
                        {t('editor.toggleHeaderRow')}
                    </TextButton>
                    <TextButton onClick={() => chain().deleteTable().run()}>
                        {t('editor.deleteTable')}
                    </TextButton>
                </div>
            )}

            {state.language !== null && (
                <div className="flex items-center gap-2 border-t px-2 py-1">
                    <label
                        htmlFor={languageId}
                        className="text-xs text-muted-foreground"
                    >
                        {t('editor.codeLanguage')}
                    </label>
                    <Input
                        id={languageId}
                        value={state.language}
                        placeholder={t('editor.codeLanguagePlaceholder')}
                        maxLength={20}
                        spellCheck={false}
                        className="h-7 w-40 font-mono text-xs"
                        onChange={(event) => {
                            // Same alphabet as the server; empty = no language.
                            const language = event.target.value
                                .toLowerCase()
                                .replace(/[^a-z0-9+#-]/g, '');

                            editor.commands.updateAttributes('codeBlock', {
                                language: language === '' ? null : language,
                            });
                        }}
                    />
                </div>
            )}
        </div>
    );
}
