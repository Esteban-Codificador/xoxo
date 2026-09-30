import { getSchema } from '@tiptap/core';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import allowlist from '@/features/rich-content/allowlist.json';
import fixture from '@/features/rich-content/every-node.fixture.json';
import type { RichContent, RichNode } from '@/features/rich-content/types';
import { editorExtensions, normalizeLanguage } from './extensions';
import RichContentEditor from './rich-content-editor';
import { parseYouTubeId } from './youtube';

vi.mock('mermaid', () => ({
    default: {
        initialize: vi.fn(),
        render: vi.fn(() => Promise.resolve({ svg: '<svg></svg>' })),
    },
}));

const schema = getSchema(editorExtensions({ onEditMath: () => undefined }));

const sorted = (record: Record<string, string[]>) =>
    Object.fromEntries(
        Object.entries(record)
            .map(([name, attrs]) => [name, [...attrs].sort()])
            .sort(([a], [b]) => String(a).localeCompare(String(b))),
    );

describe('editor schema', () => {
    // allowlist.json is pinned to RichContentSchema by a Pest test (TD-5).
    it('has exactly the nodes and attributes the server accepts', () => {
        const nodes: Record<string, string[]> = {};
        schema.spec.nodes.forEach((name, spec) => {
            nodes[name] = Object.keys(spec.attrs ?? {});
        });

        expect(sorted(nodes)).toEqual(sorted(allowlist.nodes));
    });

    it('has exactly the marks and attributes the server accepts', () => {
        const marks: Record<string, string[]> = {};
        schema.spec.marks.forEach((name, spec) => {
            marks[name] = Object.keys(spec.attrs ?? {});
        });

        expect(sorted(marks)).toEqual(sorted(allowlist.marks));
    });

    it('loads and saves a document with every node unchanged', () => {
        // What the editor does on load (fromJSON) and on save (toJSON).
        const doc = schema.nodeFromJSON(fixture.doc);
        doc.check();

        expect(doc.toJSON()).toEqual(fixture.doc);
    });

    it('keeps only languages the server accepts', () => {
        expect(normalizeLanguage(' Python ')).toBe('python');
        expect(normalizeLanguage('c++')).toBe('c++');
        expect(normalizeLanguage('shell script')).toBeNull();
        expect(normalizeLanguage(undefined)).toBeNull();
    });
});

describe('parseYouTubeId', () => {
    it.each([
        ['aircAruvnKk', 'aircAruvnKk'],
        ['https://www.youtube.com/watch?v=aircAruvnKk&t=30s', 'aircAruvnKk'],
        ['https://youtu.be/aircAruvnKk?si=abc', 'aircAruvnKk'],
        ['https://www.youtube.com/embed/aircAruvnKk', 'aircAruvnKk'],
        ['https://youtube.com/shorts/aircAruvnKk', 'aircAruvnKk'],
    ])('reads %s', (input, id) => {
        expect(parseYouTubeId(input)).toBe(id);
    });

    it.each([
        'https://vimeo.com/123456789',
        'https://www.youtube.com/watch?v=short',
        'javascript:alert(1)',
        '<iframe src="https://www.youtube.com/embed/aircAruvnKk">',
        '',
    ])('rejects %s', (input) => {
        expect(parseYouTubeId(input)).toBeNull();
    });
});

describe('RichContentEditor', () => {
    const value: RichContent = {
        version: 1,
        doc: {
            type: 'doc',
            content: [
                {
                    type: 'paragraph',
                    content: [{ type: 'text', text: 'Primer párrafo' }],
                },
                {
                    type: 'callout',
                    attrs: { variant: 'tip' },
                    content: [
                        {
                            type: 'paragraph',
                            content: [{ type: 'text', text: 'Un consejo' }],
                        },
                    ],
                },
            ],
        },
    };

    const renderEditor = (onChange = vi.fn()) => {
        render(
            <>
                <span id="body-label">Contenido</span>
                <RichContentEditor
                    value={value}
                    onChange={onChange}
                    labelledBy="body-label"
                />
            </>,
        );

        return onChange;
    };

    it('renders the content with a labelled toolbar and textbox', async () => {
        renderEditor();

        expect(
            screen.getByRole('toolbar', { name: 'Formato del contenido' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('textbox', { name: 'Contenido' }),
        ).toHaveTextContent('Primer párrafo');
        expect(
            await screen.findByRole('combobox', { name: 'Tipo de destacado' }),
        ).toHaveValue('tip');
    });

    it('emits the RichContent envelope only after a real edit', async () => {
        const onChange = renderEditor();
        expect(onChange).not.toHaveBeenCalled();

        await userEvent.selectOptions(
            await screen.findByRole('combobox', { name: 'Tipo de destacado' }),
            'warning',
        );

        await waitFor(() => expect(onChange).toHaveBeenCalled());
        const envelope = onChange.mock.lastCall?.[0] as RichContent;
        const callout = envelope.doc.content?.[1] as RichNode;
        expect(envelope.version).toBe(1);
        expect(callout).toMatchObject({
            type: 'callout',
            attrs: { variant: 'warning' },
        });
    });
});

describe('dialogs', () => {
    it('never submit the page form around the editor', async () => {
        const submitted = vi.fn((event: { preventDefault: () => void }) =>
            event.preventDefault(),
        );
        render(
            <form onSubmit={submitted}>
                <span id="body-label">Contenido</span>
                <RichContentEditor
                    value={{
                        version: 1,
                        doc: { type: 'doc', content: [{ type: 'paragraph' }] },
                    }}
                    onChange={vi.fn()}
                    labelledBy="body-label"
                />
            </form>,
        );

        await userEvent.click(screen.getByRole('button', { name: 'Diagrama' }));
        const prompt = await screen.findByRole('dialog', { name: 'Diagrama' });
        await userEvent.type(
            within(prompt).getByLabelText('Código Mermaid'),
            'graph LR; A --> B',
        );
        await userEvent.click(
            within(prompt).getByRole('button', { name: 'Aplicar' }),
        );

        await userEvent.click(screen.getByRole('button', { name: 'Imagen' }));
        const image = await screen.findByRole('dialog', { name: 'Imagen' });
        await userEvent.click(
            within(image).getByRole('button', { name: 'Subir e insertar' }),
        );

        expect(submitted).not.toHaveBeenCalled();
    });
});

describe('image dialog', () => {
    it('asks for a file and its alternative text before uploading', async () => {
        render(
            <>
                <span id="body-label">Contenido</span>
                <RichContentEditor
                    value={{
                        version: 1,
                        doc: { type: 'doc', content: [{ type: 'paragraph' }] },
                    }}
                    onChange={vi.fn()}
                    labelledBy="body-label"
                />
            </>,
        );

        await userEvent.click(screen.getByRole('button', { name: 'Imagen' }));
        const dialog = await screen.findByRole('dialog', { name: 'Imagen' });
        await userEvent.click(
            within(dialog).getByRole('button', { name: 'Subir e insertar' }),
        );

        expect(dialog).toHaveTextContent('Elige una imagen.');
        expect(dialog).toHaveTextContent('Escribe el texto alternativo.');

        // Writing clears the message at once.
        await userEvent.type(
            within(dialog).getByLabelText('Texto alternativo'),
            'Diagrama',
        );
        expect(dialog).not.toHaveTextContent('Escribe el texto alternativo.');

        // The file picker filters by `accept`; "all files" or a drop does not.
        await userEvent
            .setup({ applyAccept: false })
            .upload(
                within(dialog).getByLabelText('Archivo'),
                new File(['GIF89a'], 'animacion.gif', { type: 'image/gif' }),
            );

        expect(dialog).toHaveTextContent(
            'Solo se admiten imágenes PNG, JPEG o WebP.',
        );
    });

    it('shows an image of the document with the URL the page sent', () => {
        render(
            <>
                <span id="body-label">Contenido</span>
                <RichContentEditor
                    value={{
                        version: 1,
                        doc: {
                            type: 'doc',
                            content: [
                                {
                                    type: 'image',
                                    attrs: { mediaId: 3, alt: 'Esquema' },
                                },
                            ],
                        },
                    }}
                    media={{ 3: { url: '/media/3?s=x', width: 10, height: 5 } }}
                    onChange={vi.fn()}
                    labelledBy="body-label"
                />
            </>,
        );

        expect(screen.getByRole('img', { name: 'Esquema' })).toHaveAttribute(
            'src',
            '/media/3?s=x',
        );
        expect(
            screen.getByRole('button', {
                name: 'Texto alternativo: Imagen · Esquema',
            }),
        ).toBeInTheDocument();
    });
});
