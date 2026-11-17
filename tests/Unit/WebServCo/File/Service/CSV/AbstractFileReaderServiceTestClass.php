<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\File\Service\CSV;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Unit\Assets\AbstractFileTestClass;
use WebServCo\File\Exception\FileException;
use WebServCo\File\Service\CSV\FileReaderService;
use WebServCo\File\Service\File\ByteOrderMarkService;

use function sprintf;

/**
 * The tests of `FileReaderService`. `FileReaderServiceTest` runs them without an error handler of its own,
 * `FileReaderServiceWithStrictErrorHandlerTest` with a strict one.
 */
abstract class AbstractFileReaderServiceTestClass extends AbstractFileTestClass
{
    private const array COLUMNS = ['id', 'name', 'notes'];
    private const string OTHER_COLUMNS_MESSAGE = 'File does not start with the columns id, name, notes: "%s".';

    /**
     * @param list<list<string>> $expectedLines
     */
    #[DataProvider('files')]
    public function testReadLinesReturnsTheLinesAfterTheHeaderLine(string $data, array $expectedLines): void
    {
        $filePath = $this->createFile('data.csv', $data);

        self::assertSame($expectedLines, $this->createFileReaderService()->readLines($filePath, self::COLUMNS));
    }

    public function testReadLinesThrowsForOtherColumns(): void
    {
        $filePath = $this->createFile('data.csv', "id,title,notes\n1,foo,\n");

        $this->expectException(FileException::class);
        $this->expectExceptionMessageIs(sprintf(self::OTHER_COLUMNS_MESSAGE, $filePath));

        $this->createFileReaderService()->readLines($filePath, self::COLUMNS);
    }

    public function testReadLinesThrowsForAnEmptyFile(): void
    {
        $filePath = $this->createFile('data.csv', '');

        $this->expectException(FileException::class);
        $this->expectExceptionMessageIs(sprintf(self::OTHER_COLUMNS_MESSAGE, $filePath));

        $this->createFileReaderService()->readLines($filePath, self::COLUMNS);
    }

    public function testReadLinesThrowsForALineWithOneValueTooFew(): void
    {
        $filePath = $this->createFile('data.csv', "id,name,notes\n1,foo,\n\n2,bar\n");

        $this->expectException(FileException::class);
        $this->expectExceptionMessageIs(sprintf('Line 4 has 2 values, not 3: "%s".', $filePath));

        $this->createFileReaderService()->readLines($filePath, self::COLUMNS);
    }

    public function testReadLinesThrowsForAFileThatIsNotThere(): void
    {
        $filePath = $this->getPath('missing.csv');

        $this->expectException(FileException::class);
        $this->expectExceptionMessageIs(sprintf('File is not readable: "%s".', $filePath));

        $this->createFileReaderService()->readLines($filePath, self::COLUMNS);
    }

    public function testReadLinesThrowsForADirectory(): void
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessageIs(sprintf('File is not readable: "%s".', $this->getTemporaryDirectory()));

        $this->createFileReaderService()->readLines($this->getTemporaryDirectory(), self::COLUMNS);
    }

    /**
     * @return array<string,array{0:string,1:list<list<string>>}>
     */
    public static function files(): array
    {
        $lines = [['1', 'foo', 'a note'], ['2', 'bar', '']];

        return [
            'byte order mark' => ["\xEF\xBB\xBFid,name,notes\n1,foo,\"a note\"\n2,bar,\n", $lines],
            'byte order mark, quoted header' => [
                "\xEF\xBB\xBF\"id\",\"name\",\"notes\"\n1,foo,a note\n2,bar,\n",
                $lines,
            ],
            'CRLF line ends' => ["id,name,notes\r\n1,foo,\"a note\"\r\n2,bar,\r\n", $lines],
            'empty lines in the middle and at the end' => ["id,name,notes\n\n1,foo,\"a note\"\n\n2,bar,\n\n\n", $lines],
            'empty values' => ["id,name,notes\n,,\n\"\",\"\",\"\"\n", [['', '', ''], ['', '', '']]],
            'no byte order mark' => ["id,name,notes\n1,foo,\"a note\"\n2,bar,\n", $lines],
            'no line end at the end' => ["id,name,notes\n1,foo,\"a note\"\n2,bar,", $lines],
            'only the header line' => ["id,name,notes\n", []],
            'quoted comma, quote and line end' => [
                "id,name,notes\n1,\"foo, bar\",\"a \"\"quoted\"\" note\non two lines\"\n",
                [['1', 'foo, bar', "a \"quoted\" note\non two lines"]],
            ],
        ];
    }

    private function createFileReaderService(): FileReaderService
    {
        return new FileReaderService(new ByteOrderMarkService(), ',', '"');
    }
}
