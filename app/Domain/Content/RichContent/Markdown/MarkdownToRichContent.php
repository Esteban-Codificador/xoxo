<?php

namespace App\Domain\Content\RichContent\Markdown;

use App\Domain\Content\RichContent\InvalidRichContent;
use App\Domain\Content\RichContent\RichContent;
use App\Domain\Content\RichContent\RichContentSchema;
use App\Domain\Content\RichContent\RichContentValidator;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\BlockQuote;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Block\ThematicBreak;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Extension\CommonMark\Node\Inline\Emphasis;
use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Extension\Strikethrough\Strikethrough;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Extension\Table\TableCell;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\Table\TableRow;
use League\CommonMark\Extension\Table\TableSection;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Newline;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\MarkdownParser;

/**
 * Converts the authoring Markdown of the content package (GitHub-compatible
 * dialect, see docs/content-architecture.md §5) into RichContent.
 */
final class MarkdownToRichContent
{
    private const string ALERT_PATTERN = '/^\[!(NOTE|TIP|IMPORTANT|WARNING|CAUTION)\]\s*/';

    private readonly MarkdownParser $parser;

    /** @var list<string> */
    private array $errors = [];

    private int $line = 1;

    public function __construct()
    {
        $environment = new Environment;
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new TableExtension);
        $environment->addExtension(new StrikethroughExtension);
        $environment->addExtension(new AutolinkExtension);
        $environment->addInlineParser(new InlineMathParser, 100);

        $this->parser = new MarkdownParser($environment);
    }

    /**
     * @throws InvalidRichContent
     */
    public function convert(string $markdown): RichContent
    {
        $this->errors = [];
        $this->line = 1;

        $document = ['type' => 'doc', 'content' => $this->blocks($this->parser->parse($markdown))];
        $errors = $this->takeErrors();

        if ($errors !== []) {
            throw new InvalidRichContent($errors);
        }

        $envelope = ['version' => RichContent::CURRENT_VERSION, 'doc' => $document];
        $schemaErrors = (new RichContentValidator)->errors($envelope);

        if ($schemaErrors !== []) {
            throw new InvalidRichContent($schemaErrors);
        }

        return RichContent::fromArray($envelope);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function blocks(Node $parent): array
    {
        $blocks = [];

        foreach ($parent->children() as $child) {
            if ($child instanceof AbstractBlock && $child->getStartLine() !== null) {
                $this->line = $child->getStartLine();
            }

            $block = $this->block($child);

            if ($block !== null) {
                $blocks[] = $block;
            }
        }

        return $blocks;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function block(Node $node): ?array
    {
        return match (true) {
            $node instanceof Paragraph => $this->withContent(['type' => 'paragraph'], $this->inlines($node, [])),
            $node instanceof Heading => $this->heading($node),
            $node instanceof BlockQuote => $this->blockQuote($node),
            $node instanceof ListBlock => $this->list($node),
            $node instanceof FencedCode => $this->fencedCode($node),
            $node instanceof IndentedCode => $this->codeBlock(null, $node->getLiteral()),
            $node instanceof ThematicBreak => ['type' => 'horizontalRule'],
            $node instanceof Table => $this->table($node),
            $node instanceof HtmlBlock => $this->error('el HTML crudo no está permitido.'),
            default => $this->error('bloque no soportado ('.class_basename($node).').'),
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function heading(Heading $heading): ?array
    {
        $level = $heading->getLevel();

        if ($level < 2 || $level > 4) {
            return $this->error("encabezado de nivel {$level}: usa niveles 2 a 4 (el H1 es el título de la lección).");
        }

        return $this->withContent(['type' => 'heading', 'attrs' => ['level' => $level]], $this->inlines($heading, []));
    }

    /**
     * GitHub alerts (`> [!TIP]`) become callouts; any other quote stays a blockquote.
     *
     * @return array<string, mixed>|null
     */
    private function blockQuote(BlockQuote $quote): ?array
    {
        $content = $this->blocks($quote);
        $first = $content[0] ?? null;
        $firstText = $first['content'][0]['text'] ?? null;

        if (($first['type'] ?? null) !== 'paragraph' || ! is_string($firstText) || preg_match(self::ALERT_PATTERN, $firstText, $match) !== 1) {
            return $this->withContent(['type' => 'blockquote'], $content);
        }

        $remaining = (string) preg_replace(self::ALERT_PATTERN, '', $firstText);

        if ($remaining === '') {
            array_shift($first['content']);
        } else {
            $first['content'][0]['text'] = $remaining;
        }

        if ($first['content'] === []) {
            array_shift($content);
        } else {
            $content[0] = $first;
        }

        if ($content === []) {
            return $this->error('el callout está vacío.');
        }

        return ['type' => 'callout', 'attrs' => ['variant' => strtolower($match[1])], 'content' => $content];
    }

    /**
     * @return array<string, mixed>
     */
    private function list(ListBlock $list): array
    {
        $data = $list->getListData();
        $items = [];

        foreach ($list->children() as $item) {
            if ($item instanceof ListItem) {
                $items[] = ['type' => 'listItem', 'content' => $this->blocks($item) ?: [['type' => 'paragraph']]];
            }
        }

        if ($data->type === ListBlock::TYPE_ORDERED) {
            $node = ['type' => 'orderedList'];

            if (($data->start ?? 1) !== 1) {
                $node['attrs'] = ['start' => $data->start];
            }

            return $node + ['content' => $items];
        }

        return ['type' => 'bulletList', 'content' => $items];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fencedCode(FencedCode $code): ?array
    {
        $info = strtolower($code->getInfoWords()[0] ?? '');
        $literal = $code->getLiteral();

        return match ($info) {
            'mermaid' => ['type' => 'diagram', 'attrs' => ['kind' => 'mermaid', 'source' => rtrim($literal)]],
            'math' => ['type' => 'blockMath', 'attrs' => ['latex' => trim($literal)]],
            'video' => $this->video($literal),
            default => $this->codeBlock($info === '' ? null : $info, $literal),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function codeBlock(?string $language, string $literal): array
    {
        $text = (string) preg_replace('/\n$/', '', $literal);

        return $this->withContent(
            ['type' => 'codeBlock', 'attrs' => ['language' => $language]],
            $text === '' ? [] : [['type' => 'text', 'text' => $text]],
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function video(string $literal): ?array
    {
        $fields = [];

        foreach (preg_split('/\R/', trim($literal)) ?: [] as $line) {
            if (preg_match('/^\s*([a-z]+)\s*:\s*(\S+)\s*$/', $line, $match) === 1) {
                $fields[$match[1]] = $match[2];
            }
        }

        if (! isset($fields['provider'], $fields['id'])) {
            return $this->error('el bloque video necesita las líneas "provider:" e "id:".');
        }

        return ['type' => 'video', 'attrs' => ['provider' => strtolower($fields['provider']), 'videoId' => $fields['id']]];
    }

    /**
     * @return array<string, mixed>
     */
    private function table(Table $table): array
    {
        $rows = [];

        foreach ($table->children() as $section) {
            if (! $section instanceof TableSection) {
                continue;
            }

            foreach ($section->children() as $row) {
                if (! $row instanceof TableRow) {
                    continue;
                }

                $cells = [];

                foreach ($row->children() as $cell) {
                    if ($cell instanceof TableCell) {
                        $cells[] = [
                            'type' => $cell->getType() === TableCell::TYPE_HEADER ? 'tableHeader' : 'tableCell',
                            'content' => [$this->withContent(['type' => 'paragraph'], $this->inlines($cell, []))],
                        ];
                    }
                }

                $rows[] = ['type' => 'tableRow', 'content' => $cells];
            }
        }

        return ['type' => 'table', 'content' => $rows];
    }

    /**
     * @param  list<array<string, mixed>>  $marks
     * @return list<array<string, mixed>>
     */
    private function inlines(Node $parent, array $marks): array
    {
        $nodes = [];

        foreach ($parent->children() as $child) {
            array_push($nodes, ...$this->inline($child, $marks));
        }

        return $this->mergeText($nodes);
    }

    /**
     * @param  list<array<string, mixed>>  $marks
     * @return list<array<string, mixed>>
     */
    private function inline(Node $node, array $marks): array
    {
        return match (true) {
            $node instanceof Text => $this->text($node->getLiteral(), $marks),
            $node instanceof Code => $this->text($node->getLiteral(), [...$marks, ['type' => 'code']]),
            $node instanceof Strong => $this->inlines($node, [...$marks, ['type' => 'bold']]),
            $node instanceof Emphasis => $this->inlines($node, [...$marks, ['type' => 'italic']]),
            $node instanceof Strikethrough => $this->inlines($node, [...$marks, ['type' => 'strike']]),
            $node instanceof Link => $this->link($node, $marks),
            $node instanceof Newline => $node->getType() === Newline::HARDBREAK
                ? [['type' => 'hardBreak']]
                : $this->text(' ', $marks),
            $node instanceof InlineMath => [['type' => 'inlineMath', 'attrs' => ['latex' => $node->latex]]],
            $node instanceof Image => $this->inlineError('las imágenes se habilitan en la Fase 6 (media_assets).'),
            $node instanceof HtmlInline => $this->inlineError('el HTML crudo no está permitido.'),
            default => $this->inlineError('elemento en línea no soportado ('.class_basename($node).').'),
        };
    }

    /**
     * @param  list<array<string, mixed>>  $marks
     * @return list<array<string, mixed>>
     */
    private function link(Link $link, array $marks): array
    {
        $href = $link->getUrl();
        $error = RichContentSchema::safeHref($href);

        if ($error !== null) {
            return $this->inlineError("enlace \"{$href}\": {$error}.");
        }

        return $this->inlines($link, [...$marks, ['type' => 'link', 'attrs' => ['href' => $href]]]);
    }

    /**
     * @param  list<array<string, mixed>>  $marks
     * @return list<array<string, mixed>>
     */
    private function text(string $text, array $marks): array
    {
        if ($text === '') {
            return [];
        }

        $node = ['type' => 'text', 'text' => $text];

        if ($marks !== []) {
            $node['marks'] = $marks;
        }

        return [$node];
    }

    /**
     * CommonMark splits text at every special character; adjacent text nodes
     * with the same marks are joined as the editor would store them.
     *
     * @param  list<array<string, mixed>>  $nodes
     * @return list<array<string, mixed>>
     */
    private function mergeText(array $nodes): array
    {
        $merged = [];

        foreach ($nodes as $node) {
            $last = array_key_last($merged);

            if (
                $last !== null
                && $node['type'] === 'text'
                && $merged[$last]['type'] === 'text'
                && ($merged[$last]['marks'] ?? []) === ($node['marks'] ?? [])
            ) {
                $merged[$last]['text'] .= $node['text'];

                continue;
            }

            $merged[] = $node;
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  list<array<string, mixed>>  $content
     * @return array<string, mixed>
     */
    private function withContent(array $node, array $content): array
    {
        if ($content !== []) {
            $node['content'] = $content;
        }

        return $node;
    }

    /**
     * @return list<string>
     */
    private function takeErrors(): array
    {
        $errors = $this->errors;
        $this->errors = [];

        return $errors;
    }

    private function error(string $message): null
    {
        $this->errors[] = "línea {$this->line}: {$message}";

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function inlineError(string $message): array
    {
        $this->error($message);

        return [];
    }
}
