<?php

declare(strict_types=1);

namespace PhilipRehberger\EnvValidator;

final class ValidationResult
{
    /**
     * @param  array<string>  $missing
     * @param  array<string, string>  $invalid
     * @param  array<string>  $warnings
     */
    public function __construct(
        public readonly bool $passed,
        public readonly array $missing = [],
        public readonly array $invalid = [],
        public readonly array $warnings = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'passed' => $this->passed,
            'missing' => $this->missing,
            'invalid' => $this->invalid,
            'warnings' => $this->warnings,
        ];
    }
}
