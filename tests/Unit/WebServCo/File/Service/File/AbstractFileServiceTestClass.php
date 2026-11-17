<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\File\Service\File;

use Tests\Unit\Assets\AbstractFileTestClass;
use WebServCo\File\Exception\FileException;
use WebServCo\File\Service\File\FileService;

use function file_get_contents;
use function fileperms;
use function is_writable;
use function mkdir;
use function sprintf;

/**
 * The tests of `FileService`. `FileServiceTest` runs them without an error handler of its own,
 * `FileServiceWithStrictErrorHandlerTest` with a strict one.
 */
abstract class AbstractFileServiceTestClass extends AbstractFileTestClass
{
    private const int PERMISSION_BITS = 0o777;

    public function testReadFileReturnsTheData(): void
    {
        $filePath = $this->createFile('data.txt', "line 1\nline 2\n");

        self::assertSame("line 1\nline 2\n", (new FileService())->readFile($filePath));
    }

    public function testReadFileThrowsForAFileThatIsNotThere(): void
    {
        $filePath = $this->getPath('missing.txt');

        $this->expectException(FileException::class);
        $this->expectExceptionMessageIs(sprintf('File is not readable: "%s".', $filePath));

        (new FileService())->readFile($filePath);
    }

    public function testReadFileThrowsForADirectory(): void
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessageIs(sprintf('File is not readable: "%s".', $this->getTemporaryDirectory()));

        (new FileService())->readFile($this->getTemporaryDirectory());
    }

    public function testReadFileIfReadableReturnsTheData(): void
    {
        $filePath = $this->createFile('data.txt', 'data');

        self::assertSame('data', (new FileService())->readFileIfReadable($filePath));
    }

    public function testReadFileIfReadableReturnsNullForAFileThatIsNotThere(): void
    {
        self::assertNull((new FileService())->readFileIfReadable($this->getPath('missing.txt')));
    }

    public function testReadFileIfReadableReturnsNullForADirectory(): void
    {
        self::assertNull((new FileService())->readFileIfReadable($this->getTemporaryDirectory()));
    }

    public function testWriteFileReplacesALongerFile(): void
    {
        $filePath = $this->createFile('data.txt', 'the older and longer data');

        self::assertTrue((new FileService())->writeFile('new data', $filePath));
        self::assertSame('new data', file_get_contents($filePath));
    }

    public function testWriteFileAtomicallyReplacesALongerFile(): void
    {
        $filePath = $this->createFile('data.txt', 'the older and longer data');

        self::assertTrue((new FileService())->writeFileAtomically('new data', $filePath));
        self::assertSame('new data', file_get_contents($filePath));
    }

    public function testWriteFileCreatesTheDirectoriesWithTheGivenMode(): void
    {
        $filePath = $this->getPath('level1/level2/level3/data.txt');

        self::assertTrue((new FileService())->writeFile('data', $filePath, 0o700));
        self::assertSame('data', file_get_contents($filePath));
        $this->assertDirectoryModes(['level1', 'level1/level2', 'level1/level2/level3'], 0o700);
    }

    public function testWriteFileAtomicallyCreatesTheDirectoriesWithTheGivenMode(): void
    {
        $filePath = $this->getPath('level1/level2/level3/data.txt');

        self::assertTrue((new FileService())->writeFileAtomically('data', $filePath, 0o700));
        self::assertSame('data', file_get_contents($filePath));
        $this->assertDirectoryModes(['level1', 'level1/level2', 'level1/level2/level3'], 0o700);
    }

    public function testWriteFileAtomicallyLeavesNoOtherFile(): void
    {
        $fileService = new FileService();
        $filePath = $this->getPath('data.json');

        $fileService->writeFileAtomically('{"version": 1}', $filePath);
        $fileService->writeFileAtomically('{"version": 2}', $filePath);

        self::assertSame('{"version": 2}', file_get_contents($filePath));
        self::assertSame(['data.json'], $fileService->listDirectory($this->getTemporaryDirectory()));
    }

    public function testWriteFileAtomicallyLeavesNoOtherFileAfterAFailure(): void
    {
        $fileService = new FileService();
        $directoryPath = $this->getPath('data.json');
        mkdir($directoryPath);

        try {
            $fileService->writeFileAtomically('data', $directoryPath);
            self::fail('The write did not fail.');
        } catch (FileException $exception) {
            self::assertSame(
                sprintf('Path is a directory, not a file: "%s".', $directoryPath),
                $exception->getMessage(),
            );
        }

        self::assertSame(['data.json'], $fileService->listDirectory($this->getTemporaryDirectory()));
    }

    public function testWriteFileThrowsWhenAFileHasTheNameOfTheDirectory(): void
    {
        $this->createFile('level1', 'a file, not a directory');
        $directory = $this->getPath('level1/level2');

        $this->expectException(FileException::class);
        $this->expectExceptionMessageIs(sprintf('Error creating directory: "%s".', $directory));

        (new FileService())->writeFile('data', sprintf('%s/data.txt', $directory));
    }

    public function testWriteFileAtomicallyThrowsWhenAFileHasTheNameOfTheDirectory(): void
    {
        $this->createFile('level1', 'a file, not a directory');
        $directory = $this->getPath('level1');

        $this->expectException(FileException::class);
        $this->expectExceptionMessageIs(sprintf('Error creating directory: "%s".', $directory));

        (new FileService())->writeFileAtomically('data', sprintf('%s/data.txt', $directory));
    }

    public function testWriteFileThrowsForADirectoryThatCannotBeWrittenTo(): void
    {
        $filePath = sprintf('%s/data.txt', $this->createReadOnlyDirectory());

        $this->expectException(FileException::class);
        $this->expectExceptionMessageIs(sprintf('File is not writable: "%s".', $filePath));

        (new FileService())->writeFile('data', $filePath);
    }

    public function testWriteFileAtomicallyThrowsForADirectoryThatCannotBeWrittenTo(): void
    {
        $filePath = sprintf('%s/data.txt', $this->createReadOnlyDirectory());

        $this->expectException(FileException::class);
        $this->expectExceptionMessageIs(sprintf('File is not writable: "%s".', $filePath));

        (new FileService())->writeFileAtomically('data', $filePath);
    }

    public function testWriteDataToFilePathStillAppends(): void
    {
        $filePath = $this->createFile('data.log', "line 1\n");

        self::assertTrue((new FileService())->writeDataToFilePath("line 2\n", $filePath));
        self::assertSame("line 1\nline 2\n", file_get_contents($filePath));
    }

    public function testListDirectoryReturnsTheNamesSortedWithoutTheDotNames(): void
    {
        $this->createFile('b.txt', 'b');
        $this->createFile('.hidden', 'hidden');
        $this->createFile('a.csv', 'a');
        mkdir($this->getPath('c'));

        self::assertSame(
            ['.hidden', 'a.csv', 'b.txt', 'c'],
            (new FileService())->listDirectory($this->getTemporaryDirectory()),
        );
    }

    public function testListDirectoryReturnsNoNameForAnEmptyDirectory(): void
    {
        self::assertSame([], (new FileService())->listDirectory($this->getTemporaryDirectory()));
    }

    public function testListDirectoryReturnsNoNameForADirectoryThatIsNotThere(): void
    {
        self::assertSame([], (new FileService())->listDirectory($this->getPath('missing')));
    }

    /**
     * @param list<string> $names directories in the temporary directory
     */
    private function assertDirectoryModes(array $names, int $mode): void
    {
        foreach ($names as $name) {
            $permissions = fileperms($this->getPath($name));
            self::assertIsInt($permissions, $name);
            self::assertSame($mode, $permissions & self::PERMISSION_BITS, $name);
        }
    }

    /**
     * An empty directory without write permission. The temporary directory can still remove it.
     */
    private function createReadOnlyDirectory(): string
    {
        $directory = $this->getPath('read-only');
        mkdir($directory, 0o500);
        if (is_writable($directory)) {
            self::markTestSkipped('The tests run as a user who can write to any directory.');
        }

        return $directory;
    }
}
