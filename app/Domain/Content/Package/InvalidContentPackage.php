<?php

namespace App\Domain\Content\Package;

use RuntimeException;

final class InvalidContentPackage extends RuntimeException
{
    /**
     * @param  list<PackageIssue>  $issues
     */
    public function __construct(public readonly array $issues)
    {
        parent::__construct(count($issues).' problema(s) en el paquete de contenido.');
    }
}
