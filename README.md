# webservco/file

A PHP component/library. Please see [webservco/sandbox](https://webservco.github.io/sandbox/) for an example project using these types of components.

File value objects, plus CSV and ZIP creation and PSR-7 responses for sending a file to the browser.

- Requires: `ext-zip` (for `ZipArchiveService`), a PSR-17 implementation such as `webservco/http`
- Related: `webservco/log` (writes its files with `FileService`)

---

## Installation

```shell
composer require webservco/file
```

---

## Usage

### Download a CSV built from database rows

```php
use WebServCo\File\Factory\Response\DownloadResponseFactory;
use WebServCo\File\Service\CSV\DataCreatorService;
use WebServCo\File\Service\CSV\FileCreatorService;

$fileCreatorService = new FileCreatorService(
    // \WebServCo\File\Contract\Service\CSV\DataCreatorServiceInterface: delimiter, enclosure.
    new DataCreatorService(',', '"'),
    // PSR-17: \Psr\Http\Message\StreamFactoryInterface
    $streamFactory,
);
// $rows: any Iterator, for example a generator over a query, or new ArrayIterator($array).
// true: use the keys of the first row as the header line.
// \WebServCo\File\ValueObject\CSVFile
$file = $fileCreatorService->createCsvFileFromIterator('report.csv', $rows, true);

// PSR-17: \Psr\Http\Message\ResponseFactoryInterface
$downloadResponseFactory = new DownloadResponseFactory($responseFactory);
return $downloadResponseFactory->createDownloadResponse($file);
```

### Show a PDF in the browser

```php
use WebServCo\File\Factory\FileFactory;
use WebServCo\File\Factory\Response\OutputResponseFactory;

$fileFactory = new FileFactory($streamFactory);
// \WebServCo\File\Contract\FileInterface
$file = $fileFactory->createPdfFromPath('/path/to/invoice.pdf', 'invoice.pdf');

$outputResponseFactory = new OutputResponseFactory($responseFactory);
return $outputResponseFactory->createOutputResponse($file);
```

---

## Contracts

| Interface | Implementation | Purpose |
| --- | --- | --- |
| `Contract\FileInterface` | `ValueObject\File`, `ValueObject\CSVFile`, `ValueObject\PdfFile` | Content type, name, size and data stream. |
| `Contract\FileFactoryInterface` | `Factory\FileFactory` | Creates files from a path or a string (generic, CSV, PDF). |
| `Contract\Service\CSV\DataCreatorServiceInterface` | `Service\CSV\DataCreatorService` | Turns an iterator of rows into CSV text. |
| none | `Service\CSV\FileCreatorService` | Turns an iterator of rows into a `CSVFile`. |
| none | `Factory\Response\DownloadResponseFactory` | Response with `Content-Disposition: attachment`. |
| none | `Factory\Response\OutputResponseFactory` | Response that shows the file inline. |
| none | `Service\Archive\ZipArchiveService` | Zips one file into a directory and returns the archive file name (`{name}.zip`). |
| none | `Service\File\FileService` | Creates directories and appends data to a file. |

Namespaces are relative to `WebServCo\File\`.

---

## Notes

CSV data starts with a UTF-8 byte order mark. Excel needs it to show accented characters correctly. The header line comes from the first row's keys. Give every row the same keys.

ZIP archives are compressed with LZMA2. Some unzip tools, including older built-in ones, can't open them.

---

## Development

```shell
# Start project.
ddev start
# Install dependencies.
ddev c u
# Run all validations.
ddev c c
# Run unit tests.
ddev c test:dox
```

---

## License

[MIT](LICENSE.md)
