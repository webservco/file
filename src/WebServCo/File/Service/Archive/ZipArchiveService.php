<?php

declare(strict_types=1);

namespace WebServCo\File\Service\Archive;

use OutOfBoundsException;
use WebServCo\File\Contract\FileInterface;
use ZipArchive;

use function is_dir;
use function is_writable;
use function rtrim;
use function sprintf;

use const DIRECTORY_SEPARATOR;

final class ZipArchiveService
{
    public const string FILE_EXTENSION = 'zip';

    public function createZipArchive(FileInterface $file, string $outputDirectory): string
    {
        if (!is_dir($outputDirectory)) {
            throw new OutOfBoundsException('Invalid output directory.');
        }

        if (!is_writable($outputDirectory)) {
            throw new OutOfBoundsException('Output directory is not writable.');
        }

        $outputDirectory = rtrim($outputDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $outputFileName = sprintf('%s.%s', $file->getName(), self::FILE_EXTENSION);

        $fileContents = $file->getData()->getContents();
        // Important! Otherwise, the stream body contents can not be retrieved later.
        $file->getData()->rewind();

        $zipArchive = $this->openZipArchive($outputDirectory, $outputFileName);
        $zipArchive->addFromString($file->getName(), $fileContents);
        $zipArchive->setCompressionIndex(0, ZipArchive::CM_LZMA2);

        $zipArchive->close();

        return $outputFileName;
    }

    private function getZipArchiveOpenErrorMessage(false|int $errorCode): ?string
    {
        return match ($errorCode) {
            ZipArchive::ER_EXISTS => 'File already exists.',
            ZipArchive::ER_INCONS => 'Zip archive inconsistent.',
            ZipArchive::ER_INVAL => 'Invalid argument.',
            ZipArchive::ER_MEMORY => 'Malloc failure.',
            ZipArchive::ER_NOENT => 'No such file.',
            ZipArchive::ER_NOZIP => 'Not a zip archive.',
            ZipArchive::ER_OPEN => 'Can\'t open file.',
            ZipArchive::ER_READ => 'Read error.',
            ZipArchive::ER_SEEK => 'Seek error.',
            default => null,
        };
    }

    private function openZipArchive(string $outputDirectory, string $outputFileName): ZipArchive
    {
        $zipArchive = new ZipArchive();

        $result = $zipArchive->open(
            sprintf('%s%s', $outputDirectory, $outputFileName),
            /**
             * "When creating a new archive with ZipArchive::CREATE, the file is not actually written to disk
             * until ZipArchive::close() is called. Therefore, errors related to the file system
             * (such as permission denied or a non-existent parent directory) will only be reported when calling
             * ZipArchive::close(), not when calling this method."
             */
            ZipArchive::CREATE,
        );

        if ($result !== true) {
            $zipArchiveOpenErrorMessage = $this->getZipArchiveOpenErrorMessage($result);

            throw new OutOfBoundsException(
                sprintf(
                    'Error initializing archive%s',
                    $zipArchiveOpenErrorMessage !== null
                        ? sprintf(': "%s".', $zipArchiveOpenErrorMessage)
                        : '',
                ),
                $result !== false ? $result : 0,
            );
        }

        return $zipArchive;
    }
}
