<?php

declare(strict_types=1);

namespace PhilipRehberger\EnvValidator\Tests;

use PhilipRehberger\EnvValidator\EnvValidator;
use PhilipRehberger\EnvValidator\ProfileRegistry;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ProfileTest extends TestCase
{
    /** @var array<string> */
    private array $envVarsToClean = [];

    protected function setUp(): void
    {
        ProfileRegistry::clear();
    }

    protected function tearDown(): void
    {
        foreach ($this->envVarsToClean as $var) {
            putenv($var);
        }

        $this->envVarsToClean = [];
        ProfileRegistry::clear();
    }

    private function setEnv(string $name, string $value): void
    {
        putenv("{$name}={$value}");
        $this->envVarsToClean[] = $name;
    }

    public function test_register_and_validate_a_profile(): void
    {
        $this->setEnv('TEST_PROFILE_HOST', 'https://example.com');
        $this->setEnv('TEST_PROFILE_PORT', '3000');

        EnvValidator::profile('web', [
            'TEST_PROFILE_HOST' => 'url',
            'TEST_PROFILE_PORT' => 'int',
        ]);

        $result = EnvValidator::validateProfile('web');

        $this->assertTrue($result->passed);
    }

    public function test_validate_nonexistent_profile_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/not registered/');

        EnvValidator::validateProfile('nonexistent');
    }

    public function test_profiles_lists_all_registered_names(): void
    {
        EnvValidator::profile('web', ['HOST' => 'url']);
        EnvValidator::profile('worker', ['QUEUE' => 'string']);
        EnvValidator::profile('cache', ['REDIS_HOST' => 'string']);

        $names = ProfileRegistry::profiles();

        $this->assertCount(3, $names);
        $this->assertContains('web', $names);
        $this->assertContains('worker', $names);
        $this->assertContains('cache', $names);
    }

    public function test_multiple_profiles_can_coexist(): void
    {
        $this->setEnv('TEST_WEB_HOST', 'https://example.com');
        $this->setEnv('TEST_WORKER_QUEUE', 'default');

        EnvValidator::profile('web', ['TEST_WEB_HOST' => 'url']);
        EnvValidator::profile('worker', ['TEST_WORKER_QUEUE' => 'string']);

        $webResult = EnvValidator::validateProfile('web');
        $workerResult = EnvValidator::validateProfile('worker');

        $this->assertTrue($webResult->passed);
        $this->assertTrue($workerResult->passed);
        $this->assertTrue(ProfileRegistry::has('web'));
        $this->assertTrue(ProfileRegistry::has('worker'));
    }
}
