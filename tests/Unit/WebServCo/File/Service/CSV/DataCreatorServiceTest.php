<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\File\Service\CSV;

use ArrayIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WebServCo\File\Service\CSV\DataCreatorService;

use function assert;
use function sprintf;

use const PHP_EOL;

#[CoversClass(DataCreatorService::class)]
final class DataCreatorServiceTest extends TestCase
{
    private ?DataCreatorService $dataCreatorService = null;

    public function testDataCreatorServiceWithArrayNoHeaderLine(): void
    {
        $this->setUpDataCreatorService();
        assert($this->dataCreatorService instanceof DataCreatorService);

        /**
         * Create an iterator implementation from a simple array.
         * Use ArrayIterator and not ArrayObject: https://stackoverflow.com/a/24224719/14583382
         */
        $iterator = new ArrayIterator([]);

        $expected = sprintf("%s", "﻿");
        $result = $this->dataCreatorService->createCsvDataFromIterator($iterator, true);

        self::assertEquals($expected, $result);
    }

    public function testDataCreatorServiceWithArrayYesHeaderLine(): void
    {
        $this->setUpDataCreatorService();
        assert($this->dataCreatorService instanceof DataCreatorService);

        /**
         * Create an iterator implementation from a simple array.
         * Use ArrayIterator and not ArrayObject: https://stackoverflow.com/a/24224719/14583382
         */
        $iterator = new ArrayIterator([['foo' => 'foo_value1', 'bar' => 'bar_value1']]);

        $expected = sprintf("%sfoo,bar%sfoo_value1,bar_value1%s", "﻿", PHP_EOL, PHP_EOL);
        $result = $this->dataCreatorService->createCsvDataFromIterator($iterator, true);

        self::assertEquals($expected, $result);
    }

    private function setUpDataCreatorService(): bool
    {
        if ($this->dataCreatorService === null) {
            $this->dataCreatorService = new DataCreatorService(',', '"');
        }

        return true;
    }
}
