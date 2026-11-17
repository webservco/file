<?php

declare(strict_types=1);

namespace WebServCo\File\Service\File;

use OutOfBoundsException;
use WebServCo\File\Exception\FileException;

use function array_diff;
use function array_values;
use function bin2hex;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function is_file;
use function is_readable;
use function is_writable;
use function mkdir;
use function pathinfo;
use function random_bytes;
use function rename;
use function scandir;
use function sprintf;
use function strlen;
use function unlink;

use const FILE_APPEND;
use const PATHINFO_DIRNAME;

/**
 * Reads and writes files, and makes directories.
 *
 * The methods that throw `FileException` check a path before they use it, so they work the same
 * with and without an error handler that turns PHP warnings into exceptions.
 */
final class FileService
{
    private const array DOT_NAMES = ['.', '..'];
    private const int TEMPORARY_NAME_BYTES = 8;

    public function createDirectoryIfNotExists(string $directory, int $permissions = 0755): bool
    {
        if (is_dir($directory)) {
            // Directory already exists.
            return true;
        }

        return mkdir($directory, $permissions, true);
    }

    /**
     * @return list<string> the names in the directory, sorted, without `.` and `..`;
     *     none when the directory does not exist or cannot be read
     */
    public function listDirectory(string $directory): array
    {
        if (!is_dir($directory) || !is_readable($directory)) {
            return [];
        }

        $names = scandir($directory);
        if ($names === false) {
            return [];
        }

        return array_values(array_diff($names, self::DOT_NAMES));
    }

    public function readFile(string $filePath): string
    {
        return $this->readFileIfReadable($filePath)
            ?? throw new FileException(sprintf('File is not readable: "%s".', $filePath));
    }

    /**
     * @return ?string null when the file does not exist or cannot be read
     */
    public function readFileIfReadable(string $filePath): ?string
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return null;
        }

        $data = file_get_contents($filePath);

        return $data === false
            ? null
            : $data;
    }

    /**
     * Writes data to file.
     *
     * If directory structure does not exist, it attempts to create it.
     */
    public function writeDataToFilePath(string $data, string $filePath): bool
    {
        $directory = pathinfo($filePath, PATHINFO_DIRNAME);
        $dirResult = $this->createDirectoryIfNotExists($directory);
        if ($dirResult === false) {
            throw new OutOfBoundsException('Error creating log directory.');
        }

        $fileResult = file_put_contents($filePath, $data, FILE_APPEND);
        if ($fileResult === false) {
            throw new OutOfBoundsException('Error writing log file.');
        }

        return true;
    }

    /**
     * Writes data to file, in place of what the file holds (`writeDataToFilePath` appends).
     *
     * If the directory of the file does not exist, it is created with `$directoryPermissions`.
     */
    public function writeFile(string $data, string $filePath, int $directoryPermissions = 0755): bool
    {
        $directory = $this->createDirectoryOfFile($filePath, $directoryPermissions);
        $this->assertWritable(is_file($filePath) ? $filePath : $directory, $filePath);

        return $this->putData($data, $filePath);
    }

    /**
     * Like `writeFile`, through a temporary file in the same directory that is renamed to the file at the end.
     *
     * A process that stops before the end leaves the file as it was, never a part of the new data.
     */
    public function writeFileAtomically(string $data, string $filePath, int $directoryPermissions = 0755): bool
    {
        $directory = $this->createDirectoryOfFile($filePath, $directoryPermissions);
        $this->assertWritable($directory, $filePath);

        $temporaryFilePath = sprintf('%s.%s.tmp', $filePath, bin2hex(random_bytes(self::TEMPORARY_NAME_BYTES)));
        try {
            $this->putData($data, $temporaryFilePath);
            $this->renameFile($temporaryFilePath, $filePath);
        } finally {
            $this->removeFileIfExists($temporaryFilePath);
        }

        return true;
    }

    /**
     * The nearest path that exists must be a directory that can be written to.
     */
    private function assertDirectoryCanBeCreated(string $directory): bool
    {
        $existingPath = $directory;
        while (!file_exists($existingPath) && dirname($existingPath) !== $existingPath) {
            $existingPath = dirname($existingPath);
        }

        if (!is_dir($existingPath) || !is_writable($existingPath)) {
            throw new FileException(sprintf('Error creating directory: "%s".', $directory));
        }

        return true;
    }

    private function assertWritable(string $path, string $filePath): bool
    {
        if (!is_writable($path)) {
            throw new FileException(sprintf('File is not writable: "%s".', $filePath));
        }

        return true;
    }

    /**
     * @return string the directory of the file
     */
    private function createDirectoryOfFile(string $filePath, int $directoryPermissions): string
    {
        if (is_dir($filePath)) {
            throw new FileException(sprintf('Path is a directory, not a file: "%s".', $filePath));
        }

        $directory = dirname($filePath);
        if (is_dir($directory)) {
            return $directory;
        }

        $this->assertDirectoryCanBeCreated($directory);
        if (!mkdir($directory, $directoryPermissions, true)) {
            throw new FileException(sprintf('Error creating directory: "%s".', $directory));
        }

        return $directory;
    }

    private function putData(string $data, string $filePath): bool
    {
        if (file_put_contents($filePath, $data) !== strlen($data)) {
            throw new FileException(sprintf('Error writing file: "%s".', $filePath));
        }

        return true;
    }

    private function removeFileIfExists(string $filePath): bool
    {
        if (!is_file($filePath)) {
            return false;
        }

        return unlink($filePath);
    }

    private function renameFile(string $fromFilePath, string $toFilePath): bool
    {
        if (!rename($fromFilePath, $toFilePath)) {
            throw new FileException(sprintf('Error renaming file "%s" to "%s".', $fromFilePath, $toFilePath));
        }

        return true;
    }
}
