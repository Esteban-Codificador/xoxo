<?php

namespace App\Domain\Content\RichContent\Markdown;

use App\Domain\Content\RichContent\RichContent;

/**
 * Serializes RichContent back to the authoring Markdown of the content
 * package (the dialect MarkdownToRichContent reads: GitHub alerts for
 * callouts, fenced `math`, `mermaid` and `video` blocks, `$…$` for inline
 * math). Used to diff versions as text, and the base of content:export.
 * GFM tables have no merged cells or column widths: those are dropped.
 */
final class RichContentToMarkdown
{
    /** Outer to inner: a link wraps emphasis, never the other way round. */
    private const array MARK_ORDER = ['link', 'bold', 'italic', 'strike'];

    private const array CALLOUTS = ['note' => 'NOTE', 'tip' => 'TIP', 'important' => 'IMPORTANT', 'warning' => 'WARNING', 'caution' => 'CAUTION'];

    public function convert(RichContent $content): string
    {
        $markdown = $this->blocks($content->doc['content'] ?? []);

        return $markdown === '' ? '' : $markdown."\n";
    }

    /**
     * @param  array<mixed>  $nodes
     */
    private function blocks(array $nodes): string
    {
        $blocks = [];

        foreach ($nodes as $node) {
            if (is_array($node)) {
                $block = $this->block($node);

                if ($block !== '') {
                    $blocks[] = $block;
                }
            }
        }

        return implode("\n\n", $blocks);
    }

    /**
     * @param  array<mixed>  $node
     */
    private function block(array $node): string
    {
        $attrs = is_array($node['attrs'] ?? null) ? $node['attrs'] : [];
        $content = is_array($node['content'] ?? null) ? $node['content'] : [];

        return match ($node['type'] ?? null) {
            'paragraph' => $this->escapeLineStart($this->inline($content)),
            'heading' => str_repeat('#', (int) ($attrs['level'] ?? 2)).' '.$this->inline($content),
            'bulletList' => $this->list($content, null),
            'orderedList' => $this->list($content, (int) ($attrs['start'] ?? 1)),
            'blockquote' => $this->quote($this->blocks($content)),
            'callout' => $this->quote('[!'.(self::CALLOUTS[$attrs['variant'] ?? 'note'] ?? 'NOTE')."]\n".$this->blocks($content)),
            'codeBlock' => $this->fence(is_string($attrs['language'] ?? null) ? $attrs['language'] : '', $this->plain($content)),
            'blockMath' => $this->fence('math', (string) ($attrs['latex'] ?? '')),
            'diagram' => $this->fence('mermaid', (string) ($attrs['source'] ?? '')),
            'video' => $this->fence('video', 'provider: '.($attrs['provider'] ?? 'youtube')."\nid: ".($attrs['videoId'] ?? '')),
            'horizontalRule' => '---',
            'table' => $this->table($content),
            default => '',
        };
    }

    /**
     * @param  array<mixed>  $items
     */
    private function list(array $items, ?int $start): string
    {
        $lines = [];
        $number = $start ?? 1;
        // Tight when every item is a single paragraph, as authors write them.
        $tight = array_reduce($items, fn (bool $carry, mixed $item) => $carry
            && is_array($item) && count($item['content'] ?? []) <= 1, true);

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $marker = $start === null ? '- ' : ($number++).'. ';
            $body = $this->blocks(is_array($item['content'] ?? null) ? $item['content'] : []);
            $indent = str_repeat(' ', strlen($marker));
            $text = $marker.str_replace("\n", "\n".$indent, $body);
            // Indented blank lines become truly blank.
            $lines[] = (string) preg_replace('/^ +$/m', '', $text);
        }

        return implode($tight ? "\n" : "\n\n", $lines);
    }

    private function quote(string $body): string
    {
        return implode("\n", array_map(
            fn (string $line) => $line === '' ? '>' : '> '.$line,
            explode("\n", $body),
        ));
    }

    private function fence(string $info, string $body): string
    {
        $fence = '```';

        while (str_contains($body, $fence)) {
            $fence .= '`';
        }

        return $fence.$info."\n".rtrim($body, "\n")."\n".$fence;
    }

    /**
     * @param  array<mixed>  $rows
     */
    private function table(array $rows): string
    {
        $lines = [];

        foreach (array_values($rows) as $index => $row) {
            $cells = [];

            foreach (is_array($row) ? ($row['content'] ?? []) : [] as $cell) {
                $paragraphs = array_map(
                    fn (mixed $block) => is_array($block) ? $this->inline(is_array($block['content'] ?? null) ? $block['content'] : []) : '',
                    is_array($cell) ? ($cell['content'] ?? []) : [],
                );
                $cells[] = str_replace('|', '\|', implode(' ', array_filter($paragraphs, fn (string $text) => $text !== '')));
            }

            $lines[] = '| '.implode(' | ', $cells).' |';

            // GFM tables always have a header row: the first one plays it.
            if ($index === 0) {
                $lines[] = '|'.str_repeat(' --- |', max(count($cells), 1));
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Inline nodes with their marks opened and closed as runs, so that
     * "**bold *and italic***" or a link over several nodes stay valid.
     *
     * @param  array<mixed>  $nodes
     */
    private function inline(array $nodes): string
    {
        $out = '';
        /** @var list<array{type: string, href: string|null}> $open */
        $open = [];
        $pending = '';

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            $marks = $this->marksOf($node);
            $keep = 0;

            while ($keep < count($open) && $keep < count($marks) && $open[$keep] === $marks[$keep]) {
                $keep++;
            }

            for ($i = count($open) - 1; $i >= $keep; $i--) {
                $out .= $this->close($open[$i]);
            }

            // Whitespace that ended the previous run goes outside its marks.
            $out .= $pending;
            $pending = '';
            $open = array_slice($open, 0, $keep);
            [$lead, $text, $trail] = $this->splitSpaces($this->inlineNode($node));

            if ($keep < count($marks)) {
                $out .= $lead;
                $lead = '';

                for ($i = $keep; $i < count($marks); $i++) {
                    $out .= $this->open($marks[$i]);
                    $open[] = $marks[$i];
                }
            }

            $out .= $lead.$text;
            $pending = $trail;
        }

        for ($i = count($open) - 1; $i >= 0; $i--) {
            $out .= $this->close($open[$i]);
        }

        return $out.$pending;
    }

    /**
     * @param  array<mixed>  $node
     * @return list<array{type: string, href: string|null}>
     */
    private function marksOf(array $node): array
    {
        $marks = [];

        foreach (is_array($node['marks'] ?? null) ? $node['marks'] : [] as $mark) {
            $type = is_array($mark) ? ($mark['type'] ?? null) : null;

            if (is_string($type) && in_array($type, self::MARK_ORDER, true)) {
                $href = $type === 'link' && is_array($mark['attrs'] ?? null) ? (string) ($mark['attrs']['href'] ?? '') : null;
                $marks[array_search($type, self::MARK_ORDER, true)] = ['type' => $type, 'href' => $href];
            }
        }

        ksort($marks);

        return array_values($marks);
    }

    /**
     * @param  array<mixed>  $node
     */
    private function inlineNode(array $node): string
    {
        return match ($node['type'] ?? null) {
            'text' => $this->hasMark($node, 'code')
                ? $this->code((string) ($node['text'] ?? ''))
                : $this->escape((string) ($node['text'] ?? '')),
            'hardBreak' => "\\\n",
            'inlineMath' => '$'.(is_array($node['attrs'] ?? null) ? (string) ($node['attrs']['latex'] ?? '') : '').'$',
            default => '',
        };
    }

    /**
     * @param  array{type: string, href: string|null}  $mark
     */
    private function open(array $mark): string
    {
        return match ($mark['type']) {
            'link' => '[',
            'bold' => '**',
            'italic' => '*',
            default => '~~',
        };
    }

    /**
     * @param  array{type: string, href: string|null}  $mark
     */
    private function close(array $mark): string
    {
        return match ($mark['type']) {
            'link' => ']('.str_replace([' ', ')'], ['%20', '%29'], (string) $mark['href']).')',
            'bold' => '**',
            'italic' => '*',
            default => '~~',
        };
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function splitSpaces(string $text): array
    {
        if (preg_match('/^(\s*)(.*?)(\s*)$/s', $text, $parts) !== 1) {
            return ['', $text, ''];
        }

        return [$parts[1], $parts[2], $parts[3]];
    }

    /**
     * @param  array<mixed>  $node
     */
    private function hasMark(array $node, string $type): bool
    {
        foreach (is_array($node['marks'] ?? null) ? $node['marks'] : [] as $mark) {
            if (is_array($mark) && ($mark['type'] ?? null) === $type) {
                return true;
            }
        }

        return false;
    }

    private function code(string $text): string
    {
        $fence = '`';

        while (str_contains($text, $fence)) {
            $fence .= '`';
        }

        $padding = str_starts_with($text, '`') || str_ends_with($text, '`') ? ' ' : '';

        return $fence.$padding.$text.$padding.$fence;
    }

    /**
     * @param  array<mixed>  $nodes
     */
    private function plain(array $nodes): string
    {
        return implode('', array_map(fn (mixed $node) => is_array($node) ? (string) ($node['text'] ?? '') : '', $nodes));
    }

    /** Characters that would start Markdown syntax inside a text run. */
    private function escape(string $text): string
    {
        return (string) preg_replace('/([\\\\`*_\[\]$~<])/', '\\\\$1', $text);
    }

    /** A paragraph must not read as a heading, quote, list or rule. */
    private function escapeLineStart(string $text): string
    {
        if (preg_match('/^(\d+)([.)])(\s)/', $text, $match) === 1) {
            return $match[1].'\\'.$match[2].substr($text, strlen($match[0]) - 1);
        }

        return preg_match('/^([#>+\-=])/', $text) === 1 ? '\\'.$text : $text;
    }
}
