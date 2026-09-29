<?php

use App\Domain\Content\RichContent\RichContentSchema;
use App\Domain\Content\RichContent\RichContentValidator;

/*
| The TipTap editor and the server must accept exactly the same document
| shape (TD-5). allowlist.json is the bridge: this test pins it to
| RichContentSchema, and rich-content-editor.test.ts pins the editor's
| ProseMirror schema to it.
*/

function richContentJson(string $file): array
{
    return json_decode(
        (string) file_get_contents(__DIR__.'/../../../resources/js/features/rich-content/'.$file),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

function allowlist(): array
{
    return richContentJson('allowlist.json');
}

it('lists the same nodes and attributes as RichContentSchema', function () {
    $expected = [];
    foreach (array_keys(RichContentSchema::children()) as $node) {
        $expected[$node] = array_keys(RichContentSchema::attributes()[$node] ?? []);
    }

    expect(allowlist()['nodes'])->toEqualCanonicalizing($expected);

    foreach (allowlist()['nodes'] as $node => $attributes) {
        expect($attributes)->toEqualCanonicalizing($expected[$node]);
    }
});

it('lists the same marks and attributes as RichContentSchema', function () {
    $expected = [];
    foreach (RichContentSchema::MARKS as $mark) {
        $expected[$mark] = array_keys(RichContentSchema::attributes()[$mark] ?? []);
    }

    expect(allowlist()['marks'])->toEqual($expected);
});

it('accepts the fixture the editor round-trips', function () {
    $fixture = richContentJson('every-node.fixture.json');

    expect((new RichContentValidator)->errors($fixture))->toBe([]);

    // The fixture must exercise every node and mark of the allowlist.
    $types = [];
    $walk = function (array $node) use (&$walk, &$types): void {
        $types[] = $node['type'];
        foreach ($node['marks'] ?? [] as $mark) {
            $types[] = $mark['type'];
        }
        foreach ($node['content'] ?? [] as $child) {
            $walk($child);
        }
    };
    $walk($fixture['doc']);

    expect(array_values(array_unique($types)))->toEqualCanonicalizing([
        ...array_keys(allowlist()['nodes']),
        ...array_keys(allowlist()['marks']),
    ]);
});
