<?php

declare(strict_types=1);

namespace WebServCo\File\Service\CSV;

use Iterator;
use Psr\Http\Message\StreamFactoryInterface;
use WebServCo\File\Contract\Service\CSV\DataCreatorServiceInterface;
use WebServCo\File\ValueObject\CSVFile;

final class FileCreatorService
{
    public function __construct(
        private DataCreatorServiceInterface $dataCreatorService,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    public function createCsvFileFromIterator(string $fileName, Iterator $iterator, bool $useHeaderLine): CSVFile
    {
        return new CSVFile(
            $this->streamFactory->createStream(
                $this->dataCreatorService->createCsvDataFromIterator($iterator, $useHeaderLine),
            ),
            $fileName,
        );
    }
}
