<?php

declare(strict_types=1);

namespace TechNest\Tests\Integration;

use PHPUnit\Framework\TestCase;
use TechNest\Tests\Support\TestDatabase;

/** bin/technest, run as a real process. */
final class CliTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir() . '/technest-cli-' . bin2hex(random_bytes(6)) . '.sqlite';
    }

    protected function tearDown(): void
    {
        foreach (['', '-wal', '-shm'] as $suffix) {
            if (is_file($this->path . $suffix)) {
                unlink($this->path . $suffix);
            }
        }
    }

    public function testHelpNeedsNoDatabase(): void
    {
        [$exit, $output] = $this->technest('help');

        self::assertSame(0, $exit);
        self::assertStringContainsString('db:setup', $output);
        self::assertFileDoesNotExist($this->path);
    }

    public function testAnUnknownCommandFailsWithoutCreatingADatabase(): void
    {
        // Regression: a typo printed the help, exited 0 (so a deploy script
        // carried on) and left an empty SQLite file the app took for set up.
        [$exit, $output] = $this->technest('db:migrte');

        self::assertSame(1, $exit);
        self::assertStringContainsString("unknown command 'db:migrte'", $output);
        self::assertFileDoesNotExist($this->path);
    }

    /** @return array{int, string} exit code and combined output */
    private function technest(string ...$args): array
    {
        $env = ['DB_DRIVER' => 'sqlite', 'DB_PATH' => $this->path, 'APP_ENV' => 'testing'] + getenv();
        $process = proc_open(
            array_values([PHP_BINARY, TestDatabase::root() . '/bin/technest', ...$args]),
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            $env,
        );
        self::assertIsResource($process);
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $output];
    }
}
