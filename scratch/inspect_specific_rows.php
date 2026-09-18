<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Services\CustomerImportService;

$excelPath = 'C:\\Users\\MyBook Hype AMD\\Downloads\\Percobaan 2.xlsx';
$sheets = CustomerImportService::parseXlsxSheets($excelPath);
$rows = $sheets[0]['rows'];

$checkRows = [554, 577, 557, 579];

foreach ($checkRows as $rNum) {
    // row 1 is header, so index is rNum - 1
    $data = $rows[$rNum - 1] ?? null;
    echo "Baris #$rNum: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
}
