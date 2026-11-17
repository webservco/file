<?php

declare(strict_types=1);

namespace Tests\Unit\Assets;

use ErrorException;
use Override;
use PHPUnit\Framework\TestCase;

use function array_diff;
use function bin2hex;
use function file_put_contents;
use function is_dir;
use function is_link;
use function mkdir;
use function random_bytes;
use function restore_error_handler;
use function rmdir;
use function scandir;
use function set_error_handler;
use function sprintf;
use function sys_get_temp_dir;
use function unlink;

/**
 * Base of the tests that work with real files.
 *
 * Every test gets its own temporary directory, removed after the test.
 * A test class runs either without an error handler of its own, or with a strict one that turns every PHP error
 * into an `ErrorException`, as `webservco/error` does in an application. The results must be the same.
 */
abstract class AbstractFileTestClass extends TestCase
{
    private bool $errorHandlerIsSet = false;
    private string $temporaryDirectory = '';

    abstract protected function useStrictErrorHandler(): bool;

    protected function createFile(string $name, string $data): string
    {
        $filePath = $this->getPath($name);
        file_put_contents($filePath, $data);

        return $filePath;
    }

    protected function getPath(string $name): string
    {
        return sprintf('%s/%s', $this->temporaryDirectory, $name);
    }

    protected function getTemporaryDirectory(): string
    {
        return $this->temporaryDirectory;
    }

    #[Override]
    protected function setUp(): void
    {
        $this->temporaryDirectory = sprintf('%s/webservco-file-test-%s', sys_get_temp_dir(), bin2hex(random_bytes(4)));
        mkdir($this->temporaryDirectory, 0o700);

        if (!$this->useStrictErrorHandler()) {
            return;
        }

        set_error_handler(
            static function (int $severity, string $message, string $file, int $line): bool {
                throw new ErrorException($message, 0, $severity, $file, $line);
            },
        );
        $this->errorHandlerIsSet = true;
    }

    #[Override]
    protected function tearDown(): void
    {
        if ($this->errorHandlerIsSet) {
            restore_error_handler();
            $this->errorHandlerIsSet = false;
        }

        $this->removeDirectory($this->temporaryDirectory);
    }

    private function removeDirectory(string $directory): bool
    {
        $names = scandir($directory);
        foreach (array_diff($names === false ? [] : $names, ['.', '..']) as $name) {
            $path = sprintf('%s/%s', $directory, $name);
            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory($path);

                continue;
            }
            unlink($path);
        }

        return rmdir($directory);
    }
}
