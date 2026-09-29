<?php

use App\Domain\Content\Diff\TextDiff;

function diffTypes(array $rows): array
{
    return array_map(
        fn (array $row) => $row['type'] === 'skipped'
            ? "…{$row['count']}"
            : ['same' => ' ', 'added' => '+', 'removed' => '-'][$row['type']].implode('', array_column($row['segments'], 'text')),
        $rows,
    );
}

it('is empty when nothing changed', function () {
    expect(TextDiff::lines("a\nb\n", "a\nb\n"))->toBe([]);
});

it('marks added and removed lines with their line numbers', function () {
    $rows = TextDiff::lines("uno\ndos\ntres", "uno\ntres\ncuatro");

    expect(diffTypes($rows))->toBe([' uno', '-dos', ' tres', '+cuatro'])
        ->and($rows[1])->toMatchArray(['old' => 2, 'new' => null])
        ->and($rows[2])->toMatchArray(['old' => 3, 'new' => 2])
        ->and($rows[3])->toMatchArray(['old' => null, 'new' => 3]);
});

it('folds unchanged lines away from the changes', function () {
    $old = implode("\n", range(1, 20));
    $new = str_replace("\n10\n", "\ndiez\n", $old);

    expect(diffTypes(TextDiff::lines($old, $new, context: 2)))->toBe([
        '…7', ' 8', ' 9', '-10', '+diez', ' 11', ' 12', '…8',
    ]);
});

it('marks the changed words of a line edited in place', function () {
    $rows = TextDiff::lines('Git guarda la historia como commits.', 'Git guarda toda la historia como instantáneas.');

    expect($rows[0]['segments'])->toBe([
        ['text' => 'Git guarda la historia como ', 'changed' => false],
        ['text' => 'commits', 'changed' => true],
        ['text' => '.', 'changed' => false],
    ])->and($rows[1]['segments'])->toBe([
        ['text' => 'Git guarda ', 'changed' => false],
        ['text' => 'toda ', 'changed' => true],
        ['text' => 'la historia como ', 'changed' => false],
        ['text' => 'instantáneas', 'changed' => true],
        ['text' => '.', 'changed' => false],
    ]);
});

it('does not mark words when a line was replaced rather than edited', function () {
    $rows = TextDiff::lines('Una frase sobre ramas.', 'Otro texto distinto por completo.');

    expect($rows[0]['segments'])->toBe([['text' => 'Una frase sobre ramas.', 'changed' => false]])
        ->and($rows[1]['segments'])->toBe([['text' => 'Otro texto distinto por completo.', 'changed' => false]]);
});

it('diffs from and to an empty text', function () {
    expect(diffTypes(TextDiff::lines('', "a\nb")))->toBe(['+a', '+b'])
        ->and(diffTypes(TextDiff::lines("a\nb", '')))->toBe(['-a', '-b']);
});
