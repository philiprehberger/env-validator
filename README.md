# PHP Env Validator

[![Tests](https://github.com/philiprehberger/env-validator/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/env-validator/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/env-validator.svg)](https://packagist.org/packages/philiprehberger/env-validator)
[![Last updated](https://img.shields.io/github/last-commit/philiprehberger/env-validator)](https://github.com/philiprehberger/env-validator/commits/main)

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

### Dependency Rules

Make variables conditionally required based on other variables:

```php
// DB_HOST is only required when DB_DRIVER is set
$result = EnvValidator::required(['DB_DRIVER', 'DB_HOST'])
    ->dependsOn('DB_HOST', 'DB_DRIVER')
    ->validate();

// REDIS_HOST is required when CACHE_DRIVER equals "redis"
$result = EnvValidator::required(['CACHE_DRIVER', 'REDIS_HOST'])
    ->requiredIf('REDIS_HOST', 'CACHE_DRIVER', 'redis')
    ->validate();

// APP_SECRET is required unless APP_ENV equals "local"
$result = EnvValidator::required(['APP_ENV', 'APP_SECRET'])
    ->requiredUnless('APP_SECRET', 'APP_ENV', 'local')
    ->validate();
```

### .env File Parsing

Validate a `.env` file without loading it into the environment:

```php
$result = EnvValidator::fromFile('/path/to/.env')
    ->type('APP_PORT', 'int')
    ->type('APP_URL', 'url')
    ->validate();
```

The parser handles comments, quoted values, multiline values, `export` prefix, and `${VAR}` interpolation.

### Environment Profiles

Register named validation profiles for different environments:

```php
// Register profiles
EnvValidator::profile('web', [
    'APP_URL' => 'url',
    'APP_PORT' => 'int',
    'APP_DEBUG' => 'bool',
]);

EnvValidator::profile('worker', [
    'QUEUE_CONNECTION' => 'string',
    'REDIS_HOST' => 'string',
]);

// Validate against a profile
$result = EnvValidator::validateProfile('web');
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
| `fromFile(string $path): PendingFileValidation` | Validate a .env file without loading it |
| `profile(string $name, array $schema): void` | Register a named validation profile |
| `validateProfile(string $name): ValidationResult` | Validate against a named profile |

### `PendingValidation`

| Method | Description |
|--------|-------------|
| `optional(array $vars): self` | Add optional variables (warnings only) |
| `defaults(array $defaults): self` | Set default values for missing variables |
| `type(string $var, string $type): self` | Add a type rule for a variable |
| `custom(string $var, callable $validator, string $message = ''): self` | Add a custom validation rule |
| `enum(string $var, string $enumClass): self` | Validate against a backed enum |
| `dependsOn(string $var, string $dependency): self` | Require variable only when dependency is set |
| `requiredIf(string $var, string $conditionVar, mixed $value): self` | Require variable when condition equals value |
| `requiredUnless(string $var, string $conditionVar, mixed $value): self` | Require variable unless condition equals value |
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

## Support

If you find this project useful:

⭐ [Star the repo](https://github.com/philiprehberger/env-validator)

🐛 [Report issues](https://github.com/philiprehberger/env-validator/issues?q=is%3Aissue+is%3Aopen+label%3Abug)

💡 [Suggest features](https://github.com/philiprehberger/env-validator/issues?q=is%3Aissue+is%3Aopen+label%3Aenhancement)

❤️ [Sponsor development](https://github.com/sponsors/philiprehberger)

🌐 [All Open Source Projects](https://philiprehberger.com/open-source-packages)

💻 [GitHub Profile](https://github.com/philiprehberger)

🔗 [LinkedIn Profile](https://www.linkedin.com/in/philiprehberger)

## License

[MIT](LICENSE)
