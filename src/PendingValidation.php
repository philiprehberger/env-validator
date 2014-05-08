<?php

declare(strict_types=1);

namespace PhilipRehberger\EnvValidator;

use PhilipRehberger\EnvValidator\Exceptions\EnvValidationException;

final class PendingValidation
{
    /** @var array<string> */
    private array $required;

    /** @var array<string> */
    private array $optional = [];

    /** @var array<string, string> */
    private array $defaults = [];

    /** @var array<string, array<string>> */
    private array $types = [];

    /**
     * @param  array<string>  $required
     */
    public function __construct(array $required)
    {
        $this->required = $required;
    }

    /**
     * @param  array<string>  $vars
     */
    public function optional(array $vars): self
    {
        $this->optional = array_merge($this->optional, $vars);

        return $this;
    }

    /**
     * @param  array<string, string>  $defaults
     */
    public function defaults(array $defaults): self
    {
        $this->defaults = array_merge($this->defaults, $defaults);

        return $this;
    }

    /**
     * Add a type rule for a specific variable.
     */
    public function type(string $var, string $type): self
    {
        $this->types[$var][] = $type;

        return $this;
    }

    /**
     * Validate and return the result.
     */
    public function validate(): ValidationResult
    {
        $missing = [];
        $invalid = [];
        $warnings = [];

        foreach ($this->required as $var) {
            $value = $this->resolveValue($var);

            if ($value === null) {
                $missing[] = $var;

                continue;
            }

            $this->validateType($var, $value, $invalid);
        }

        foreach ($this->optional as $var) {
            $value = $this->resolveValue($var);

            if ($value === null) {
                $warnings[] = "Optional variable '{$var}' is not set.";

                continue;
            }

            $this->validateType($var, $value, $invalid);
        }

        return new ValidationResult(
            passed: empty($missing) && empty($invalid),
            missing: $missing,
            invalid: $invalid,
            warnings: $warnings,
        );
    }

    /**
     * Validate or throw on failure.
     *
     * @throws EnvValidationException
     */
    public function validateOrFail(): void
    {
        $result = $this->validate();

        if (! $result->passed) {
            throw EnvValidationException::fromResult($result);
        }
    }

    private function resolveValue(string $var): ?string
    {
        $value = getenv($var);

        if ($value !== false && $value !== '') {
            return $value;
        }

        if (isset($this->defaults[$var])) {
            return $this->defaults[$var];
        }

        return null;
    }

    /**
     * @param  array<string, string>  $invalid
     */
    private function validateType(string $var, string $value, array &$invalid): void
    {
        if (! isset($this->types[$var])) {
            return;
        }

        foreach ($this->types[$var] as $type) {
            $valid = match ($type) {
                'string' => true,
                'int', 'integer' => ctype_digit($value) || (str_starts_with($value, '-') && ctype_digit(substr($value, 1))),
                'float', 'number' => is_numeric($value),
                'bool', 'boolean' => in_array(strtolower($value), ['true', 'false', '1', '0', 'yes', 'no'], true),
                'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
                'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
                'json' => json_decode($value) !== null || $value === 'null',
                default => true,
            };

            if (! $valid) {
                $invalid[$var] = "Variable '{$var}' failed type validation '{$type}' with value '{$value}'.";
            }
        }
    }
}
