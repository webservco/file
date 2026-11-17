<?php

declare(strict_types=1);

namespace WebServCo\File\Service\CSV;

use WebServCo\File\Exception\FileException;
use WebServCo\File\Service\File\ByteOrderMarkService;

use function array_map;
use function count;
use function fclose;
use function fgetcsv;
use function fopen;
use function implode;
use function is_array;
use function is_file;
use function is_readable;
use function sprintf;
use function strval;

/**
 * Reads a CSV file that starts with a header line.
 */
final class FileReaderService
{
    private const int ANY_LINE_LENGTH = 0;

    /**
     * What `fgetcsv` returns for an empty line.
     */
    private const array EMPTY_LINE = [null];

    /**
     * Warning
     * When escape is set to anything other than an empty string ("") it can result in CSV that is
     * not compliant with » RFC 4180 or unable to survive a roundtrip through the PHP CSV functions.
     * The default for escape is "\\" so it is recommended to set it to the empty string explicitly.
     * The default value will change in a future version of PHP, no earlier than PHP 9.0.
     */
    public function __construct(
        private ByteOrderMarkService $byteOrderMarkService,
        private string $delimiter,
        private string $enclosure,
        private string $escape = '',
    ) {
    }

    /**
     * Skips a UTF-8 byte order mark, checks the header line, and leaves out empty lines.
     *
     * @param list<string> $columns the header line the file must start with
     * @return list<list<string>> the lines after the header line, each with one value for each column
     */
    public function readLines(string $filePath, array $columns): array
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new FileException(sprintf('File is not readable: "%s".', $filePath));
        }

        $filePointerResource = fopen($filePath, 'r');
        if ($filePointerResource === false) {
            throw new FileException(sprintf('File is not readable: "%s".', $filePath));
        }

        try {
            $this->byteOrderMarkService->skipUtf8ByteOrderMark($filePointerResource);
            $this->assertHeaderLine($filePointerResource, $filePath, $columns);

            return $this->readDataLines($filePointerResource, $filePath, count($columns));
        } finally {
            fclose($filePointerResource);
        }
    }

    /**
     * @param resource $filePointerResource
     * @param list<string> $columns
     */
    private function assertHeaderLine($filePointerResource, string $filePath, array $columns): bool
    {
        if ($this->readLine($filePointerResource) === $columns) {
            return true;
        }

        throw new FileException(
            sprintf('File does not start with the columns %s: "%s".', implode(', ', $columns), $filePath),
        );
    }

    /**
     * @param list<?string> $line
     * @return list<string>
     */
    private function createDataLine(array $line, int $lineNumber, string $filePath, int $columnCount): array
    {
        if (count($line) !== $columnCount) {
            throw new FileException(
                sprintf(
                    'Line %d has %d values, not %d: "%s".',
                    $lineNumber,
                    count($line),
                    $columnCount,
                    $filePath,
                ),
            );
        }

        return array_map(strval(...), $line);
    }

    /**
     * @param resource $filePointerResource
     * @return list<list<string>>
     */
    private function readDataLines($filePointerResource, string $filePath, int $columnCount): array
    {
        $lines = [];
        // The header line is line 1.
        $lineNumber = 2;
        $line = $this->readLine($filePointerResource);
        while ($line !== null) {
            if ($line !== self::EMPTY_LINE) {
                $lines[] = $this->createDataLine($line, $lineNumber, $filePath, $columnCount);
            }
            $lineNumber += 1;
            $line = $this->readLine($filePointerResource);
        }

        return $lines;
    }

    /**
     * @param resource $filePointerResource
     * @return ?list<?string> null at the end of the file
     */
    private function readLine($filePointerResource): ?array
    {
        $line = fgetcsv($filePointerResource, self::ANY_LINE_LENGTH, $this->delimiter, $this->enclosure, $this->escape);

        return is_array($line)
            ? $line
            : null;
    }
}
