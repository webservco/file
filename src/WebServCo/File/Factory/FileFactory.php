<?php

declare(strict_types=1);

namespace WebServCo\File\Factory;

use OutOfBoundsException;
use Psr\Http\Message\StreamFactoryInterface;
use WebServCo\File\Contract\FileFactoryInterface;
use WebServCo\File\Contract\FileInterface;
use WebServCo\File\ValueObject\File;

use function is_readable;

final class FileFactory implements FileFactoryInterface
{
    public function __construct(private StreamFactoryInterface $streamFactory)
    {
    }

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

    public function createFromString(string $contentType, string $fileData, string $name): FileInterface
    {
        return new File(
            $contentType,
            $this->streamFactory->createStream($fileData),
            $name,
        );
    }
}
