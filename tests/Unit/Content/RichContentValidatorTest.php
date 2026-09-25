<?php

use App\Domain\Content\RichContent\InvalidRichContent;
use App\Domain\Content\RichContent\RichContent;
use App\Domain\Content\RichContent\RichContentValidator;

/**
 * @param  list<array<string, mixed>>  $content
 * @return array{version: int, doc: array<string, mixed>}
 */
function envelope(array $content): array
{
    return ['version' => 1, 'doc' => ['type' => 'doc', 'content' => $content]];
}

function paragraph(string $text, array $marks = []): array
{
    $node = ['type' => 'text', 'text' => $text];

    if ($marks !== []) {
        $node['marks'] = $marks;
    }

    return ['type' => 'paragraph', 'content' => [$node]];
}

it('accepts every node of the schema', function () {
    $document = envelope([
        ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Concepto']]],
        paragraph('Texto con', [['type' => 'bold'], ['type' => 'link', 'attrs' => ['href' => 'https://git-scm.com/docs', 'target' => '_blank', 'rel' => 'noopener', 'class' => null]]]),
        ['type' => 'paragraph', 'content' => [
            ['type' => 'text', 'text' => 'Fórmula '],
            ['type' => 'inlineMath', 'attrs' => ['latex' => 'a \cdot b']],
            ['type' => 'hardBreak'],
        ]],
        ['type' => 'bulletList', 'content' => [['type' => 'listItem', 'content' => [paragraph('uno')]]]],
        ['type' => 'orderedList', 'attrs' => ['start' => 3, 'type' => null], 'content' => [['type' => 'listItem', 'content' => [paragraph('tres')]]]],
        ['type' => 'blockquote', 'content' => [paragraph('cita')]],
        ['type' => 'callout', 'attrs' => ['variant' => 'warning'], 'content' => [paragraph('cuidado')]],
        ['type' => 'codeBlock', 'attrs' => ['language' => 'python'], 'content' => [['type' => 'text', 'text' => 'print(1)']]],
        ['type' => 'horizontalRule'],
        ['type' => 'table', 'content' => [['type' => 'tableRow', 'content' => [
            ['type' => 'tableHeader', 'attrs' => ['colspan' => 1, 'rowspan' => 1, 'colwidth' => null], 'content' => [paragraph('A')]],
            ['type' => 'tableCell', 'content' => [paragraph('B')]],
        ]]]],
        ['type' => 'blockMath', 'attrs' => ['latex' => '\sum_i x_i']],
        ['type' => 'diagram', 'attrs' => ['kind' => 'mermaid', 'source' => 'flowchart LR; A-->B']],
        ['type' => 'video', 'attrs' => ['provider' => 'youtube', 'videoId' => 'aircAruvnKk']],
    ]);

    expect((new RichContentValidator)->errors($document))->toBe([]);
});

it('rejects unknown nodes, marks and attributes', function (array $content, string $expected) {
    expect(implode("\n", (new RichContentValidator)->errors(envelope($content))))->toContain($expected);
})->with([
    'unknown node' => [[['type' => 'iframe']], '"iframe" no puede ir dentro de "doc"'],
    'unknown nested node' => [[['type' => 'blockquote', 'content' => [['type' => 'iframe']]]], '"iframe" no puede ir dentro de "blockquote"'],
    'unknown mark' => [[paragraph('x', [['type' => 'underline']])], 'marca no permitida'],
    'h1' => [[['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'x']]]], 'level: debe ser un entero entre 2 y 4'],
    'unknown attribute' => [[['type' => 'paragraph', 'attrs' => ['style' => 'color:red']]], 'style: atributo no permitido'],
    'javascript link' => [[paragraph('x', [['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']]])], 'esquema de enlace no permitido'],
    'protocol relative link' => [[paragraph('x', [['type' => 'link', 'attrs' => ['href' => '//evil.test']]])], 'esquema de enlace no permitido'],
    'bad video id' => [[['type' => 'video', 'attrs' => ['provider' => 'youtube', 'videoId' => '"><script>']]], 'videoId: formato inválido'],
    'unknown video provider' => [[['type' => 'video', 'attrs' => ['provider' => 'vimeo', 'videoId' => 'aircAruvnKk']]], 'provider: debe ser uno de: youtube'],
    'block inside paragraph' => [[['type' => 'paragraph', 'content' => [paragraph('x')]]], '"paragraph" no puede ir dentro de "paragraph"'],
    'marks inside code block' => [[['type' => 'codeBlock', 'content' => [['type' => 'text', 'text' => 'x', 'marks' => [['type' => 'bold']]]]]], 'no admite marcas'],
    'empty text' => [[['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => '']]]], 'necesita "text" no vacío'],
    'missing required attribute' => [[['type' => 'callout', 'content' => [paragraph('x')]]], 'variant: atributo obligatorio'],
    'content on leaf node' => [[['type' => 'horizontalRule', 'content' => [paragraph('x')]]], 'no admite contenido'],
]);

it('rejects a wrong envelope', function () {
    $validator = new RichContentValidator;

    expect($validator->errors(['version' => 2, 'doc' => ['type' => 'doc']]))->toContain('version: se esperaba 1.')
        ->and($validator->errors(['version' => 1, 'doc' => ['type' => 'paragraph']]))->toContain('doc: el nodo raíz debe ser de tipo "doc".')
        ->and($validator->errors('<p>html</p>'))->toBe(['El contenido debe ser un objeto {version, doc}.']);
});

it('rejects documents deeper than the limit', function () {
    $node = paragraph('fondo');

    for ($i = 0; $i < 13; $i++) {
        $node = ['type' => 'blockquote', 'content' => [$node]];
    }

    expect(implode("\n", (new RichContentValidator)->errors(envelope([$node]))))->toContain('profundidad máxima');
});

it('only builds valid instances', function () {
    RichContent::fromArray(envelope([['type' => 'script']]));
})->throws(InvalidRichContent::class);

it('extracts plain text without diagrams or videos', function () {
    $content = RichContent::fromArray(envelope([
        ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Concepto']]],
        paragraph('Un commit es una instantánea.'),
        ['type' => 'diagram', 'attrs' => ['kind' => 'mermaid', 'source' => 'flowchart LR; A-->B']],
        ['type' => 'bulletList', 'content' => [
            ['type' => 'listItem', 'content' => [paragraph('uno')]],
            ['type' => 'listItem', 'content' => [paragraph('dos')]],
        ]],
    ]));

    expect($content->plainText())->toBe("Concepto\n\nUn commit es una instantánea.\n\nuno\ndos");
});

it('hashes independently of key order', function () {
    $a = RichContent::fromArray(envelope([['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'X']]]]));
    $b = RichContent::fromArray(['doc' => ['content' => [['content' => [['text' => 'X', 'type' => 'text']], 'attrs' => ['level' => 2], 'type' => 'heading']], 'type' => 'doc'], 'version' => 1]);

    expect($a->hash())->toBe($b->hash())->toHaveLength(64);
});

it('lists headings of a level', function () {
    $content = RichContent::fromArray(envelope([
        ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Concepto']]],
        ['type' => 'heading', 'attrs' => ['level' => 3], 'content' => [['type' => 'text', 'text' => 'Detalle']]],
        ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Práctica']]],
    ]));

    expect($content->headings(2))->toBe(['Concepto', 'Práctica']);
});
