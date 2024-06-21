<?php

declare(strict_types=1);

namespace WebServCo\File\Contract\Service\CSV;

use Iterator;

interface DataCreatorServiceInterface
{
    public function createCsvDataFromIterator(Iterator $iterator, bool $useHeaderLine): string;
}
