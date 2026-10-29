<?php

## Quick and dirty example

use PhpOffice\PhpSpreadsheet\IOFactory;

################################################################
## Boot
require_once dirname(__DIR__) . '/dist/pathload-0.php';
pathload()->addSearchDir(dirname(__DIR__) . '/dist');
pathload()->addNamespace('psr-simple-cache@3', ['Psr\\SimpleCache\\']);
pathload()->addNamespace('phpspreadsheet@5', ['PhpOffice\\PhpSpreadsheet\\']);

###############################################################
## Logic
$spreadsheet = IOFactory::load(__DIR__ . '/hello.csv');
$sheet = $spreadsheet->getActiveSheet();
foreach ($sheet->getRowIterator() as $row) {
    $cellIterator = $row->getCellIterator();
    $cellIterator->setIterateOnlyExistingCells(false);
    foreach ($cellIterator as $cell) {
        echo $cell->getValue() . "\t";
    }
    echo "\n";
}
