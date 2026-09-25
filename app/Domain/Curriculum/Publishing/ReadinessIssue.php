<?php

namespace App\Domain\Curriculum\Publishing;

/**
 * One unmet publishing requirement. The code maps to a translated message
 * (lang/{locale}/publishing.php) so the editor checklist can render it.
 */
final readonly class ReadinessIssue
{
    /**
     * @param  array<string, int|string>  $params
     */
    public function __construct(public string $code, public array $params = []) {}

    public function message(): string
    {
        return __("publishing.{$this->code}", $this->params);
    }
}
