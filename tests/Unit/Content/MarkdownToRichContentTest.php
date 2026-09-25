<?php

use App\Domain\Content\RichContent\InvalidRichContent;
use App\Domain\Content\RichContent\Markdown\MarkdownToRichContent;

/**
 * @return list<array<string, mixed>>
 */
function convertMarkdown(string $markdown): array
{
    return (new MarkdownToRichContent)->convert($markdown)->doc['content'];
}

function conversionErrors(string $markdown): string
{
    try {
        (new MarkdownToRichContent)->convert($markdown);
    } catch (InvalidRichContent $exception) {
        return implode("\n", $exception->errors);
    }

    return '';
}

it('converts headings and paragraphs with marks', function () {
    $blocks = convertMarkdown("## Concepto\n\nTexto **fuerte**, *énfasis*, ~~tachado~~, `code` y [docs](https://git-scm.com/docs).");

    expect($blocks[0])->toBe(['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Concepto']]])
        ->and($blocks[1]['content'])->toBe([
            ['type' => 'text', 'text' => 'Texto '],
            ['type' => 'text', 'text' => 'fuerte', 'marks' => [['type' => 'bold']]],
            ['type' => 'text', 'text' => ', '],
            ['type' => 'text', 'text' => 'énfasis', 'marks' => [['type' => 'italic']]],
            ['type' => 'text', 'text' => ', '],
            ['type' => 'text', 'text' => 'tachado', 'marks' => [['type' => 'strike']]],
            ['type' => 'text', 'text' => ', '],
            ['type' => 'text', 'text' => 'code', 'marks' => [['type' => 'code']]],
            ['type' => 'text', 'text' => ' y '],
            ['type' => 'text', 'text' => 'docs', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'https://git-scm.com/docs']]]],
            ['type' => 'text', 'text' => '.'],
        ]);
});

it('joins soft line breaks into spaces and keeps hard breaks', function () {
    $blocks = convertMarkdown("una\nlínea  \notra");

    expect($blocks[0]['content'])->toBe([
        ['type' => 'text', 'text' => 'una línea'],
        ['type' => 'hardBreak'],
        ['type' => 'text', 'text' => 'otra'],
    ]);
});

it('parses inline math but leaves prices as text', function () {
    $blocks = convertMarkdown('El producto $a \cdot b$ cuesta entre $5 y $10.');

    expect($blocks[0]['content'])->toBe([
        ['type' => 'text', 'text' => 'El producto '],
        ['type' => 'inlineMath', 'attrs' => ['latex' => 'a \cdot b']],
        ['type' => 'text', 'text' => ' cuesta entre $5 y $10.'],
    ]);
});

it('turns GitHub alerts into callouts and keeps plain quotes', function () {
    $blocks = convertMarkdown("> [!WARNING]\n> Rota la clave.\n\n> Una cita normal.");

    expect($blocks[0])->toBe([
        'type' => 'callout',
        'attrs' => ['variant' => 'warning'],
        'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Rota la clave.']]]],
    ])->and($blocks[1]['type'])->toBe('blockquote');
});

it('converts fenced blocks by their info string', function () {
    $blocks = convertMarkdown(<<<'MD'
    ```python
    print("hola")
    ```

    ```
    sin lenguaje
    ```

    ```mermaid
    flowchart LR
        A --> B
    ```

    ```math
    \frac{1}{n}\sum_i x_i
    ```

    ```video
    provider: youtube
    id: aircAruvnKk
    ```
    MD);

    expect($blocks)->toBe([
        ['type' => 'codeBlock', 'attrs' => ['language' => 'python'], 'content' => [['type' => 'text', 'text' => 'print("hola")']]],
        ['type' => 'codeBlock', 'attrs' => ['language' => null], 'content' => [['type' => 'text', 'text' => 'sin lenguaje']]],
        ['type' => 'diagram', 'attrs' => ['kind' => 'mermaid', 'source' => "flowchart LR\n    A --> B"]],
        ['type' => 'blockMath', 'attrs' => ['latex' => '\frac{1}{n}\sum_i x_i']],
        ['type' => 'video', 'attrs' => ['provider' => 'youtube', 'videoId' => 'aircAruvnKk']],
    ]);
});

it('converts lists, including ordered lists with a custom start', function () {
    $blocks = convertMarkdown("- uno\n- dos\n\n3. tres\n4. cuatro");

    expect($blocks[0]['type'])->toBe('bulletList')
        ->and($blocks[0]['content'])->toHaveCount(2)
        ->and($blocks[0]['content'][1]['content'][0]['content'][0]['text'])->toBe('dos')
        ->and($blocks[1]['type'])->toBe('orderedList')
        ->and($blocks[1]['attrs'])->toBe(['start' => 3]);
});

it('converts tables with header and data cells', function () {
    $blocks = convertMarkdown("| Área | Comando |\n|---|---|\n| Staging | `git add` |");

    expect($blocks[0]['type'])->toBe('table')
        ->and($blocks[0]['content'][0]['content'][0]['type'])->toBe('tableHeader')
        ->and($blocks[0]['content'][1]['content'][1])->toBe([
            'type' => 'tableCell',
            'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'git add', 'marks' => [['type' => 'code']]]]]],
        ]);
});

it('rejects constructs outside the schema with the line number', function (string $markdown, string $expected) {
    expect(conversionErrors($markdown))->toContain($expected);
})->with([
    'h1' => ["# Título\n", 'línea 1: encabezado de nivel 1'],
    'raw html block' => ["Intro\n\n<div>hola</div>\n", 'línea 3: el HTML crudo no está permitido'],
    'inline html' => ['texto <span>x</span>', 'el HTML crudo no está permitido'],
    'image' => ['![diagrama](diagrama.png)', 'las imágenes se habilitan en la Fase 6'],
    'javascript link' => ['[clic](javascript:alert(1))', 'esquema de enlace no permitido'],
    'video without id' => ["```video\nprovider: youtube\n```", 'necesita las líneas "provider:" e "id:"'],
    'invalid video id' => ["```video\nprovider: youtube\nid: nope\n```", 'videoId: formato inválido'],
    'empty callout' => ["> [!TIP]\n", 'el callout está vacío'],
]);
