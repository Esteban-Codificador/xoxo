<?php

namespace App\Domain\Content\RichContent\Markdown;

use League\CommonMark\Node\Inline\AbstractInline;

final class InlineMath extends AbstractInline
{
    public function __construct(public readonly string $latex)
    {
        parent::__construct();
    }
}
