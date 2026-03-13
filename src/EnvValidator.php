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
}
