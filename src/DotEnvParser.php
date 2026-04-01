<?php

declare(strict_types=1);

namespace PhilipRehberger\EnvValidator;

use RuntimeException;

final class DotEnvParser
{
    /**
     * Parse a .env file and return key-value pairs.
     *
     * Handles comments (#), empty lines, quoted values (single and double),
     * multiline values, export prefix, and variable interpolation (${VAR}).
     *
     * @return array<string, string>
     *
     * @throws RuntimeException
     */
    public static function parse(string $path): array
    {
        if (! file_exists($path)) {
            throw new RuntimeException("File not found: {$path}");
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException("Unable to read file: {$path}");
        }

        $lines = explode("\n", $content);
        $env = [];
        $i = 0;
        $count = count($lines);

        while ($i < $count) {
            $line = $lines[$i];
            $trimmed = trim($line);

            // Skip empty lines and comments
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                $i++;

                continue;
            }

            // Strip export prefix
            if (str_starts_with($trimmed, 'export ')) {
                $trimmed = substr($trimmed, 7);
            }

            // Split on first =
            $eqPos = strpos($trimmed, '=');

            if ($eqPos === false) {
                $i++;

                continue;
            }

            $key = trim(substr($trimmed, 0, $eqPos));
            $rawValue = substr($trimmed, $eqPos + 1);

            $value = self::parseValue($rawValue, $lines, $i, $count);

            // Interpolate variables
            $value = self::interpolate($value, $env);

            $env[$key] = $value;
            $i++;
        }

        return $env;
    }

    /**
     * Parse a value, handling quotes and multiline values.
     *
     * @param  array<string>  $lines
     */
    private static function parseValue(string $rawValue, array $lines, int &$i, int $count): string
    {
        $rawValue = trim($rawValue);

        // Double-quoted value
        if (str_starts_with($rawValue, '"')) {
            return self::parseQuotedValue($rawValue, $lines, $i, $count, '"');
        }

        // Single-quoted value
        if (str_starts_with($rawValue, "'")) {
            return self::parseQuotedValue($rawValue, $lines, $i, $count, "'");
        }

        // Strip inline comments for unquoted values
        $commentPos = strpos($rawValue, ' #');

        if ($commentPos !== false) {
            $rawValue = rtrim(substr($rawValue, 0, $commentPos));
        }

        return $rawValue;
    }

    /**
     * Parse a quoted (single or double) value, supporting multiline.
     *
     * @param  array<string>  $lines
     */
    private static function parseQuotedValue(string $rawValue, array $lines, int &$i, int $count, string $quote): string
    {
        // Remove opening quote
        $value = substr($rawValue, 1);

        // Check if closing quote is on same line
        $closePos = strpos($value, $quote);

        if ($closePos !== false) {
            return substr($value, 0, $closePos);
        }

        // Multiline: accumulate lines until closing quote
        $parts = [$value];

        while (++$i < $count) {
            $nextLine = $lines[$i];
            $closePos = strpos($nextLine, $quote);

            if ($closePos !== false) {
                $parts[] = substr($nextLine, 0, $closePos);

                break;
            }

            $parts[] = $nextLine;
        }

        return implode("\n", $parts);
    }

    /**
     * Interpolate ${VAR} references in a value.
     *
     * @param  array<string, string>  $env
     */
    private static function interpolate(string $value, array $env): string
    {
        return (string) preg_replace_callback('/\$\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function (array $matches) use ($env): string {
            $varName = $matches[1];

            if (isset($env[$varName])) {
                return $env[$varName];
            }

            $fromEnv = getenv($varName);

            return $fromEnv !== false ? $fromEnv : $matches[0];
        }, $value);
    }
}
