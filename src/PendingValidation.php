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

    /** @var array<string, array{callable, string}> */
    private array $customRules = [];

    /** @var array<string, class-string> */
    private array $enumRules = [];

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
     * Add a custom validation rule for a specific variable.
     */
    public function custom(string $var, callable $validator, string $message = ''): self
    {
        $this->customRules[$var] = [$validator, $message];

        return $this;
    }

    /**
     * Validate that a variable's value matches a backed enum case.
     *
     * @param  class-string  $enumClass
     */
    public function enum(string $var, string $enumClass): self
    {
        $this->enumRules[$var] = $enumClass;

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
            $this->validateCustom($var, $value, $invalid);
            $this->validateEnum($var, $value, $invalid);
        }

        foreach ($this->optional as $var) {
            $value = $this->resolveValue($var);

            if ($value === null) {
                $warnings[] = "Optional variable '{$var}' is not set.";

                continue;
            }

            $this->validateType($var, $value, $invalid);
            $this->validateCustom($var, $value, $invalid);
            $this->validateEnum($var, $value, $invalid);
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
                'ip' => filter_var($value, FILTER_VALIDATE_IP) !== false,
                'ipv4' => filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false,
                'ipv6' => filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false,
                'json' => json_decode($value) !== null || $value === 'null',
                default => true,
            };

            if (! $valid) {
                $invalid[$var] = "Variable '{$var}' failed type validation '{$type}' with value '{$value}'.";
            }
        }
    }

    /**
     * @param  array<string, string>  $invalid
     */
    private function validateCustom(string $var, string $value, array &$invalid): void
    {
        if (! isset($this->customRules[$var])) {
            return;
        }

        [$validator, $message] = $this->customRules[$var];

        if (! $validator($value)) {
            $invalid[$var] = $message !== ''
                ? $message
                : "Variable '{$var}' failed custom validation with value '{$value}'.";
        }
    }

    /**
     * @param  array<string, string>  $invalid
     */
    private function validateEnum(string $var, string $value, array &$invalid): void
    {
        if (! isset($this->enumRules[$var])) {
            return;
        }

        $enumClass = $this->enumRules[$var];

        if (! enum_exists($enumClass)) {
            $invalid[$var] = "Variable '{$var}' references non-existent enum '{$enumClass}'.";

            return;
        }

        if (! is_a($enumClass, \BackedEnum::class, true)) {
            $invalid[$var] = "Variable '{$var}' references non-backed enum '{$enumClass}'.";

            return;
        }

        $cases = $enumClass::cases();
        /** @var array<\BackedEnum> $cases */
        $values = array_map(fn (\BackedEnum $case) => (string) $case->value, $cases);

        if (! in_array($value, $values, true)) {
            $invalid[$var] = "Variable '{$var}' value '{$value}' is not a valid case of '{$enumClass}'.";
        }
    }
}
