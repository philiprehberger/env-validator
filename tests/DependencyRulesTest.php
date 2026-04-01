<?php

declare(strict_types=1);

namespace PhilipRehberger\EnvValidator\Tests;

use PhilipRehberger\EnvValidator\EnvValidator;
use PHPUnit\Framework\TestCase;

final class DependencyRulesTest extends TestCase
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

    public function test_required_if_triggers_when_condition_var_equals_value(): void
    {
        $this->setEnv('TEST_DB_DRIVER', 'mysql');

        $result = EnvValidator::required(['TEST_DB_DRIVER', 'TEST_DB_HOST'])
            ->requiredIf('TEST_DB_HOST', 'TEST_DB_DRIVER', 'mysql')
            ->validate();

        $this->assertFalse($result->passed);
        $this->assertContains('TEST_DB_HOST', $result->missing);
    }

    public function test_required_if_skips_when_condition_var_does_not_match(): void
    {
        $this->setEnv('TEST_DB_DRIVER', 'sqlite');

        $result = EnvValidator::required(['TEST_DB_DRIVER', 'TEST_DB_HOST'])
            ->requiredIf('TEST_DB_HOST', 'TEST_DB_DRIVER', 'mysql')
            ->validate();

        $this->assertTrue($result->passed);
    }

    public function test_depends_on_requires_when_dependency_is_set(): void
    {
        $this->setEnv('TEST_CACHE_DRIVER', 'redis');

        $result = EnvValidator::required(['TEST_CACHE_DRIVER', 'TEST_REDIS_HOST'])
            ->dependsOn('TEST_REDIS_HOST', 'TEST_CACHE_DRIVER')
            ->validate();

        $this->assertFalse($result->passed);
        $this->assertContains('TEST_REDIS_HOST', $result->missing);
    }

    public function test_depends_on_skips_when_dependency_is_not_set(): void
    {
        $result = EnvValidator::required(['TEST_CACHE_DRIVER', 'TEST_REDIS_HOST'])
            ->dependsOn('TEST_CACHE_DRIVER', 'TEST_SOME_UNSET_VAR')
            ->dependsOn('TEST_REDIS_HOST', 'TEST_CACHE_DRIVER')
            ->validate();

        $this->assertTrue($result->passed);
    }

    public function test_required_unless_requires_when_condition_var_does_not_match(): void
    {
        $this->setEnv('TEST_APP_ENV', 'production');

        $result = EnvValidator::required(['TEST_APP_ENV', 'TEST_APP_SECRET'])
            ->requiredUnless('TEST_APP_SECRET', 'TEST_APP_ENV', 'local')
            ->validate();

        $this->assertFalse($result->passed);
        $this->assertContains('TEST_APP_SECRET', $result->missing);
    }

    public function test_required_unless_skips_when_condition_var_matches(): void
    {
        $this->setEnv('TEST_APP_ENV', 'local');

        $result = EnvValidator::required(['TEST_APP_ENV', 'TEST_APP_SECRET'])
            ->requiredUnless('TEST_APP_SECRET', 'TEST_APP_ENV', 'local')
            ->validate();

        $this->assertTrue($result->passed);
    }
}
