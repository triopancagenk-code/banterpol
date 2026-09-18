<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\CustomerImportService;
use App\Models\Order;

$excelPath = 'C:\\Users\\MyBook Hype AMD\\Downloads\\Percobaan 2.xlsx';
$sheets = CustomerImportService::parseXlsxSheets($excelPath);
$rows = $sheets[0]['rows'];
$header = $rows[0];
$fieldMap = CustomerImportService::mapHeaders($header);

$dataRows = array_slice($rows, 1);

// Let's track:
// 1. Duplicates within Excel itself (same NIK, same Name+Phone, same Name+Address)
// 2. Matching with DB Orders

$seenNik = [];
$seenPhone = [];
$seenNameAddress = [];

$internalDuplicates = [];

foreach ($dataRows as $idx => $r) {
    $excelRowNum = $idx + 2; // 1-based, header is 1
    $nik = trim($r[0] ?? '');
    $nikClean = preg_replace('/[^0-9]/', '', $nik);
    $name = trim($r[1] ?? '');
    $phone = trim($r[2] ?? '');
    $address = trim($r[5] ?? '');

    $dupReasons = [];

    if (!empty($nikClean) && strlen($nikClean) >= 10) {
        $nikKey = $nikClean . '_' . strtolower($name);
        if (isset($seenNik[$nikKey])) {
            $dupReasons[] = "Duplikat NIK + Nama dengan baris #" . $seenNik[$nikKey];
        } else {
            $seenNik[$nikKey] = $excelRowNum;
        }
    }

    if (!empty($phone) && $phone !== '-') {
        $phoneKey = $phone . '_' . strtolower($name);
        if (isset($seenPhone[$phoneKey])) {
            $dupReasons[] = "Duplikat Phone + Nama dengan baris #" . $seenPhone[$phoneKey];
        } else {
            $seenPhone[$phoneKey] = $excelRowNum;
        }
    }

    if (!empty($name) && !empty($address) && $address !== '-') {
        $nameAddrKey = strtolower($name) . '_' . strtolower($address);
        if (isset($seenNameAddress[$nameAddrKey])) {
            $dupReasons[] = "Duplikat Nama + Alamat dengan baris #" . $seenNameAddress[$nameAddrKey];
        } else {
            $seenNameAddress[$nameAddrKey] = $excelRowNum;
        }
    }

    if (!empty($dupReasons)) {
        $internalDuplicates[] = [
            'row' => $excelRowNum,
            'name' => $name,
            'nik' => $nik,
            'phone' => $phone,
            'address' => $address,
            'reasons' => $dupReasons,
        ];
    }
}

echo "=== INTERNAL DUPLICATES IN EXCEL (" . count($internalDuplicates) . ") ===\n";
foreach ($internalDuplicates as $dup) {
    echo "Baris #{$dup['row']}: {$dup['name']} (NIK: {$dup['nik']}, HP: {$dup['phone']}, Alamat: {$dup['address']})\n";
    echo "  Alasan: " . implode(', ', $dup['reasons']) . "\n";
}

// Now let's simulate the exact import loop from CustomerImportService
// to see how many would be imported, updated, skipped
echo "\n=== SIMULASI IMPORT DARI AWAL (DATABASE KOSONG) ===\n";
$importedCount = 0;
$updatedCount = 0;
$skippedCount = 0;
$logEvents = [];

$simOrdersByNikAndName = [];
$simOrdersByPhoneAndName = [];
$simOrdersByNameAndAddress = [];

foreach ($dataRows as $idx => $row) {
    $excelRowNum = $idx + 2;
    $nikRaw = $row[0] ?? '';
    if (is_numeric($nikRaw) && preg_match('/[eE]/i', (string)$nikRaw)) {
        $nikRaw = sprintf('%.0f', (float)$nikRaw);
    }
    $nik = preg_replace('/[^0-9]/', '', (string)$nikRaw);
    $nik = (!empty($nik) && $nik !== '0') ? $nik : null;

    $name = trim($row[1] ?? '');
    $phone = trim($row[2] ?? '');
    if (!empty($phone) && $phone !== '-') {
        if (is_numeric($phone) && preg_match('/[eE]/i', (string)$phone)) {
            $phone = sprintf('%.0f', (float)$phone);
        }
        $phone = preg_replace('/[^0-9+]/', '', (string)$phone);
        if (str_starts_with($phone, '62')) {
            $phone = '0' . substr($phone, 2);
        } elseif (str_starts_with($phone, '8')) {
            $phone = '0' . $phone;
        }
        $phone = !empty($phone) ? $phone : '-';
    } else {
        $phone = '-';
    }

    $address = trim($row[5] ?? '');

    if (empty($name) && empty($address) && empty($nik) && ($phone === '-' || empty($phone))) {
        $skippedCount++;
        $logEvents[] = "Baris #{$excelRowNum}: Baris kosong dilewati.";
        continue;
    }

    $existingId = null;
    $matchReason = '';
    if (!empty($nik) && strlen($nik) >= 10) {
        $nikKey = $nik . '_' . strtolower(trim($name));
        if (isset($simOrdersByNikAndName[$nikKey])) {
            $existingId = $simOrdersByNikAndName[$nikKey];
            $matchReason = "NIK + Nama sama dengan baris #{$existingId}";
        }
    }
    if (!$existingId && $phone !== '-') {
        $phoneKey = $phone . '_' . strtolower(trim($name));
        if (isset($simOrdersByPhoneAndName[$phoneKey])) {
            $existingId = $simOrdersByPhoneAndName[$phoneKey];
            $matchReason = "Phone + Nama sama dengan baris #{$existingId}";
        }
    }
    if (!$existingId && !empty($name) && $name !== '-') {
        $nameKey = strtolower(trim($name)) . '_' . strtolower(trim($address));
        if (isset($simOrdersByNameAndAddress[$nameKey])) {
            $existingId = $simOrdersByNameAndAddress[$nameKey];
            $matchReason = "Nama + Alamat sama dengan baris #{$existingId}";
        }
    }

    if ($existingId) {
        $updatedCount++;
        $logEvents[] = "Baris #{$excelRowNum} ({$name}): Ditimpa/Update karena {$matchReason}";
    } else {
        $importedCount++;
        if (!empty($nik)) {
            $simOrdersByNikAndName[$nik . '_' . strtolower(trim($name))] = $excelRowNum;
        }
        if ($phone !== '-') {
            $simOrdersByPhoneAndName[$phone . '_' . strtolower(trim($name))] = $excelRowNum;
        }
        if (!empty($name) && !empty($address) && $address !== '-') {
            $simOrdersByNameAndAddress[strtolower(trim($name)) . '_' . strtolower(trim($address))] = $excelRowNum;
        }
    }
}

echo "Hasil Simulasi:\n";
echo "Imported (Baru): {$importedCount}\n";
echo "Updated (Ditimpa/Duplikat): {$updatedCount}\n";
echo "Skipped: {$skippedCount}\n";
echo "Total yang masuk DB: {$importedCount}\n";
echo "\nDetail Log Update/Skip:\n";
foreach ($logEvents as $ev) {
    echo "  - $ev\n";
}
