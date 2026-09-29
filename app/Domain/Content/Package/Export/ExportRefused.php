<?php

namespace App\Domain\Content\Package\Export;

use RuntimeException;

/**
 * The export cannot run: nothing was written.
 */
final class ExportRefused extends RuntimeException
{
    /**
     * @param  list<string>  $reasons
     */
    public function __construct(string $message, public readonly array $reasons = [])
    {
        parent::__construct($message);
    }
}
