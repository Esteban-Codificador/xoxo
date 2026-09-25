import { render, screen, waitFor, within } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { RichContentRenderer } from './rich-content-renderer';
import { safeHref } from './safe-href';
import { collectHeadings, slugify } from './text';
import type { RichNode } from './types';

const mermaid = vi.hoisted(() => ({
    initialize: vi.fn(),
    render: vi.fn(),
}));

vi.mock('mermaid', () => ({ default: mermaid }));

const text = (value: string, marks: RichNode['marks'] = []): RichNode => ({
    type: 'text',
    text: value,
    marks,
});

const paragraph = (...content: RichNode[]): RichNode => ({
    type: 'paragraph',
    content,
});

function renderDoc(...content: RichNode[]) {
    return render(
        <RichContentRenderer
            content={{ version: 1, doc: { type: 'doc', content } }}
        />,
    );
}

afterEach(() => {
    mermaid.initialize.mockReset();
    mermaid.render.mockReset();
});

describe('text and marks', () => {
    it('renders every mark with its semantic element', () => {
        const { container } = renderDoc(
            paragraph(
                text('negrita', [{ type: 'bold' }]),
                text('cursiva', [{ type: 'italic' }]),
                text('tachado', [{ type: 'strike' }]),
                text('x = 1', [{ type: 'code' }]),
            ),
        );

        expect(container.querySelector('strong')).toHaveTextContent('negrita');
        expect(container.querySelector('em')).toHaveTextContent('cursiva');
        expect(container.querySelector('s')).toHaveTextContent('tachado');
        expect(container.querySelector('p code')).toHaveTextContent('x = 1');
    });

    it('opens external links in a new tab without leaking the opener', () => {
        renderDoc(
            paragraph(
                text('la documentación', [
                    {
                        type: 'link',
                        attrs: { href: 'https://docs.python.org/3/' },
                    },
                ]),
            ),
        );

        const link = screen.getByRole('link', { name: /la documentación/ });
        expect(link).toHaveAttribute('href', 'https://docs.python.org/3/');
        expect(link).toHaveAttribute('target', '_blank');
        expect(link).toHaveAttribute('rel', 'noopener noreferrer');
        expect(link).toHaveTextContent('(se abre en una pestaña nueva)');
    });

    it('keeps internal, anchor and mail links in the same tab', () => {
        renderDoc(
            paragraph(
                text('otra lección', [
                    { type: 'link', attrs: { href: '/lecciones/git' } },
                ]),
                text('arriba', [{ type: 'link', attrs: { href: '#inicio' } }]),
                text('correo', [
                    {
                        type: 'link',
                        attrs: { href: 'mailto:equipo@example.com' },
                    },
                ]),
            ),
        );

        for (const name of ['otra lección', 'arriba', 'correo']) {
            expect(screen.getByRole('link', { name })).not.toHaveAttribute(
                'target',
            );
        }

        expect(
            screen.getByRole('link', { name: 'otra lección' }),
        ).toHaveAttribute('href', '/lecciones/git');
    });

    it.each([
        'javascript:alert(1)',
        'JavaScript:alert(1)',
        ' javascript:alert(1)',
        'data:text/html,<script>alert(1)</script>',
        'vbscript:msgbox(1)',
        '//evil.example.com',
        'java\tscript:alert(1)',
    ])('drops the link but keeps the text for %s', (href) => {
        const { container } = renderDoc(
            paragraph(text('haz clic', [{ type: 'link', attrs: { href } }])),
        );

        expect(container.querySelector('a')).toBeNull();
        expect(container).toHaveTextContent('haz clic');
    });

    it('treats text as text, never as markup', () => {
        const { container } = renderDoc(
            paragraph(text('<img src=x onerror=alert(1)>')),
        );

        expect(container.querySelector('img')).toBeNull();
        expect(container).toHaveTextContent('<img src=x onerror=alert(1)>');
    });
});

describe('unknown content', () => {
    it('skips unknown nodes and marks and warns once in development', () => {
        const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});

        const { container } = renderDoc(
            { type: 'iframe', attrs: { src: 'https://evil.example.com' } },
            paragraph(text('visible', [{ type: 'blink' }])),
            { type: 'iframe' },
        );

        expect(container.querySelector('iframe')).toBeNull();
        expect(container).toHaveTextContent('visible');
        expect(warn).toHaveBeenCalledWith(expect.stringContaining('iframe'));
        expect(warn).toHaveBeenCalledWith(expect.stringContaining('blink'));
        expect(
            warn.mock.calls.filter(([message]) =>
                String(message).includes('iframe'),
            ),
        ).toHaveLength(1);
    });
});

describe('headings', () => {
    it('slugifies Spanish text', () => {
        expect(slugify('¿Por qué importa el árbol de decisión?')).toBe(
            'por-que-importa-el-arbol-de-decision',
        );
        expect(slugify('¡¿?!')).toBe('seccion');
    });

    it('gives each heading a unique id that matches the table of contents', () => {
        const doc: RichNode[] = [
            {
                type: 'heading',
                attrs: { level: 2 },
                content: [text('Práctica')],
            },
            {
                type: 'heading',
                attrs: { level: 3 },
                content: [text('Práctica')],
            },
            {
                type: 'heading',
                attrs: { level: 4 },
                content: [text('Código', [{ type: 'bold' }])],
            },
        ];

        renderDoc(...doc);

        expect(
            collectHeadings({ type: 'doc', content: doc }).map(
                ({ id, level }) => [id, level],
            ),
        ).toEqual([
            ['practica', 2],
            ['practica-2', 3],
            ['codigo', 4],
        ]);
        expect(screen.getByRole('heading', { level: 2 })).toHaveAttribute(
            'id',
            'practica',
        );
        expect(screen.getByRole('heading', { level: 3 })).toHaveAttribute(
            'id',
            'practica-2',
        );
        expect(screen.getByRole('heading', { level: 4 })).toHaveAttribute(
            'id',
            'codigo',
        );
    });
});

describe('blocks', () => {
    it('renders lists, quotes and rules', () => {
        const { container } = renderDoc(
            {
                type: 'orderedList',
                attrs: { start: 3 },
                content: [
                    { type: 'listItem', content: [paragraph(text('tercero'))] },
                ],
            },
            {
                type: 'bulletList',
                content: [
                    { type: 'listItem', content: [paragraph(text('viñeta'))] },
                ],
            },
            { type: 'blockquote', content: [paragraph(text('cita'))] },
            { type: 'horizontalRule' },
            paragraph(text('línea'), { type: 'hardBreak' }, text('siguiente')),
        );

        expect(container.querySelector('ol')).toHaveAttribute('start', '3');
        expect(container.querySelector('ul li')).toHaveTextContent('viñeta');
        expect(container.querySelector('blockquote')).toHaveTextContent('cita');
        expect(container.querySelector('hr')).not.toBeNull();
        expect(container.querySelector('p br')).not.toBeNull();
    });

    it('renders tables with a header row inside a scrollable region', () => {
        const cell = (type: string, value: string, attrs = {}): RichNode => ({
            type,
            attrs,
            content: [paragraph(text(value))],
        });

        renderDoc({
            type: 'table',
            content: [
                {
                    type: 'tableRow',
                    content: [
                        cell('tableHeader', 'Modelo'),
                        cell('tableHeader', 'Uso'),
                    ],
                },
                {
                    type: 'tableRow',
                    content: [
                        cell('tableCell', 'Regresión'),
                        cell('tableCell', 'Predecir'),
                    ],
                },
                {
                    type: 'tableRow',
                    content: [cell('tableCell', 'Ambos', { colspan: 2 })],
                },
            ],
        });

        const region = screen.getByRole('region', { name: 'Tabla' });
        expect(region).toHaveAttribute('tabindex', '0');
        expect(
            within(region).getByRole('columnheader', { name: 'Modelo' }),
        ).toHaveAttribute('scope', 'col');
        expect(within(region).getAllByRole('row')).toHaveLength(3);
        expect(
            within(region).getByRole('cell', { name: 'Ambos' }),
        ).toHaveAttribute('colspan', '2');
    });

    it('labels callouts with an icon and text, not only color', () => {
        const { container } = renderDoc(
            {
                type: 'callout',
                attrs: { variant: 'warning' },
                content: [paragraph(text('Cuidado con los datos'))],
            },
            {
                type: 'callout',
                attrs: { variant: 'tip' },
                content: [paragraph(text('Usa venv'))],
            },
        );

        const [warning, tip] = container.querySelectorAll('aside');
        expect(warning).toHaveTextContent('Advertencia');
        expect(warning).toHaveTextContent('Cuidado con los datos');
        expect(tip).toHaveTextContent('Consejo');
    });
});

describe('video', () => {
    it('embeds YouTube through the privacy-enhanced domain', () => {
        renderDoc({
            type: 'video',
            attrs: { provider: 'youtube', videoId: 'aircAruvnKk' },
        });

        const frame = screen.getByTitle('Video de YouTube');
        expect(frame).toHaveAttribute(
            'src',
            'https://www.youtube-nocookie.com/embed/aircAruvnKk',
        );
        expect(frame).toHaveAttribute('loading', 'lazy');
    });

    it.each([
        { provider: 'youtube', videoId: 'x"><script>' },
        { provider: 'youtube', videoId: 'short' },
        { provider: 'vimeo', videoId: 'aircAruvnKk' },
    ])('refuses $provider/$videoId', (attrs) => {
        const { container } = renderDoc({ type: 'video', attrs });

        expect(container.querySelector('iframe')).toBeNull();
        expect(container).toHaveTextContent('Video no disponible');
    });
});

describe('code', () => {
    it('shows the code immediately and highlights it once Shiki loads', async () => {
        const code = 'def saludar(nombre):\n    return f"Hola, {nombre}"';
        const { container } = renderDoc({
            type: 'codeBlock',
            attrs: { language: 'py' },
            content: [text(code)],
        });

        const pre = container.querySelector('pre');
        expect(pre).toHaveTextContent('def saludar(nombre):');

        await waitFor(
            () =>
                expect(
                    pre?.querySelectorAll('span[style]').length,
                ).toBeGreaterThan(3),
            {
                timeout: 5000,
            },
        );
        expect(pre?.dataset.language).toBe('python');
        expect(pre?.textContent).toBe(code);
        expect(pre?.querySelector('[style*="--shiki-dark"]')).not.toBeNull();
    });

    it('leaves unknown languages as plain text', async () => {
        const { container } = renderDoc({
            type: 'codeBlock',
            attrs: { language: 'cobol' },
            content: [text('DISPLAY "HOLA".')],
        });

        expect(container.querySelector('figcaption')).toHaveTextContent(
            'cobol',
        );
        expect(container.querySelector('pre span[style]')).toBeNull();
        expect(container.querySelector('pre')).toHaveTextContent(
            'DISPLAY "HOLA".',
        );
    });
});

describe('math', () => {
    it('renders LaTeX with KaTeX, inline and as a block', async () => {
        const { container } = renderDoc(
            paragraph(text('La pérdida es '), {
                type: 'inlineMath',
                attrs: {
                    latex: 'L = \\frac{1}{n}\\sum_i (y_i - \\hat{y}_i)^2',
                },
            }),
            { type: 'blockMath', attrs: { latex: 'e^{i\\pi} + 1 = 0' } },
        );

        await waitFor(() =>
            expect(container.querySelectorAll('.katex')).toHaveLength(2),
        );
        expect(
            container.querySelector('[data-math="block"] .katex-display'),
        ).not.toBeNull();
        expect(container.querySelector('math')).not.toBeNull();
    });

    it('shows the source and an error for invalid LaTeX', async () => {
        const { container } = renderDoc({
            type: 'blockMath',
            attrs: { latex: '\\frac{1}{' },
        });

        expect(
            await screen.findByText('Fórmula no válida'),
        ).toBeInTheDocument();
        expect(
            container.querySelector('[data-math="block"]'),
        ).toHaveTextContent('\\frac{1}{');
    });

    it('does not honor \\href or \\htmlClass from the source', async () => {
        const { container } = renderDoc({
            type: 'blockMath',
            attrs: {
                latex: '\\href{javascript:alert(1)}{x} + \\htmlClass{evil}{y}',
            },
        });

        await waitFor(() =>
            expect(container.querySelector('.katex')).not.toBeNull(),
        );
        expect(container.querySelector('a')).toBeNull();
        expect(container.querySelector('.evil')).toBeNull();
    });
});

describe('diagrams', () => {
    it('renders Mermaid in strict mode', async () => {
        mermaid.render.mockResolvedValue({
            svg: '<svg data-testid="diagram"><g></g></svg>',
        });

        renderDoc({
            type: 'diagram',
            attrs: { kind: 'mermaid', source: 'graph TD; A-->B' },
        });

        expect(await screen.findByTestId('diagram')).toBeInTheDocument();
        expect(mermaid.initialize).toHaveBeenCalledWith(
            expect.objectContaining({
                securityLevel: 'strict',
                startOnLoad: false,
            }),
        );
        expect(mermaid.render).toHaveBeenCalledWith(
            expect.any(String),
            'graph TD; A-->B',
        );
        expect(screen.getByRole('img', { name: 'Diagrama' })).toHaveAttribute(
            'aria-busy',
            'false',
        );
    });

    it('falls back to the source when Mermaid cannot parse it', async () => {
        mermaid.render.mockRejectedValue(new Error('Parse error'));

        renderDoc({
            type: 'diagram',
            attrs: { kind: 'mermaid', source: 'graph TD; A--' },
        });

        expect(
            await screen.findByText('No se pudo dibujar el diagrama.'),
        ).toBeInTheDocument();
        expect(screen.getByText('graph TD; A--')).toBeInTheDocument();
    });
});

describe('safeHref', () => {
    it('mirrors the server allowlist', () => {
        expect(safeHref('https://example.com')?.kind).toBe('external');
        expect(safeHref('http://example.com')?.kind).toBe('external');
        expect(safeHref('mailto:a@b.co')?.kind).toBe('mail');
        expect(safeHref('/ruta')?.kind).toBe('internal');
        expect(safeHref('#ancla')?.kind).toBe('anchor');
        expect(safeHref('ftp://example.com')).toBeNull();
        expect(safeHref('')).toBeNull();
        expect(safeHref(42)).toBeNull();
        expect(safeHref(`https://example.com/${'a'.repeat(2048)}`)).toBeNull();
    });
});
