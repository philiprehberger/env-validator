<?php

declare(strict_types=1);

namespace PhilipRehberger\EnvValidator;

final class EnvValidator
{
    /**
     * Define required variables.
     *
     * @param  array<string>  $vars
     */
    public static function required(array $vars): PendingValidation
    {
        return new PendingValidation($vars);
    }

    /**
     * Define a schema with rules.
     *
     * @param  array<string, string|array<string>>  $rules  Keys are env var names, values are type rules
     */
    public static function schema(array $rules): PendingValidation
    {
        $pending = new PendingValidation(array_keys($rules));

        foreach ($rules as $var => $rule) {
            $types = is_array($rule) ? $rule : [$rule];

            foreach ($types as $type) {
                $pending->type($var, $type);
            }
        }

        return $pending;
    }

    /**
     * Validate environment variables from a .env file without loading them.
     */
    public static function fromFile(string $path): PendingFileValidation
    {
        $env = DotEnvParser::parse($path);

        return new PendingFileValidation(array_keys($env), $env);
    }

    /**
     * Register a named validation profile.
     *
     * @param  array<string, string|array<string>>  $schema
     */
    public static function profile(string $name, array $schema): void
    {
        ProfileRegistry::register($name, $schema);
    }

    /**
     * Validate against a named profile.
     */
    public static function validateProfile(string $name): ValidationResult
    {
        return ProfileRegistry::validate($name);
    }
}
