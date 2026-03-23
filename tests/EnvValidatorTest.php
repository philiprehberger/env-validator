<?php

declare(strict_types=1);

namespace PhilipRehberger\EnvValidator\Tests;

use PhilipRehberger\EnvValidator\EnvValidator;
use PhilipRehberger\EnvValidator\Exceptions\EnvValidationException;
use PHPUnit\Framework\TestCase;

final class EnvValidatorTest extends TestCase
{
    /** @var array<string> */
    private array $envVarsToClean = [];

    protected function tearDown(): void
    {
        foreach ($this->envVarsToClean as $var) {
            putenv($var);
        }

        $this->envVarsToClean = [];
    }

    private function setEnv(string $name, string $value): void
    {
        putenv("{$name}={$value}");
        $this->envVarsToClean[] = $name;
    }

    public function test_required_vars_present_passes(): void
    {
        $this->setEnv('TEST_APP_NAME', 'my-app');
        $this->setEnv('TEST_APP_KEY', 'secret');

        $result = EnvValidator::required(['TEST_APP_NAME', 'TEST_APP_KEY'])->validate();

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->missing);
    }

    public function test_required_var_missing_fails(): void
    {
        $result = EnvValidator::required(['TEST_MISSING_VAR'])->validate();

        $this->assertFalse($result->passed);
        $this->assertContains('TEST_MISSING_VAR', $result->missing);
    }

    public function test_multiple_missing_vars(): void
    {
        $result = EnvValidator::required(['TEST_VAR_A', 'TEST_VAR_B', 'TEST_VAR_C'])->validate();

        $this->assertFalse($result->passed);
        $this->assertCount(3, $result->missing);
        $this->assertContains('TEST_VAR_A', $result->missing);
        $this->assertContains('TEST_VAR_B', $result->missing);
        $this->assertContains('TEST_VAR_C', $result->missing);
    }

    public function test_type_validation_int_passes(): void
    {
        $this->setEnv('TEST_PORT', '8080');

        $result = EnvValidator::required(['TEST_PORT'])
            ->type('TEST_PORT', 'int')
            ->validate();

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->invalid);
    }

    public function test_type_validation_int_fails(): void
    {
        $this->setEnv('TEST_PORT', 'not-a-number');

        $result = EnvValidator::required(['TEST_PORT'])
            ->type('TEST_PORT', 'int')
            ->validate();

        $this->assertFalse($result->passed);
        $this->assertArrayHasKey('TEST_PORT', $result->invalid);
    }

    public function test_type_validation_bool_passes(): void
    {
        $this->setEnv('TEST_DEBUG', 'true');

        $result = EnvValidator::required(['TEST_DEBUG'])
            ->type('TEST_DEBUG', 'bool')
            ->validate();

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->invalid);
    }

    public function test_type_validation_url_passes(): void
    {
        $this->setEnv('TEST_API_URL', 'https://example.com/api');

        $result = EnvValidator::required(['TEST_API_URL'])
            ->type('TEST_API_URL', 'url')
            ->validate();

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->invalid);
    }

    public function test_type_validation_url_fails(): void
    {
        $this->setEnv('TEST_API_URL', 'not-a-url');

        $result = EnvValidator::required(['TEST_API_URL'])
            ->type('TEST_API_URL', 'url')
            ->validate();

        $this->assertFalse($result->passed);
        $this->assertArrayHasKey('TEST_API_URL', $result->invalid);
    }

    public function test_type_validation_email(): void
    {
        $this->setEnv('TEST_ADMIN_EMAIL', 'admin@example.com');

        $result = EnvValidator::required(['TEST_ADMIN_EMAIL'])
            ->type('TEST_ADMIN_EMAIL', 'email')
            ->validate();

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->invalid);
    }

    public function test_default_values_applied(): void
    {
        $result = EnvValidator::required(['TEST_DEFAULT_VAR'])
            ->defaults(['TEST_DEFAULT_VAR' => 'fallback'])
            ->validate();

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->missing);
    }

    public function test_optional_vars_dont_trigger_failure(): void
    {
        $this->setEnv('TEST_REQUIRED', 'present');

        $result = EnvValidator::required(['TEST_REQUIRED'])
            ->optional(['TEST_OPTIONAL_MISSING'])
            ->validate();

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->missing);
        $this->assertNotEmpty($result->warnings);
    }

    public function test_validate_or_fail_throws_on_missing(): void
    {
        $this->expectException(EnvValidationException::class);
        $this->expectExceptionMessageMatches('/Missing: TEST_FAIL_VAR/');

        EnvValidator::required(['TEST_FAIL_VAR'])->validateOrFail();
    }

    public function test_validate_or_fail_passes_when_valid(): void
    {
        $this->setEnv('TEST_VALID_VAR', 'value');

        EnvValidator::required(['TEST_VALID_VAR'])->validateOrFail();

        $this->assertTrue(true);
    }

    public function test_schema_method(): void
    {
        $this->setEnv('TEST_HOST', 'https://example.com');
        $this->setEnv('TEST_PORT', '3000');
        $this->setEnv('TEST_DEBUG', 'false');

        $result = EnvValidator::schema([
            'TEST_HOST' => 'url',
            'TEST_PORT' => 'int',
            'TEST_DEBUG' => 'bool',
        ])->validate();

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->missing);
        $this->assertEmpty($result->invalid);
    }

    public function test_type_validation_ip_passes(): void
    {
        $this->setEnv('TEST_IP', '192.168.1.1');

        $result = EnvValidator::required(['TEST_IP'])
            ->type('TEST_IP', 'ip')
            ->validate();

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->invalid);
    }

    public function test_type_validation_ip_passes_with_ipv6(): void
    {
        $this->setEnv('TEST_IP', '::1');

        $result = EnvValidator::required(['TEST_IP'])
            ->type('TEST_IP', 'ip')
            ->validate();

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->invalid);
    }

    public function test_type_validation_ip_fails(): void
    {
        $this->setEnv('TEST_IP', 'not-an-ip');

        $result = EnvValidator::required(['TEST_IP'])
            ->type('TEST_IP', 'ip')
            ->validate();

        $this->assertFalse($result->passed);
        $this->assertArrayHasKey('TEST_IP', $result->invalid);
    }

    public function test_type_validation_ipv4_passes(): void
    {
        $this->setEnv('TEST_IP', '10.0.0.1');

        $result = EnvValidator::required(['TEST_IP'])
            ->type('TEST_IP', 'ipv4')
            ->validate();

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->invalid);
    }

    public function test_type_validation_ipv4_fails_with_ipv6(): void
    {
        $this->setEnv('TEST_IP', '::1');

        $result = EnvValidator::required(['TEST_IP'])
            ->type('TEST_IP', 'ipv4')
            ->validate();

        $this->assertFalse($result->passed);
        $this->assertArrayHasKey('TEST_IP', $result->invalid);
    }

    public function test_type_validation_ipv4_fails_with_invalid(): void
    {
        $this->setEnv('TEST_IP', '999.999.999.999');

        $result = EnvValidator::required(['TEST_IP'])
            ->type('TEST_IP', 'ipv4')
            ->validate();

        $this->assertFalse($result->passed);
        $this->assertArrayHasKey('TEST_IP', $result->invalid);
    }

    public function test_type_validation_ipv6_passes(): void
    {
        $this->setEnv('TEST_IP', '2001:0db8:85a3:0000:0000:8a2e:0370:7334');

        $result = EnvValidator::required(['TEST_IP'])
            ->type('TEST_IP', 'ipv6')
            ->validate();

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->invalid);
    }

    public function test_type_validation_ipv6_fails_with_ipv4(): void
    {
        $this->setEnv('TEST_IP', '192.168.1.1');

        $result = EnvValidator::required(['TEST_IP'])
            ->type('TEST_IP', 'ipv6')
            ->validate();

        $this->assertFalse($result->passed);
        $this->assertArrayHasKey('TEST_IP', $result->invalid);
    }

    public function test_custom_validation_passes(): void
    {
        $this->setEnv('TEST_CUSTOM', 'hello-world');

        $result = EnvValidator::required(['TEST_CUSTOM'])
            ->custom('TEST_CUSTOM', fn (string $value) => str_contains($value, '-'))
            ->validate();

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->invalid);
    }

    public function test_custom_validation_fails(): void
    {
        $this->setEnv('TEST_CUSTOM', 'helloworld');

        $result = EnvValidator::required(['TEST_CUSTOM'])
            ->custom('TEST_CUSTOM', fn (string $value) => str_contains($value, '-'))
            ->validate();

        $this->assertFalse($result->passed);
        $this->assertArrayHasKey('TEST_CUSTOM', $result->invalid);
    }

    public function test_custom_validation_with_custom_message(): void
    {
        $this->setEnv('TEST_CUSTOM', 'bad');

        $result = EnvValidator::required(['TEST_CUSTOM'])
            ->custom('TEST_CUSTOM', fn (string $value) => strlen($value) >= 5, 'Value must be at least 5 characters.')
            ->validate();

        $this->assertFalse($result->passed);
        $this->assertSame('Value must be at least 5 characters.', $result->invalid['TEST_CUSTOM']);
    }

    public function test_custom_validation_with_empty_value(): void
    {
        $result = EnvValidator::required(['TEST_CUSTOM_EMPTY'])
            ->custom('TEST_CUSTOM_EMPTY', fn (string $value) => $value !== '')
            ->validate();

        $this->assertFalse($result->passed);
        $this->assertContains('TEST_CUSTOM_EMPTY', $result->missing);
    }

    public function test_enum_validation_passes(): void
    {
        $this->setEnv('TEST_ENV', 'production');

        $result = EnvValidator::required(['TEST_ENV'])
            ->enum('TEST_ENV', TestEnvironment::class)
            ->validate();

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->invalid);
    }

    public function test_enum_validation_fails(): void
    {
        $this->setEnv('TEST_ENV', 'invalid');

        $result = EnvValidator::required(['TEST_ENV'])
            ->enum('TEST_ENV', TestEnvironment::class)
            ->validate();

        $this->assertFalse($result->passed);
        $this->assertArrayHasKey('TEST_ENV', $result->invalid);
    }

    public function test_enum_validation_is_case_sensitive(): void
    {
        $this->setEnv('TEST_ENV', 'Production');

        $result = EnvValidator::required(['TEST_ENV'])
            ->enum('TEST_ENV', TestEnvironment::class)
            ->validate();

        $this->assertFalse($result->passed);
        $this->assertArrayHasKey('TEST_ENV', $result->invalid);
    }
}

enum TestEnvironment: string
{
    case Production = 'production';
    case Staging = 'staging';
    case Development = 'development';
}
