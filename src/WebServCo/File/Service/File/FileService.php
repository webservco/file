<?php

declare(strict_types=1);

namespace WebServCo\File\Service\File;

use OutOfBoundsException;

use function file_put_contents;
use function is_dir;
use function mkdir;
use function pathinfo;

use const FILE_APPEND;
use const PATHINFO_DIRNAME;

final class FileService
{
    public function createDirectoryIfNotExists(string $directory, int $permissions = 0755): bool
    {
        if (is_dir($directory)) {
            // Directory already exists.
            return true;
        }

        return mkdir($directory, $permissions, true);
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
}
