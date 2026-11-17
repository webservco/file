<?php

declare(strict_types=1);

namespace WebServCo\File\Exception;

use RuntimeException;

/**
 * A file or directory that cannot be read, written or made, or a CSV file that does not have the expected form.
 *
 * The message names the path.
 */
final class FileException extends RuntimeException
{
}
