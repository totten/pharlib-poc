<?php

use PHPUnit\Framework\TestCase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PhpSpreadsheetTest extends TestCase {

    public function testSpreadsheetCreateAndLoad(): void {
        $phar = getenv('TEST_PHAR');
        $this->assertNotEmpty($phar, 'TEST_PHAR environment variable must be set');
        $this->assertFileExists($phar);

        // Standard PSR dependencies provided by environment/composer
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }

        $distDir = dirname($phar);
        pathload()->addSearchDir($distDir);
        pathload()->addNamespace('phpspreadsheet@5', ['PhpOffice\\PhpSpreadsheet\\']);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Hello');
        $sheet->setCellValue('B1', 'Pharlib');
        $sheet->setCellValue('A2', 42);

        $tmpFile = sys_get_temp_dir() . '/test_phpspreadsheet_' . uniqid() . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpFile);

        $this->assertFileExists($tmpFile);

        $loadedSpreadsheet = IOFactory::load($tmpFile);
        $loadedSheet = $loadedSpreadsheet->getActiveSheet();

        $this->assertEquals('Hello', $loadedSheet->getCell('A1')->getValue());
        $this->assertEquals('Pharlib', $loadedSheet->getCell('B1')->getValue());
        $this->assertEquals(42, $loadedSheet->getCell('A2')->getValue());

        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }
    }
}
