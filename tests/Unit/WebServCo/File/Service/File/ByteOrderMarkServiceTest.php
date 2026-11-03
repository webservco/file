<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\File\Service\File;

use ArrayIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;
use WebServCo\File\Service\CSV\DataCreatorService;
use WebServCo\File\Service\File\ByteOrderMarkService;

use function fclose;
use function fgetcsv;
use function fopen;
use function fwrite;
use function is_array;
use function is_resource;
use function rewind;

#[CoversClass(ByteOrderMarkService::class)]
#[UsesClass(DataCreatorService::class)]
final class ByteOrderMarkServiceTest extends TestCase
{
    /**
     * @param array<int,string> $expectedFirstLine
     */
    #[DataProvider('files')]
    public function testFirstLineReadsWithoutTheByteOrderMark(
        string $contents,
        bool $expectedSkipped,
        array $expectedFirstLine,
    ): void {
        $filePointerResource = $this->createFile($contents);

        $byteOrderMarkService = new ByteOrderMarkService();

        self::assertSame($expectedSkipped, $byteOrderMarkService->skipUtf8ByteOrderMark($filePointerResource));
        self::assertSame($expectedFirstLine, $this->readLine($filePointerResource));

        fclose($filePointerResource);
    }

    public function testAnEmptyFileHasNoLine(): void
    {
        $filePointerResource = $this->createFile('');

        $byteOrderMarkService = new ByteOrderMarkService();

        self::assertFalse($byteOrderMarkService->skipUtf8ByteOrderMark($filePointerResource));
        self::assertFalse(fgetcsv($filePointerResource, 0, ',', '"', ''));

        fclose($filePointerResource);
    }

    public function testDataCreatorServiceOutputReadsBackWithoutTheByteOrderMark(): void
    {
        $dataCreatorService = new DataCreatorService(',', '"');
        $filePointerResource = $this->createFile(
            $dataCreatorService->createCsvDataFromIterator(
                new ArrayIterator([['id' => 1, 'name' => 'A, "quoted" name']]),
                true,
            ),
        );

        $byteOrderMarkService = new ByteOrderMarkService();

        self::assertTrue($byteOrderMarkService->skipUtf8ByteOrderMark($filePointerResource));
        self::assertSame(['id', 'name'], $this->readLine($filePointerResource));
        self::assertSame(['1', 'A, "quoted" name'], $this->readLine($filePointerResource));

        fclose($filePointerResource);
    }

    /**
     * @return array<string,array{0:string,1:bool,2:array<int,string>}>
     */
    public static function files(): array
    {
        return [
            'byte order mark' => ["\xEF\xBB\xBF1,foo,\n2,bar,\n", true, ['1', 'foo', '']],
            // Without the skip, `fgetcsv` would not see the quote and would keep it in the value.
            'byte order mark, quoted first field' => ["\xEF\xBB\xBF\"1\",foo\n", true, ['1', 'foo']],
            'no byte order mark' => ["1,foo,\n2,bar,\n", false, ['1', 'foo', '']],
            'only part of a byte order mark' => ["\xEF\xBB1\n", false, ["\xEF\xBB1"]],
            'shorter than a byte order mark' => ["1\n", false, ['1']],
        ];
    }

    /**
     * @return resource
     */
    private function createFile(string $contents)
    {
        $filePointerResource = fopen('php://memory', 'r+');
        if (!is_resource($filePointerResource)) {
            throw new UnexpectedValueException('Not a valid resource.');
        }

        fwrite($filePointerResource, $contents);
        rewind($filePointerResource);

        return $filePointerResource;
    }

    /**
     * @param resource $filePointerResource
     * @return array<int,string|null>
     */
    private function readLine($filePointerResource): array
    {
        $lineData = fgetcsv($filePointerResource, 0, ',', '"', '');
        if (!is_array($lineData)) {
            throw new UnexpectedValueException('No line.');
        }

        return $lineData;
    }
}
