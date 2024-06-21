<?php

declare(strict_types=1);

namespace WebServCo\File\Contract;

use Psr\Http\Message\StreamInterface;

interface FileInterface
{
    public function getContentType(): string;

    public function getData(): StreamInterface;

    public function getName(): string;

    public function getSize(): int;
}
