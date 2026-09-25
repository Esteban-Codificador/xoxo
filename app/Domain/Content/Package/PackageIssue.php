<?php

namespace App\Domain\Content\Package;

final readonly class PackageIssue
{
    public function __construct(public string $file, public string $message) {}

    public function __toString(): string
    {
        return "{$this->file}: {$this->message}";
    }
}
