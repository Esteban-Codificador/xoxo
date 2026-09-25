<?php

namespace App\Domain\Content\RichContent\Markdown;

use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;

/**
 * Parses `$latex$` into an InlineMath node. Like Pandoc, the opening `$`
 * must not be followed by a space and the closing `$` must not be preceded
 * by one nor followed by a digit, so prices such as "$5 y $10" stay text.
 */
final class InlineMathParser implements InlineParserInterface
{
    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::regex('\$(?!\s)([^$\n]+?)(?<!\s)\$(?!\d)');
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        $latex = $inlineContext->getSubMatches()[0] ?? '';

        $inlineContext->getCursor()->advanceBy($inlineContext->getFullMatchLength());
        $inlineContext->getContainer()->appendChild(new InlineMath($latex));

        return true;
    }
}
