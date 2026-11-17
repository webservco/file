<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\File\Service\File;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use WebServCo\File\Exception\FileException;
use WebServCo\File\Service\File\FileService;

/**
 * The tests of `AbstractFileServiceTestClass`, without an error handler of its own.
 */
#[CoversClass(FileException::class)]
#[CoversClass(FileService::class)]
final class FileServiceTest extends AbstractFileServiceTestClass
{
    #[Override]
    protected function useStrictErrorHandler(): bool
    {
        return false;
    }
}
