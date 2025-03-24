<?php

declare(strict_types=1);

namespace WebServCo\File\Service\CSV;

use Iterator;
use RuntimeException;
use UnexpectedValueException;
use WebServCo\File\Contract\Service\CSV\DataCreatorServiceInterface;

use function array_keys;
use function chr;
use function fclose;
use function fopen;
use function fputcsv;
use function fwrite;
use function is_array;
use function is_int;
use function is_resource;
use function is_scalar;
use function is_string;
use function stream_get_contents;
use function strval;

final class DataCreatorService implements DataCreatorServiceInterface
{
    public function __construct(private string $delimiter, private string $enclosure)
    {
    }

    public function createCsvDataFromIterator(Iterator $iterator, bool $useHeaderLine): string
    {
        $filePointerResource = $this->createFilePointerResource();

        $this->addByteOrderMark($filePointerResource);

        // Check for empty data.
        if (!$iterator->valid()) {
            return $this->getStreamContents($filePointerResource);
        }

        // Add header line (if requested).
        $this->handleHeaderLine($filePointerResource, $iterator, $useHeaderLine);

        /**
         * Psalm error: Unable to determine the type that $data is being assigned to (see https://psalm.dev/032)
         * However this is indeed mixed, no solution but to suppress error.
         *
         * @psalm-suppress MixedAssignment
         */
        foreach ($iterator as $data) {
            $this->writeCsvLine($filePointerResource, $this->getProcessedData($data));
        }

        return $this->getStreamContents($filePointerResource);
    }

    /**
     * @param ?resource $filePointerResource
     */
    private function addByteOrderMark(mixed $filePointerResource): bool
    {
        if (!is_resource($filePointerResource)) {
            throw new UnexpectedValueException('Not a valid resource.');
        }

        // Add Byte Order mark (BOM) for UTF-8.
        $result = fwrite($filePointerResource, chr(0xEF) . chr(0xBB) . chr(0xBF));
        if ($result === false) {
            throw new RuntimeException('Error writing data.');
        }

        return true;
    }

    /**
     * Phan: Doc-block of createFilePointerResource has declared return type resource
     * which is not a permitted replacement of the nullable return type mixed declared in the signature
     * ('?T' should be documented as 'T|null' or '?T')
     * However if make nullable, phpstan throws error (correctly, as there is a is_resource check)
     *
     * @return resource
     * @suppress PhanTypeMismatchDeclaredReturnNullable
     */
    private function createFilePointerResource(): mixed
    {
        // temporary file/memory wrapper; if bigger than 5MB will be written to temp file.
        $filePointerResource = fopen('php://temp/maxmemory:' . strval(5 * 1024 * 1024), 'r+');

        if (!is_resource($filePointerResource)) {
            throw new UnexpectedValueException('Not a valid resource.');
        }

        return $filePointerResource;
    }

    /**
     * @return array<int|string, bool|float|int|string|null>
     */
    private function getProcessedData(mixed $data): array
    {
        if (!is_array($data)) {
            throw new UnexpectedValueException('Data is not an array.');
        }

        $result = [];

        /**
         * Psalm MixedAssignment error.
         * However $value is indeed mixed.
         *
         * @psalm-suppress MixedAssignment
         */
        foreach ($data as $key => $value) {
            $result[$this->getProcessedDataKey($key)] = $this->getProcessedDataValue($value);
        }

        return $result;
    }

    private function getProcessedDataKey(mixed $key): int|string
    {
        if (is_string($key) || is_int($key)) {
            return $key;
        }

        throw new UnexpectedValueException('Unexpected key type.');
    }

    private function getProcessedDataValue(mixed $value): bool|float|int|string|null
    {
        if (is_scalar($value) || $value === null) {
            return $value;
        }

        throw new UnexpectedValueException('Unexpected data type.');
    }

    /**
     *  Phan: "PhanTypeMismatchArgumentInternalProbablyReal
     *  Argument 2 ($length) is null of type null but \stream_get_contents() takes int"
     *  As per documentation, default value is null.
     *
     * @param ?resource $filePointerResource
     * @suppress PhanTypeMismatchArgumentInternalProbablyReal
     */
    private function getStreamContents(mixed $filePointerResource): string
    {
        if (!is_resource($filePointerResource)) {
            throw new UnexpectedValueException('Not a valid resource.');
        }

        /**
         * Get data.
         *
         * length: default null
         * "the maximum bytes to read. Defaults to null (read all the remaining buffer)."
         * offset: default -1
         * "Seek to the specified offset before reading.
         * If this number is negative, no seeking will occur and reading will start from the current position."
         * We use 0 to make sure we get all the data.
         * Alternative solution: rewind($filePointerResource);
         */
        $csvData = stream_get_contents($filePointerResource, null, 0);
        if ($csvData === false) {
            throw new RuntimeException('Error getting stream contents');
        }

        fclose($filePointerResource);

        return $csvData;
    }

    /**
     * @param ?resource $filePointerResource
     */
    private function handleHeaderLine(mixed $filePointerResource, Iterator $iterator, bool $useHeaderLine): bool
    {
        if (!is_resource($filePointerResource)) {
            throw new UnexpectedValueException('Not a valid resource.');
        }

        if (!$useHeaderLine) {
            return false;
        }

        $currentData = $iterator->current();

        if (!is_array($currentData)) {
            throw new UnexpectedValueException('Data is not an array.');
        }

        $result = fputcsv($filePointerResource, array_keys($currentData), $this->delimiter, $this->enclosure);

        if ($result === false) {
            throw new RuntimeException('Error writing data.');
        }

        return true;
    }

    /**
     * @param ?resource $filePointerResource
     * @param array<int|string, bool|float|int|string|null> $data
     */
    private function writeCsvLine(mixed $filePointerResource, array $data): bool
    {
        if (!is_resource($filePointerResource)) {
            throw new UnexpectedValueException('Not a valid resource.');
        }

        $result = fputcsv($filePointerResource, $data, $this->delimiter, $this->enclosure);
        if ($result === false) {
            throw new RuntimeException('Error writing data.');
        }

        return true;
    }
}
