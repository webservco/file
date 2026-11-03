<?php

declare(strict_types=1);

namespace WebServCo\File\Service\File;

use UnexpectedValueException;

use function fread;
use function rewind;
use function strlen;

/**
 * The UTF-8 byte order mark. `DataCreatorService` starts CSV data with it, and so do spreadsheets.
 * `fgetcsv` keeps it as part of the first value, so a reader skips it before reading the first line.
 */
final class ByteOrderMarkService
{
    public const string UTF8_BYTE_ORDER_MARK = "\xEF\xBB\xBF";

    /**
     * Call right after opening the file. Without a byte order mark, the file is read from its start, as before.
     *
     * @param resource $filePointerResource
     * @return bool true if a byte order mark was skipped
     */
    public function skipUtf8ByteOrderMark($filePointerResource): bool
    {
        if (fread($filePointerResource, strlen(self::UTF8_BYTE_ORDER_MARK)) === self::UTF8_BYTE_ORDER_MARK) {
            return true;
        }

        if (!rewind($filePointerResource)) {
            throw new UnexpectedValueException('Error rewinding file.');
        }

        return false;
    }
}
