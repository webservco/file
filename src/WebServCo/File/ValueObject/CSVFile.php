<?php

declare(strict_types=1);

namespace WebServCo\File\ValueObject;

use Psr\Http\Message\StreamInterface;
use WebServCo\File\Contract\FileInterface;

final class CSVFile extends AbstractFile implements FileInterface
{
    private string $contentType = 'text/csv';

    public function __construct(StreamInterface $data, string $name)
    {
        parent::__construct($this->contentType, $data, $name);
    }
}
