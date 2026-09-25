<?php

namespace App\Domain\Content\RichContent;

use InvalidArgumentException;

final class InvalidRichContent extends InvalidArgumentException
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct(implode(PHP_EOL, $errors));
    }
}
