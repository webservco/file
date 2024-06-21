<?php

declare(strict_types=1);

namespace WebServCo\File\Factory\Response;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use WebServCo\File\Contract\FileInterface;

use function gmdate;
use function sprintf;

final class DownloadResponseFactory
{
    public function __construct(private ResponseFactoryInterface $responseFactory)
    {
    }

    public function createDownloadResponse(FileInterface $file): ResponseInterface
    {
        return $this->responseFactory->createResponse(200)
            ->withHeader('Accept-Ranges', 'bytes')
            ->withHeader('Cache-Control', 'public')
            ->withHeader('Connection', 'close')
            ->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Disposition', sprintf('attachment; filename="%s"', $file->getName()))
            ->withHeader('Content-Length', (string) $file->getSize())
            ->withHeader('Content-Transfer-Encoding', 'binary')
            ->withHeader('Content-Type', $file->getContentType())
            //->withHeader('ETag', md5($fileData))
            ->withHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
            ->withBody($file->getData());
    }
}
