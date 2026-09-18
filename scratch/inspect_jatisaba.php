<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Services\CustomerImportService;

$excelPath = 'C:\\Users\\MyBook Hype AMD\\Downloads\\Percobaan 2.xlsx';
$sheets = CustomerImportService::parseXlsxSheets($excelPath);
$rows = $sheets[0]['rows'];
$dataRows = array_slice($rows, 1);

echo "=== AROUND ROW 180-185 ===\n";
for ($i = 178; $i <= 185; $i++) {
    $rowNum = $i + 2;
    echo "Row #$rowNum: Name='{$dataRows[$i][1]}' | Addr='{$dataRows[$i][5]}'\n";
}

echo "\n=== AROUND ROW 120-125 ===\n";
for ($i = 118; $i <= 124; $i++) {
    $rowNum = $i + 2;
    echo "Row #$rowNum: Name='{$dataRows[$i][1]}' | Addr='{$dataRows[$i][5]}'\n";
}
