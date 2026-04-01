<?php

declare(strict_types=1);

namespace PhilipRehberger\EnvValidator;

use RuntimeException;

final class ProfileRegistry
{
    /** @var array<string, array<string, string|array<string>>> */
    private static array $profiles = [];

    /**
     * Register a named validation profile.
     *
     * @param  array<string, string|array<string>>  $schema
     */
    public static function register(string $name, array $schema): void
    {
        self::$profiles[$name] = $schema;
    }

    /**
     * Validate against a named profile.
     *
     * @param  array<string, string>|null  $env  Optional env source (uses getenv() if null)
     *
     * @throws RuntimeException
     */
    public static function validate(string $name, ?array $env = null): ValidationResult
    {
        if (! self::has($name)) {
            throw new RuntimeException("Validation profile '{$name}' is not registered.");
        }

        $schema = self::$profiles[$name];

        $pending = new PendingValidation(array_keys($schema));

        if ($env !== null) {
            $pending->setEnvSource($env);
        }

        foreach ($schema as $var => $rule) {
            $types = is_array($rule) ? $rule : [$rule];

            foreach ($types as $type) {
                $pending->type($var, $type);
            }
        }

        return $pending->validate();
    }

    /**
     * Check if a profile exists.
     */
    public static function has(string $name): bool
    {
        return isset(self::$profiles[$name]);
    }

    /**
     * List registered profile names.
     *
     * @return array<string>
     */
    public static function profiles(): array
    {
        return array_keys(self::$profiles);
    }

    /**
     * Clear all registered profiles (useful for testing).
     */
    public static function clear(): void
    {
        self::$profiles = [];
    }
}
