# webservco/file

A PHP component/library for working with files.

---

## Factory

### Response\DownloadResponseFactory

```php
public function createDownloadResponse(FileInterface $file): ResponseInterface 
```

### FileFactory

```php
public function createFromPath(string $contentType, string $filePath, string $name): FileInterface 
```

```php
public function createFromString(string $contentType, string $fileData, string $name): FileInterface 
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


