<?php

namespace App\Domain\Content\Package\Export;

use App\Domain\Content\Package\PackageMedia;
use App\Domain\Content\RichContent\InvalidRichContent;
use App\Domain\Content\RichContent\Markdown\InlineMathParser;
use App\Domain\Content\RichContent\Markdown\MarkdownToRichContent;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Parser\MarkdownParser;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Rewrites an existing package file with new content while keeping the
 * hand-written text of every field and every body block whose meaning did
 * not change, so that `git diff` after an export shows only the edit.
 *
 * It works on the text (top-level YAML keys, top-level Markdown blocks) and
 * never has to be right: the exporter checks that the merged file means
 * exactly what the canonical one does, and falls back to the canonical
 * file otherwise.
 */
final readonly class FileMerger
{
    private const string FRONT_MATTER = '/\A---\R(.*?)\R---[ \t]*(?:\R(.*))?\z/s';

    private const string TOP_LEVEL_KEY = '/^([A-Za-z_][A-Za-z0-9_]*):(?:\s|$)/';

    private MarkdownParser $parser;

    public function __construct(private ExportFormat $format, private MarkdownToRichContent $markdown)
    {
        // Same block grammar as the importer: only line ranges are needed.
        $environment = new Environment;
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new TableExtension);
        $environment->addExtension(new StrikethroughExtension);
        $environment->addExtension(new AutolinkExtension);
        $environment->addInlineParser(new InlineMathParser, 100);

        $this->parser = new MarkdownParser($environment);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function markdownFile(string $existing, array $data, string $body): ?string
    {
        if (preg_match(self::FRONT_MATTER, $existing, $match) !== 1) {
            return null;
        }

        $yaml = $this->mapping($match[1], $data);
        $oldBody = $match[2] ?? '';

        if ($yaml === null) {
            return null;
        }

        if ($this->format->sameDocument($oldBody, $body) && trim($oldBody) !== '') {
            return "---\n{$yaml}\n---\n{$oldBody}";
        }

        if (trim($body) === '') {
            return "---\n{$yaml}\n---\n";
        }

        $lead = trim($oldBody) === '' || str_starts_with($oldBody, "\n") ? "\n" : '';

        return "---\n{$yaml}\n---\n{$lead}".$this->body($oldBody, $body);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function roadmapFile(string $existing, array $data, ?string $description): ?string
    {
        $yaml = $this->mapping(rtrim($existing, "\n"), [...$data, 'description' => $description], markdownKeys: ['description']);

        return $yaml === null ? null : "{$yaml}\n";
    }

    /**
     * A YAML list of resources or videos: each item keeps its text, or its
     * unchanged fields when it changed; new items are appended in the given
     * order.
     *
     * @param  list<array<string, mixed>>  $items
     */
    public function listFile(string $existing, array $items): string
    {
        $old = [];
        $prefix = [];
        $current = null;

        foreach (explode("\n", rtrim($existing, "\n")) as $line) {
            if (str_starts_with($line, '- ')) {
                $current = count($old);
                $old[$current] = [substr($line, 2)];
            } elseif ($current === null) {
                $prefix[] = $line;
            } else {
                // Item fields are indented two spaces under the dash.
                $old[$current][] = str_starts_with($line, '  ') ? substr($line, 2) : $line;
            }
        }

        $byKey = [];

        foreach ($old as $lines) {
            $text = implode("\n", $lines);
            $key = $this->parse($text)['key'] ?? null;

            if (is_string($key)) {
                $byKey[$key] = $text;
            }
        }

        $out = $prefix;

        foreach ($items as $item) {
            $key = (string) ($item['key'] ?? '');
            $mapping = isset($byKey[$key]) ? $this->mapping($byKey[$key], $item) : null;
            $lines = explode("\n", $mapping ?? rtrim($this->format->listFile([$item]), "\n"));

            if ($mapping !== null) {
                $lines = array_map(fn (string $line, int $index) => ($index === 0 ? '- ' : ($line === '' ? '' : '  ')).$line, $lines, array_keys($lines));
            }

            array_push($out, ...$lines);
        }

        return implode("\n", $out)."\n";
    }

    /**
     * A YAML mapping with the new values: unchanged keys keep their text
     * and place, changed ones are rewritten, new ones go after the key that
     * precedes them in $data.
     *
     * @param  array<string, mixed>  $data
     * @param  list<string>  $markdownKeys
     */
    private function mapping(string $yaml, array $data, array $markdownKeys = []): ?string
    {
        $data = array_filter($data, fn (mixed $value) => $value !== null);
        // Top-level blocks: the key (null for text before the first one) and its lines.
        $keys = [];
        $texts = [];

        foreach (explode("\n", $yaml) as $line) {
            if (preg_match(self::TOP_LEVEL_KEY, $line, $match) === 1) {
                $keys[] = $match[1];
                $texts[] = $line;
            } elseif ($texts === []) {
                $keys[] = null;
                $texts[] = $line;
            } else {
                $texts[count($texts) - 1] .= "\n{$line}";
            }
        }

        $out = [];

        foreach ($keys as $index => $key) {
            $text = $texts[$index];

            if ($key === null) {
                $out[] = [null, $text];

                continue;
            }

            if (! array_key_exists($key, $data)) {
                continue;
            }

            $parsed = $this->parse($text);

            if ($parsed === null) {
                return null;
            }

            $markdown = in_array($key, $markdownKeys, true);

            $out[] = [$key, $this->format->sameField($key, $parsed[$key] ?? null, $data[$key], $markdown)
                ? $text
                : $this->format->field($key, $markdown ? $this->markdownField($parsed[$key] ?? null, $data[$key]) : $data[$key])];
        }

        $order = array_keys($data);

        foreach ($order as $index => $key) {
            if (in_array($key, array_column($out, 0), true)) {
                continue;
            }

            // After the closest preceding key already written.
            $at = 0;
            for ($previous = $index - 1; $previous >= 0; $previous--) {
                $position = array_search($order[$previous], array_column($out, 0), true);

                if ($position !== false) {
                    $at = $position + 1;
                    break;
                }
            }

            array_splice($out, $at, 0, [[$key, $this->format->field($key, $data[$key])]]);
        }

        return implode("\n", array_column($out, 1));
    }

    /**
     * A Markdown field that changed keeps the old text of its unchanged
     * blocks, like a lesson body: one edited paragraph is one changed
     * paragraph, not the whole field rewrapped.
     */
    private function markdownField(mixed $old, mixed $new): mixed
    {
        if (! is_string($old) || ! is_string($new)) {
            return $new;
        }

        $merged = $this->body($old, $new);

        return str_ends_with($new, "\n") ? $merged : rtrim($merged, "\n");
    }

    /**
     * @return array<mixed>|null
     */
    private function parse(string $yaml): ?array
    {
        try {
            $value = Yaml::parse($yaml, Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE | Yaml::PARSE_DATETIME);
        } catch (ParseException) {
            return null;
        }

        return is_array($value) ? $value : null;
    }

    /**
     * The new body with the old text of every top-level block that
     * converts to the same nodes.
     */
    private function body(string $old, string $new): string
    {
        $reuse = [];

        foreach ($this->blocks($old) as [$text, $nodes]) {
            if ($nodes !== null) {
                $reuse[$nodes] ??= $text;
            }
        }

        $blocks = array_map(
            fn (array $block) => $block[1] !== null && isset($reuse[$block[1]]) ? $reuse[$block[1]] : $block[0],
            $this->blocks($new),
        );

        return implode("\n\n", $blocks)."\n";
    }

    /**
     * Top-level blocks of a Markdown text with the nodes each converts to.
     *
     * @return list<array{0: string, 1: string|null}>
     */
    private function blocks(string $markdown): array
    {
        $lines = explode("\n", str_replace("\r\n", "\n", $markdown));
        $blocks = [];

        foreach ($this->parser->parse($markdown)->children() as $node) {
            if (! $node instanceof AbstractBlock || $node->getStartLine() === null || $node->getEndLine() === null) {
                continue;
            }

            $text = rtrim(implode("\n", array_slice($lines, $node->getStartLine() - 1, $node->getEndLine() - $node->getStartLine() + 1)));

            try {
                $nodes = (string) json_encode($this->markdown->convert($text, PackageMedia::comparable())->doc['content'] ?? []);
            } catch (InvalidRichContent) {
                $nodes = null;
            }

            $blocks[] = [$text, $nodes];
        }

        return $blocks;
    }
}
