<?php

declare(strict_types=1);

namespace WebServCo\File\ValueObject;

use Override;
use Psr\Http\Message\StreamInterface;
use UnexpectedValueException;
use WebServCo\File\Contract\FileInterface;

abstract class AbstractFile implements FileInterface
{
    public function __construct(private string $contentType, private StreamInterface $data, private string $name)
    {
    }

    #[Override]
    public function getContentType(): string
    {
        return $this->contentType;
    }

    #[Override]
    public function getData(): StreamInterface
    {
        return $this->data;
    }

    #[Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[Override]
    public function getSize(): int
    {
        $size = $this->data->getSize();

        if ($size === null) {
            throw new UnexpectedValueException('Unable to get stream size.');
        }

        return $size;
    }
}
