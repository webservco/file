<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\File\Service\CSV;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use WebServCo\File\Exception\FileException;
use WebServCo\File\Service\CSV\FileReaderService;
use WebServCo\File\Service\File\ByteOrderMarkService;

/**
 * The tests of `AbstractFileReaderServiceTestClass`,
 * with an error handler that turns every PHP error into an exception.
 */
#[CoversClass(FileReaderService::class)]
#[UsesClass(ByteOrderMarkService::class)]
#[UsesClass(FileException::class)]
final class FileReaderServiceWithStrictErrorHandlerTest extends AbstractFileReaderServiceTestClass
{
    #[Override]
    protected function useStrictErrorHandler(): bool
    {
        return true;
    }
}
