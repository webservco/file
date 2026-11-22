# webservco/file

A PHP component/library. Please see [webservco/sandbox](https://webservco.github.io/sandbox/) for an example project using these types of components.

File value objects, reading and writing files, CSV creation and reading, ZIP creation, and PSR-7 responses for sending a file to the browser.

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

### Read and write a file

```php
use WebServCo\File\Exception\FileException;
use WebServCo\File\Service\File\FileService;

$fileService = new FileService();

try {
    // Throws when the file does not exist or cannot be read.
    $query = $fileService->readFile('/path/to/query.sql');
    // null when the file does not exist or cannot be read.
    $state = $fileService->readFileIfReadable('/path/to/output/.work/state.json');

    // Writes the file new, and makes its directory first when there is none (here with mode 0775).
    $fileService->writeFile($csvData, '/path/to/output/report.csv', 0775);
    // The same through a temporary file that is renamed at the end: a stopped process never leaves half a file.
    $fileService->writeFileAtomically($jsonData, '/path/to/output/.work/state.json', 0775);
} catch (FileException $exception) {
    // The message names the path, e.g. `File is not readable: "/path/to/query.sql".`
}

// The names in a directory, sorted, without `.` and `..`; none when the directory does not exist.
$names = $fileService->listDirectory('/path/to/output');
```

### Read a CSV file that may start with a byte order mark

```php
use WebServCo\File\Service\CSV\FileReaderService;
use WebServCo\File\Service\File\ByteOrderMarkService;

// Delimiter, enclosure, and escape (the empty text when not given).
$fileReaderService = new FileReaderService(new ByteOrderMarkService(), ',', '"');
// Skips a UTF-8 byte order mark, if there is one, and checks that the first line is these columns.
// list<list<string>>: the lines after the header line, each with one value for each column. Empty lines are left out.
// Throws `FileException` for a file that cannot be read, other columns, or a line with another number of values.
$lines = $fileReaderService->readLines('/path/to/export.csv', ['id', 'name', 'notes']);
```

---

## Contracts

| Interface | Implementation | Purpose |
| --- | --- | --- |
| `Contract\FileInterface` | `ValueObject\File`, `ValueObject\CSVFile`, `ValueObject\PdfFile` | Content type, name, size and data stream. |
| `Contract\FileFactoryInterface` | `Factory\FileFactory` | Creates files from a path or a string (generic, CSV, PDF). |
| `Contract\Service\CSV\DataCreatorServiceInterface` | `Service\CSV\DataCreatorService` | Turns an iterator of rows into CSV text. |
| none | `Service\CSV\FileCreatorService` | Turns an iterator of rows into a `CSVFile`. |
| none | `Service\CSV\FileReaderService` | Reads the lines of a CSV file, after checking its header line. |
| none | `Factory\Response\DownloadResponseFactory` | Response with `Content-Disposition: attachment`. |
| none | `Factory\Response\OutputResponseFactory` | Response that shows the file inline. |
| none | `Service\Archive\ZipArchiveService` | Zips one file into a directory and returns the archive file name (`{name}.zip`). |
| none | `Service\File\FileService` | Reads and writes files (also through a temporary file), lists a directory, creates directories, and appends data to a file. |
| none | `Service\File\ByteOrderMarkService` | Skips a UTF-8 byte order mark at the start of an open file, before reading it. |

Namespaces are relative to `WebServCo\File\`.

---

## Notes

CSV data starts with a UTF-8 byte order mark. Excel needs it to show accented characters correctly. The header line comes from the first row's keys. Give every row the same keys.

`fgetcsv` doesn't skip that byte order mark: it stays in the first value, and a quoted first field keeps its quotes. `FileReaderService` skips it. To read such a file with `fgetcsv` yourself, call `ByteOrderMarkService::skipUtf8ByteOrderMark()` right after opening it. `ByteOrderMarkService::UTF8_BYTE_ORDER_MARK` holds the mark that `DataCreatorService` writes.

`FileService::writeFile()` writes the file new. `FileService::writeDataToFilePath()` appends: `webservco/log` uses it for every log line. Both make the directory of the file when there is none. The mode of a new directory is reduced by the umask, as with `mkdir()`.

`FileReaderService` and the `FileService` methods `readFile`, `writeFile` and `writeFileAtomically` throw `Exception\FileException` (a `RuntimeException`), with the path in the message. They check a path before they use it, so they work the same with and without the error handler of `webservco/error`, which turns every PHP warning into an exception. The line numbers in the messages of `FileReaderService` count CSV lines: a quoted value with a line end inside does not add one.

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
# Run unit tests, with coverage (needs Xdebug, which DDEV leaves off).
ddev xdebug on && ddev c test:dox; ddev xdebug off
```

---

## License

[MIT](LICENSE.md)
