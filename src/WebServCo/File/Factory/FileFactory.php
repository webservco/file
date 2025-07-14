<?php

declare(strict_types=1);

namespace WebServCo\File\Factory;

use OutOfBoundsException;
use Override;
use Psr\Http\Message\StreamFactoryInterface;
use WebServCo\File\Contract\FileFactoryInterface;
use WebServCo\File\Contract\FileInterface;
use WebServCo\File\ValueObject\CSVFile;
use WebServCo\File\ValueObject\File;
use WebServCo\File\ValueObject\PdfFile;

use function is_readable;

final class FileFactory implements FileFactoryInterface
{
    public function __construct(private StreamFactoryInterface $streamFactory)
    {
    }

    #[Override]
    public function createCSVFromPath(string $filePath, string $name): FileInterface
    {
        if (!is_readable($filePath)) {
            throw new OutOfBoundsException('File path is not readable.');
        }

        return new CSVFile($this->streamFactory->createStreamFromFile($filePath), $name);
    }

    #[Override]
    public function createCSVFromString(string $fileData, string $name): FileInterface
    {
        return new CSVFile($this->streamFactory->createStream($fileData), $name);
    }

    #[Override]
    public function createFromPath(string $contentType, string $filePath, string $name): FileInterface
    {
        if (!is_readable($filePath)) {
            throw new OutOfBoundsException('File path is not readable.');
        }

        return new File(
            $contentType,
            $this->streamFactory->createStreamFromFile($filePath),
            $name,
        );
    }

    #[Override]
    public function createFromString(string $contentType, string $fileData, string $name): FileInterface
    {
        return new File(
            $contentType,
            $this->streamFactory->createStream($fileData),
            $name,
        );
    }

    #[Override]
    public function createPdfFromPath(string $filePath, string $name): FileInterface
    {
        if (!is_readable($filePath)) {
            throw new OutOfBoundsException('File path is not readable.');
        }

        return new PdfFile($this->streamFactory->createStreamFromFile($filePath), $name);
    }

    #[Override]
    public function createPdfFromString(string $fileData, string $name): FileInterface
    {
        return new PdfFile($this->streamFactory->createStream($fileData), $name);
    }
}
