<?php

declare(strict_types=1);

namespace WebServCo\File\ValueObject;

use Psr\Http\Message\StreamInterface;
use UnexpectedValueException;
use WebServCo\File\Contract\FileInterface;

abstract class AbstractFile implements FileInterface
{
    public function __construct(private string $contentType, private StreamInterface $data, private string $name)
    {
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getData(): StreamInterface
    {
        return $this->data;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSize(): int
    {
        $size = $this->data->getSize();

        if ($size === null) {
            throw new UnexpectedValueException('Unable to get stream size.');
        }

        return $size;
    }
}
