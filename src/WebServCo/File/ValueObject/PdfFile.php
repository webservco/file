<?php

declare(strict_types=1);

namespace WebServCo\File\ValueObject;

use Psr\Http\Message\StreamInterface;
use WebServCo\File\Contract\FileInterface;

final class PdfFile extends AbstractFile implements FileInterface
{
    public const string CONTENT_TYPE = 'application/pdf';

    public function __construct(StreamInterface $data, string $name)
    {
        parent::__construct(self::CONTENT_TYPE, $data, $name);
    }
}
