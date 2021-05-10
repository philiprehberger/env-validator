# PHP Env Validator

[![Tests](https://github.com/philiprehberger/env-validator/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/env-validator/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/env-validator.svg)](https://packagist.org/packages/philiprehberger/env-validator)
[![License](https://img.shields.io/github/license/philiprehberger/env-validator)](LICENSE)

Validate required environment variables with type checking and defaults.

## Requirements

- PHP 8.2+

## Installation

```bash
composer require philiprehberger/env-validator
```

## Usage

### Required Variables

Check that environment variables are present:

```php
use PhilipRehberger\EnvValidator\EnvValidator;

$result = EnvValidator::required(['APP_KEY', 'DATABASE_URL', 'REDIS_HOST'])
    ->validate();

if (!$result->passed) {
    echo 'Missing: ' . implode(', ', $result->missing);
}
```

### Schema with Type Rules

Define variables with type validation in a single call:

```php
$result = EnvValidator::schema([
    'APP_PORT' => 'int',
    'APP_DEBUG' => 'bool',
    'APP_URL' => 'url',
    'ADMIN_EMAIL' => 'email',
])->validate();
```

### Default Values

Provide fallback values for variables that may not be set:

```php
$result = EnvValidator::required(['APP_PORT', 'APP_ENV'])
    ->defaults([
        'APP_PORT' => '8080',
        'APP_ENV' => 'production',
    ])
    ->validate();
```

### Optional Variables

Optional variables generate warnings but do not cause validation failure:

```php
$result = EnvValidator::required(['DATABASE_URL'])
    ->optional(['CACHE_DRIVER', 'QUEUE_CONNECTION'])
    ->validate();

// $result->warnings contains notices about unset optional vars
```

### Type Validation

Add type rules to individual variables:

```php
$result = EnvValidator::required(['API_PORT', 'API_URL'])
    ->type('API_PORT', 'int')
    ->type('API_URL', 'url')
    ->validate();
```

### Validate or Fail

Throw an exception if validation fails:

```php
use PhilipRehberger\EnvValidator\Exceptions\EnvValidationException;

try {
    EnvValidator::required(['APP_KEY', 'DATABASE_URL'])
        ->validateOrFail();
} catch (EnvValidationException $e) {
    echo $e->getMessage();
    // Access the full result
    $result = $e->result;
}
```

### IP Validation

Validate IP addresses with specific version constraints:

```php
$result = EnvValidator::required(['SERVER_IP', 'GATEWAY_V4', 'GATEWAY_V6'])
    ->type('SERVER_IP', 'ip')
    ->type('GATEWAY_V4', 'ipv4')
    ->type('GATEWAY_V6', 'ipv6')
    ->validate();
```

### Custom Validation Rules

Define your own validation logic with a callable:

```php
$result = EnvValidator::required(['APP_SECRET'])
    ->custom('APP_SECRET', fn (string $value) => strlen($value) >= 32, 'Secret must be at least 32 characters.')
    ->validate();
```

### Enum Validation

Validate that a value matches one of a backed enum's cases:

```php
enum Environment: string
{
    case Production = 'production';
    case Staging = 'staging';
    case Development = 'development';
}

$result = EnvValidator::required(['APP_ENV'])
    ->enum('APP_ENV', Environment::class)
    ->validate();
```

### Supported Types

| Type                | Description                                            |
|---------------------|--------------------------------------------------------|
| `string`            | Always passes (any string value)                       |
| `int`, `integer`    | Numeric digits, optionally prefixed with `-`           |
| `float`, `number`   | Any numeric value (uses `is_numeric`)                  |
| `bool`, `boolean`   | `true`, `false`, `1`, `0`, `yes`, `no` (case-insensitive) |
| `url`               | Valid URL (uses `FILTER_VALIDATE_URL`)                 |
| `email`             | Valid email (uses `FILTER_VALIDATE_EMAIL`)             |
| `ip`                | Valid IP address (uses `FILTER_VALIDATE_IP`)           |
| `ipv4`              | Valid IPv4 address                                     |
| `ipv6`              | Valid IPv6 address                                     |
| `json`              | Valid JSON string                                      |

## API

### `EnvValidator`

| Method | Description |
|--------|-------------|
| `required(array $vars): PendingValidation` | Define required environment variables |
| `schema(array $rules): PendingValidation` | Define variables with type rules |

### `PendingValidation`

| Method | Description |
|--------|-------------|
| `optional(array $vars): self` | Add optional variables (warnings only) |
| `defaults(array $defaults): self` | Set default values for missing variables |
| `type(string $var, string $type): self` | Add a type rule for a variable |
| `custom(string $var, callable $validator, string $message = ''): self` | Add a custom validation rule |
| `enum(string $var, string $enumClass): self` | Validate against a backed enum |
| `validate(): ValidationResult` | Run validation and return result |
| `validateOrFail(): void` | Run validation, throw on failure |

### `ValidationResult`

| Property | Type | Description |
|----------|------|-------------|
| `passed` | `bool` | Whether validation passed |
| `missing` | `array<string>` | List of missing required variables |
| `invalid` | `array<string, string>` | Map of variable names to error messages |
| `warnings` | `array<string>` | List of warning messages |
| `toArray()` | `array` | Convert result to array |

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

## License

MIT
