<?php

namespace App\Domain\Content\Diff;

/**
 * Line diff for reviewing content changes, with the changed words marked
 * inside lines that were edited rather than replaced. Paragraphs serialize
 * to one line each, so word marks are what make a one-word fix visible.
 *
 * Rows are either a line (same, added or removed) or a run of unchanged
 * lines folded away from the changes.
 *
 * @phpstan-type Segment array{text: string, changed: bool}
 * @phpstan-type Row array{type: 'same'|'added'|'removed', old: int|null, new: int|null, segments: list<Segment>}|array{type: 'skipped', count: int}
 */
final class TextDiff
{
    /**
     * Past this many LCS cells (a thousand changed lines against a thousand)
     * the block reads as "all removed, all added": memory stays bounded.
     */
    private const int MAX_CELLS = 1_000_000;

    /** Below this share of shared words, a line reads as replaced, not edited. */
    private const float MIN_SHARED_WORDS = 0.4;

    /**
     * Empty when both texts are equal.
     *
     * @return list<Row>
     */
    public static function lines(string $old, string $new, int $context = 3): array
    {
        if ($old === $new) {
            return [];
        }

        return self::fold(self::rows(self::split($old), self::split($new)), $context);
    }

    /**
     * Edit script turning $old into $new, removals before additions inside
     * each changed block.
     *
     * @param  list<string>  $old
     * @param  list<string>  $new
     * @return list<array{0: 'same'|'added'|'removed', 1: string}>
     */
    public static function sequence(array $old, array $new): array
    {
        $prefix = 0;
        $max = min(count($old), count($new));

        while ($prefix < $max && $old[$prefix] === $new[$prefix]) {
            $prefix++;
        }

        $suffix = 0;

        while ($suffix < $max - $prefix && $old[count($old) - 1 - $suffix] === $new[count($new) - 1 - $suffix]) {
            $suffix++;
        }

        $a = array_slice($old, $prefix, count($old) - $prefix - $suffix);
        $b = array_slice($new, $prefix, count($new) - $prefix - $suffix);
        $ops = [];

        foreach (array_slice($old, 0, $prefix) as $item) {
            $ops[] = ['same', $item];
        }

        array_push($ops, ...self::middle($a, $b));

        foreach (array_slice($old, count($old) - $suffix) as $item) {
            $ops[] = ['same', $item];
        }

        return $ops;
    }

    /**
     * @param  list<string>  $a
     * @param  list<string>  $b
     * @return list<array{0: 'same'|'added'|'removed', 1: string}>
     */
    private static function middle(array $a, array $b): array
    {
        $n = count($a);
        $m = count($b);

        if ($n * $m > self::MAX_CELLS) {
            return [
                ...array_map(fn (string $item) => ['removed', $item], $a),
                ...array_map(fn (string $item) => ['added', $item], $b),
            ];
        }

        // $lcs[$i][$j]: longest common subsequence of $a[$i..] and $b[$j..].
        $lcs = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));

        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $lcs[$i][$j] = $a[$i] === $b[$j]
                    ? $lcs[$i + 1][$j + 1] + 1
                    : max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
            }
        }

        $ops = [];
        $removed = [];
        $added = [];
        $i = 0;
        $j = 0;

        while ($i < $n || $j < $m) {
            if ($i < $n && $j < $m && $a[$i] === $b[$j]) {
                array_push($ops, ...$removed, ...$added);
                $removed = $added = [];
                $ops[] = ['same', $a[$i]];
                $i++;
                $j++;
            } elseif ($j >= $m || ($i < $n && $lcs[$i + 1][$j] >= $lcs[$i][$j + 1])) {
                $removed[] = ['removed', $a[$i++]];
            } else {
                $added[] = ['added', $b[$j++]];
            }
        }

        array_push($ops, ...$removed, ...$added);

        return $ops;
    }

    /**
     * @param  list<string>  $old
     * @param  list<string>  $new
     * @return list<Row>
     */
    private static function rows(array $old, array $new): array
    {
        $rows = [];
        $lines = ['old' => 1, 'new' => 1];
        $removed = [];
        $added = [];

        foreach (self::sequence($old, $new) as [$type, $text]) {
            if ($type === 'removed') {
                $removed[] = $text;
            } elseif ($type === 'added') {
                $added[] = $text;
            } else {
                array_push($rows, ...self::changeBlock($removed, $added, $lines));
                $removed = $added = [];
                $rows[] = ['type' => 'same', 'old' => $lines['old']++, 'new' => $lines['new']++, 'segments' => [self::segment($text, false)]];
            }
        }

        array_push($rows, ...self::changeBlock($removed, $added, $lines));

        return $rows;
    }

    /**
     * Rows of one block of removed and added lines. Lines are paired in
     * order to mark the words each edit changed.
     *
     * @param  list<string>  $removed
     * @param  list<string>  $added
     * @param  array{old: int, new: int}  $lines  Next line numbers, advanced here.
     * @return list<Row>
     */
    private static function changeBlock(array $removed, array $added, array &$lines): array
    {
        $rows = [];
        $marks = [];

        for ($k = 0; $k < min(count($removed), count($added)); $k++) {
            $marks[$k] = self::words($removed[$k], $added[$k]);
        }

        foreach ($removed as $k => $text) {
            $rows[] = ['type' => 'removed', 'old' => $lines['old']++, 'new' => null, 'segments' => $marks[$k][0] ?? [self::segment($text, false)]];
        }

        foreach ($added as $k => $text) {
            $rows[] = ['type' => 'added', 'old' => null, 'new' => $lines['new']++, 'segments' => $marks[$k][1] ?? [self::segment($text, false)]];
        }

        return $rows;
    }

    /**
     * Word marks for a line edited in place, or null when the two lines
     * share too little to read as an edit.
     *
     * @return array{0: list<Segment>, 1: list<Segment>}|null
     */
    private static function words(string $before, string $after): ?array
    {
        $a = self::tokens($before);
        $b = self::tokens($after);
        $ops = self::sequence($a, $b);
        $shared = 0;

        foreach ($ops as [$type, $token]) {
            if ($type === 'same' && trim($token) !== '') {
                $shared++;
            }
        }

        $words = max(count(array_filter($a, fn (string $token) => trim($token) !== '')), count(array_filter($b, fn (string $token) => trim($token) !== '')), 1);

        if ($shared / $words < self::MIN_SHARED_WORDS) {
            return null;
        }

        $old = [];
        $new = [];

        foreach ($ops as [$type, $token]) {
            if ($type !== 'added') {
                self::append($old, $token, $type === 'removed');
            }

            if ($type !== 'removed') {
                self::append($new, $token, $type === 'added');
            }
        }

        return [$old, $new];
    }

    /**
     * @param  list<Segment>  $segments
     */
    private static function append(array &$segments, string $text, bool $changed): void
    {
        $last = array_key_last($segments);

        // A space between two changed words belongs to the change.
        if ($last !== null && $segments[$last]['changed'] === $changed) {
            $segments[$last]['text'] .= $text;

            return;
        }

        $segments[] = self::segment($text, $changed);
    }

    /**
     * @return Segment
     */
    private static function segment(string $text, bool $changed): array
    {
        return ['text' => $text, 'changed' => $changed];
    }

    /**
     * Words, runs of spaces and single punctuation marks.
     *
     * @return list<string>
     */
    private static function tokens(string $line): array
    {
        return preg_split('/(\s+|[^\p{L}\p{N}\s])/u', $line, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * @return list<string>
     */
    private static function split(string $text): array
    {
        $text = rtrim(str_replace("\r\n", "\n", $text), "\n");

        return $text === '' ? [] : explode("\n", $text);
    }

    /**
     * Keeps $context unchanged lines around each change and folds the rest.
     *
     * @param  list<Row>  $rows
     * @return list<Row>
     */
    private static function fold(array $rows, int $context): array
    {
        $keep = [];

        foreach ($rows as $index => $row) {
            if ($row['type'] !== 'same') {
                for ($k = max(0, $index - $context); $k <= min(count($rows) - 1, $index + $context); $k++) {
                    $keep[$k] = true;
                }
            }
        }

        $folded = [];
        $skipped = 0;

        foreach ($rows as $index => $row) {
            if (isset($keep[$index])) {
                if ($skipped > 0) {
                    $folded[] = ['type' => 'skipped', 'count' => $skipped];
                    $skipped = 0;
                }

                $folded[] = $row;
            } else {
                $skipped++;
            }
        }

        if ($skipped > 0) {
            $folded[] = ['type' => 'skipped', 'count' => $skipped];
        }

        return $folded;
    }
}
