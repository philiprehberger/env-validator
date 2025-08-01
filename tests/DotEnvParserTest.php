<?php

declare(strict_types=1);

namespace PhilipRehberger\EnvValidator\Tests;

use PhilipRehberger\EnvValidator\DotEnvParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DotEnvParserTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/env-validator-tests-'.uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tmpDir.'/{,.}*', GLOB_BRACE);

        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        if (is_dir($this->tmpDir)) {
            rmdir($this->tmpDir);
        }
    }

    private function createEnvFile(string $content): string
    {
        $path = $this->tmpDir.'/.env';
        file_put_contents($path, $content);

        return $path;
    }

    public function test_parses_simple_key_value_pairs(): void
    {
        $path = $this->createEnvFile("APP_NAME=my-app\nAPP_PORT=3000\n");

        $result = DotEnvParser::parse($path);

        $this->assertSame('my-app', $result['APP_NAME']);
        $this->assertSame('3000', $result['APP_PORT']);
    }

    public function test_parses_double_quoted_values(): void
    {
        $path = $this->createEnvFile("APP_NAME=\"my app\"\nAPP_KEY=\"secret key\"\n");

        $result = DotEnvParser::parse($path);

        $this->assertSame('my app', $result['APP_NAME']);
        $this->assertSame('secret key', $result['APP_KEY']);
    }

    public function test_parses_single_quoted_values(): void
    {
        $path = $this->createEnvFile("APP_NAME='my app'\nAPP_KEY='secret key'\n");

        $result = DotEnvParser::parse($path);

        $this->assertSame('my app', $result['APP_NAME']);
        $this->assertSame('secret key', $result['APP_KEY']);
    }

    public function test_parses_comments_and_empty_lines(): void
    {
        $content = "# This is a comment\n\nAPP_NAME=my-app\n# Another comment\nAPP_PORT=3000\n";
        $path = $this->createEnvFile($content);

        $result = DotEnvParser::parse($path);

        $this->assertCount(2, $result);
        $this->assertSame('my-app', $result['APP_NAME']);
        $this->assertSame('3000', $result['APP_PORT']);
    }

    public function test_parses_with_export_prefix(): void
    {
        $path = $this->createEnvFile("export APP_NAME=my-app\nexport APP_PORT=3000\n");

        $result = DotEnvParser::parse($path);

        $this->assertSame('my-app', $result['APP_NAME']);
        $this->assertSame('3000', $result['APP_PORT']);
    }

    public function test_throws_on_nonexistent_file(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/File not found/');

        DotEnvParser::parse('/nonexistent/path/.env');
    }

    public function test_parses_variable_interpolation(): void
    {
        $content = "BASE_URL=https://example.com\nAPI_URL=\${BASE_URL}/api\n";
        $path = $this->createEnvFile($content);

        $result = DotEnvParser::parse($path);

        $this->assertSame('https://example.com', $result['BASE_URL']);
        $this->assertSame('https://example.com/api', $result['API_URL']);
    }
}
