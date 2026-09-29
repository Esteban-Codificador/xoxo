<?php

use App\Domain\Content\RichContent\Markdown\MarkdownToRichContent;
use App\Domain\Content\RichContent\Markdown\RichContentToMarkdown;
use App\Domain\Content\RichContent\RichContent;

/*
| The serializer writes the dialect the importer reads, so Markdown out and
| back in must give the same document. That is what makes the text diff of
| versions trustworthy and content:export (TD-4) possible.
*/

function toMarkdown(array $content): string
{
    return (new RichContentToMarkdown)->convert(RichContent::fromDocument(['type' => 'doc', 'content' => $content]));
}

/**
 * Drops what the editor adds and the importer never writes (null link
 * attributes, orderedList type, default cell spans) and sorts marks, which
 * carry no order.
 */
function comparableDocument(mixed $value): mixed
{
    if (! is_array($value)) {
        return $value;
    }

    if (isset($value['marks']) && is_array($value['marks'])) {
        usort($value['marks'], fn (array $a, array $b) => strcmp($a['type'], $b['type']));
    }

    if (in_array($value['type'] ?? null, ['tableHeader', 'tableCell'], true)
        && ($value['attrs']['colspan'] ?? 1) === 1 && ($value['attrs']['rowspan'] ?? 1) === 1) {
        unset($value['attrs']);
    }

    $value = array_filter($value, fn (mixed $item) => $item !== null);

    return array_map(comparableDocument(...), $value);
}

function roundTrip(array $doc): array
{
    $markdown = (new RichContentToMarkdown)->convert(RichContent::fromDocument($doc));

    return (new MarkdownToRichContent)->convert($markdown)->doc;
}

it('round-trips the fixture with every node and mark', function () {
    $fixture = json_decode(
        (string) file_get_contents(__DIR__.'/../../../resources/js/features/rich-content/every-node.fixture.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect(comparableDocument(roundTrip($fixture['doc'])))->toEqual(comparableDocument($fixture['doc']));
});

it('round-trips every Markdown body of the content package', function () {
    $files = new RegexIterator(
        new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../../../content')),
        '/\.md$/',
    );
    $checked = 0;

    foreach ($files as $file) {
        $body = (string) preg_replace('/\A---\n.*?\n---\n/s', '', (string) file_get_contents((string) $file));
        $doc = (new MarkdownToRichContent)->convert($body)->doc;

        expect(roundTrip($doc))->toEqual($doc, (string) $file);
        $checked++;
    }

    expect($checked)->toBeGreaterThan(10);
});

it('writes the authoring dialect of the content package', function () {
    expect(toMarkdown([
        ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Concepto']]],
        ['type' => 'callout', 'attrs' => ['variant' => 'tip'], 'content' => [
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Haz commits pequeños.']]],
        ]],
        ['type' => 'video', 'attrs' => ['provider' => 'youtube', 'videoId' => 'aircAruvnKk']],
        ['type' => 'orderedList', 'attrs' => ['start' => 2], 'content' => [
            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Dos']]]]],
            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Tres']]]]],
        ]],
    ]))->toBe(<<<'MD'
        ## Concepto

        > [!TIP]
        > Haz commits pequeños.

        ```video
        provider: youtube
        id: aircAruvnKk
        ```

        2. Dos
        3. Tres

        MD);
});

it('escapes text that would otherwise read as Markdown', function () {
    $content = [
        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => '# no es un título, *ni* esto [un enlace] ni $x$']]],
        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => '1. no es una lista']]],
        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => '- tampoco esta']]],
        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'código con `comillas`', 'marks' => [['type' => 'code']]]]],
    ];

    expect(roundTrip(['type' => 'doc', 'content' => $content]))->toEqual(['type' => 'doc', 'content' => $content]);
});

it('keeps overlapping marks and spaces at the edge of a run valid', function () {
    $content = [['type' => 'paragraph', 'content' => [
        ['type' => 'text', 'text' => 'negrita ', 'marks' => [['type' => 'bold']]],
        ['type' => 'text', 'text' => 'y cursiva', 'marks' => [['type' => 'bold'], ['type' => 'italic']]],
        ['type' => 'text', 'text' => ' fin'],
    ]]];

    expect(toMarkdown($content))->toBe("**negrita *y cursiva*** fin\n")
        ->and(roundTrip(['type' => 'doc', 'content' => $content]))->toEqual(['type' => 'doc', 'content' => $content]);
});

it('serializes an empty document as an empty string', function () {
    expect((new RichContentToMarkdown)->convert(RichContent::empty()))->toBe('');
});
