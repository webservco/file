# webservco/file

A PHP component/library for working with files.

---

## Factory

### Response\DownloadResponseFactory

```php
public function createDownloadResponse(FileInterface $file): ResponseInterface 
```

### Response\OutputResponseFactory

```php
public function createOutputResponse(FileInterface $file): ResponseInterface
```

### FileFactory

```php
# General
public function createFromPath(string $contentType, string $filePath, string $name): FileInterface; 

# CSV
public function createCSVFromPath(string $filePath, string $name): FileInterface;

# PDF
public function createPdfFromPath(string $filePath, string $name): FileInterface;

```

```php
# General
public function createFromString(string $contentType, string $fileData, string $name): FileInterface;

# CSV
public function createCSVFromString(string $fileData, string $name): FileInterface;

# PDF
public function createPdfFromString(string $fileData, string $name): FileInterface;
```

---

## Service

- use case: database result to CSV.
- to use with a static array: `$iterator = new ArrayIterator($array)`;

### CSV\DataCreatorService

```php
public function createCsvDataFromIterator(Iterator $iterator, bool $useHeaderLine): string;
```

### CSV\FileCreatorService

```php
public function createCsvFileFromIterator(string $fileName, Iterator $iterator, bool $useHeaderLine): CSVFile 
```

---

## Value object

Implement `FileInterface`.

### File

### CSVFile

---


