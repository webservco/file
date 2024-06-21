<?php

declare(strict_types=1);

namespace WebServCo\File\Contract;

interface FileFactoryInterface
{
    public function createFromPath(string $contentType, string $filePath, string $name): FileInterface;

    public function createFromString(string $contentType, string $fileData, string $name): FileInterface;
}
