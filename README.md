# pharlib

This project builds *.phar archives for common PHP libraries, such PSR Log, PHPSpreadsheet, and Guzzle.

> ___This is a quick sketch of a project. It was built in about an hour with AI assistance (but uses existing pathload-poc). The point is to show how much easier it is getting to reshape build/distribution.___

## Project Layout

| Path | Description
| -- | --
| `bin/pharlib` | Control script
| `lib/psr-log-3/` | Rules for building psr/log v3.x
| `lib/phpspreadsheet-5.8/` | Rules for building PHPSpreadsheet v5.8.x
| `lib/phpspreadsheet-5.9/` | Rules for building PHPSpreadsheet v5.9.x
| `lib/guzzle-7/` | Rules for building Guzzle 7.x
| `dist/PACKAGE@VERSION.TIMESTAMP.phar` | Generated artifacts
| `dist/psr-log@3.0.0.202610101234.phar` | Generated artifacts
| `dist/phpspreadsheet@5.8.1.2026101014321.phar` | Generated artifacts
| `inspiration/` | Interesting snippets of code from similar efforts. These are not authoritative, but they may provide pointers+ideas.

## Building libraries

Use the command `./bin/pharlib` to prepare a new build.

```
git clone https://.../pharlib.git
export PATH=$PWD/pharlib/bin:$PATH

cd pharlib/lib/phpspreadsheet-5.8
pharlib clean compile test
```

## Using libraries

Here is an example of using a library:

```php
use PhpOffice\PhpSpreadsheet\IOFactory;

require_once `dist/pathload-0.php`;
pathload()->addSearchDir(__DIR__ . '/dist');
pathload()->addNamespace('phpspreadsheet@5', ['PhpOffice\\PhpSpreadsheet\\']);

$spreadsheet = IOFactory::load('path/to/your/file.xlsx');
$sheet = $spreadsheet->getActiveSheet();
foreach ($sheet->getRowIterator() as $row) {
    $cellIterator = $row->getCellIterator();
    $cellIterator->setIterateOnlyExistingCells(false);
    foreach ($cellIterator as $cell) {
        echo $cell->getValue() . "\t";
    }
    echo "\n";
}
```

## Technical Details

* Work Environment: To run a command with a specific version of PHP, call `use-bknix`:

    ```
    # Formula
    use-bknix <PROFILE> -r <COMMAND>
    
    # Example
    export TEST_PHAR=./dist/phpspreadsheet-5.8.1.202610201234.phar
    use-bknix php83 -r phpunit9
    ```

* PHAR files are build with `box` and `php-scoper`. In this iteration, prefixing follows this principle:

    _Let the marquee packages through in the main namespace; and subordinate all namespaces for transitive dependencies_.

    In particular, that means:

    * The primary namespace is NOT prefixed. (For example, in `phpspreadsheet@5`, the namespace is `PhpOffice\PhpSpreadsheet\`.)
    * The PSR namespace `Psr\` is NEVER prefixed.
    * The PSR packages are NEVER included as transitive dependencies. (They are only standalone libraries.)
    * All other transitive dependencies are included. They use a random prefix.

* Each library should follow a similar structure. Typical items in a library folder:

    * `download.sh`: A script which calls `composer init`, `composer config`, and `composer require` to build a working copy.
    * `box.json`: Rules for building PHAR
    * `scoper.inc.php`: Rules for namespace-prefixing in the PHAR.
    * `tests/**.php`: Integration tests that use a PHAR file. For example, in `phpspreadsheet-5.8`, the test might write+read a simple XLSX file.
    * `phpunit.xml.dist`: 
