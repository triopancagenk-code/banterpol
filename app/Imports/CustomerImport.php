<?php

namespace App\Imports;

use App\Models\Order;
use App\Services\CustomerImportService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class CustomerImport implements ToCollection, WithEvents
{
    /**
     * Opsi perbarui data jika pelanggan sudah ada
     */
    protected bool $updateExisting;

    /**
     * Nama sheet aktif saat ini
     */
    protected string $currentSheetName = 'Sheet 1';

    /**
     * Statistik hasil impor
     */
    public int $imported = 0;
    public int $updated = 0;
    public int $skipped = 0;
    public array $errors = [];
    public array $processedSheets = [];
    public array $skippedSheets = [];

    /**
     * Cache in-memory untuk pencarian cepat data existing (O(1))
     */
    protected array $existingByNumber = [];
    protected array $existingByNikAndName = [];
    protected array $existingByPhoneAndName = [];
    protected array $existingByNameAndAddress = [];

    /**
     * Penomoran otomatis pelanggan
     */
    protected string $yearPrefix;
    protected int $maxSeq = 0;

    public function __construct(bool $updateExisting = true)
    {
        $this->updateExisting = $updateExisting;
        $this->yearPrefix = 'PLG-' . date('Y') . '-';
        $this->initializeCache();
    }

    /**
     * Event listener untuk menangkap nama sheet sebelum sheet diproses
     */
    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $this->currentSheetName = $event->getSheet()->getTitle() ?: 'Sheet';
            },
        ];
    }

    /**
     * Inisialisasi lookup cache dan nomor urut pelanggan
     */
    protected function initializeCache(): void
    {
        try {
            $totalOrdersCount = Order::count();
            $this->maxSeq = $totalOrdersCount;

            $this->existingByNumber = Order::whereNotNull('order_number')->pluck('id', 'order_number')->all();

            foreach ($this->existingByNumber as $num => $id) {
                if (str_starts_with((string)$num, $this->yearPrefix)) {
                    $seqPart = substr($num, strlen($this->yearPrefix));
                    if (is_numeric($seqPart)) {
                        $seqVal = (int) $seqPart;
                        if ($seqVal > $this->maxSeq) {
                            $this->maxSeq = $seqVal;
                        }
                    }
                }
            }

            foreach (Order::select('id', 'customer_name', 'id_card_number', 'customer_phone', 'address')->get() as $existingOrd) {
                $cName = strtolower(trim((string)$existingOrd->customer_name));
                if (!empty($existingOrd->id_card_number)) {
                    $key = $existingOrd->id_card_number . '_' . $cName;
                    $this->existingByNikAndName[$key] = $existingOrd->id;
                }
                if (!empty($existingOrd->customer_phone) && $existingOrd->customer_phone !== '-') {
                    $key = $existingOrd->customer_phone . '_' . $cName;
                    $this->existingByPhoneAndName[$key] = $existingOrd->id;
                }
                if (!empty($existingOrd->customer_name) && !empty($existingOrd->address) && $existingOrd->address !== '-') {
                    $key = $cName . '_' . strtolower(trim((string)$existingOrd->address));
                    $this->existingByNameAndAddress[$key] = $existingOrd->id;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('CustomerImport cache initialization warning: ' . $e->getMessage());
        }
    }

    /**
     * Proses baris-baris pada setiap sheet
     */
    public function collection(Collection $rows): void
    {
        $rawRows = $rows->toArray();
        if (empty($rawRows)) {
            return;
        }

        $sheetName = $this->currentSheetName ?: 'Sheet';

        // Bersihkan dan normalisasi setiap sel
        $cleanedRows = [];
        foreach ($rawRows as $r) {
            if (!is_array($r)) {
                continue;
            }
            $cleanRow = array_map(function ($val) {
                if ($val === null) {
                    return '';
                }
                if (is_bool($val)) {
                    return $val ? 'TRUE' : 'FALSE';
                }
                return trim(str_replace(["\xc2\xa0", "\u{00a0}", "&nbsp;"], ' ', (string)$val));
            }, $r);

            // Lewati baris yang sepenuhnya kosong
            if (count(array_filter($cleanRow, fn($v) => $v !== '')) === 0) {
                continue;
            }
            $cleanedRows[] = $cleanRow;
        }

        if (empty($cleanedRows)) {
            return;
        }

        // Cari baris header secara dinamis (mendukung header di Baris 5 seperti template.xlsx maupun Baris 1)
        $headerIndex = null;
        $fieldMap = [];
        $bestScore = 0;

        $scanLimit = min(15, count($cleanedRows));
        for ($rIdx = 0; $rIdx < $scanLimit; $rIdx++) {
            $candidateRow = $cleanedRows[$rIdx];

            // Cek apakah baris ini memuat mapping kolom
            $candidateMap = CustomerImportService::mapHeaders($candidateRow);
            $hasName = isset($candidateMap['customer_name']);
            $hasAddress = isset($candidateMap['address']) || isset($candidateMap['village']);

            $score = count($candidateMap);
            if ($hasName) {
                $score += 3;
            }
            if ($hasAddress) {
                $score += 3;
            }
            if (isset($candidateMap['price'])) {
                $score += 2;
            }
            if (isset($candidateMap['id_card_number'])) {
                $score += 2;
            }
            if (isset($candidateMap['customer_phone'])) {
                $score += 1;
            }
            if (isset($candidateMap['package_name'])) {
                $score += 1;
            }

            // Penalti bila hanya 1 kolom yang cocok dan merupakan teks judul laporan
            if (count($candidateMap) === 1 && $hasName) {
                $cellText = (string)($candidateRow[$candidateMap['customer_name']] ?? '');
                if (strlen($cellText) > 25 || str_word_count($cellText) > 3) {
                    $score = 0;
                }
            }

            if ($score > $bestScore && ($hasName || $hasAddress)) {
                $bestScore = $score;
                $headerIndex = $rIdx;
                $fieldMap = $candidateMap;

                // Jika sudah menemukan nama dan alamat dengan kecocokan tinggi
                if ($hasName && $hasAddress && $score >= 6) {
                    break;
                }
            }
        }

        // Jika tidak ditemukan baris header yang cocok, periksa template posisi default template.xlsx (20 kolom)
        if ($headerIndex === null) {
            // Periksa jika ada baris yang memuat kata 'Nama Lengkap' atau 'No. KTP'
            foreach ($cleanedRows as $idx => $row) {
                $rowStr = strtolower(implode(' ', $row));
                if (str_contains($rowStr, 'nama') && (str_contains($rowStr, 'ktp') || str_contains($rowStr, 'nik') || str_contains($rowStr, 'alamat'))) {
                    $headerIndex = $idx;
                    $fieldMap = CustomerImportService::mapHeaders($row);
                    break;
                }
            }
        }

        // Jika tetap tidak ditemukan header yang valid, lewati sheet ini
        if ($headerIndex === null) {
            $this->skippedSheets[] = $sheetName;
            return;
        }

        // Cek jika berkas menggunakan format template 9-kolom resmi (No, Nama Pemohon, PPOE, No KTP, No Handphone, Email, Jenis Layanan, Harga, Alamat)
        $isNineColumnTemplate = count($cleanedRows[$headerIndex]) <= 12 && (isset($fieldMap['pppoe']) || (isset($cleanedRows[$headerIndex][2]) && stripos((string)$cleanedRows[$headerIndex][2], 'ppoe') !== false));

        if ($isNineColumnTemplate) {
            $nineColumnPositions = [
                'customer_name' => 1, // B: Nama Pemohon
                'pppoe' => 2,         // C: PPOE
                'id_card_number' => 3,// D: No KTP
                'customer_phone' => 4,// E: No Handphone
                'customer_email' => 5,// F: Email
                'package_name' => 6,  // G: Jenis Layanan
                'price' => 7,         // H: Harga
                'address' => 8,       // I: Alamat
            ];
            foreach ($nineColumnPositions as $field => $pos) {
                if (!isset($fieldMap[$field]) && !in_array($pos, $fieldMap, true) && isset($cleanedRows[$headerIndex][$pos])) {
                    $fieldMap[$field] = $pos;
                }
            }
        } else {
            // Fallback mapping kolom indeks sesuai urutan legacy 20-kolom template.xlsx (A sampai T)
            $templateColumnPositions = [
                'order_number' => 1,       // B: ID / No. Pelanggan
                'id_card_number' => 2,     // C: No. KTP / NIK
                'customer_name' => 3,      // D: Nama Lengkap Pelanggan
                'birth_place' => 4,        // E: Tempat Lahir
                'birth_date' => 5,         // F: Tanggal Lahir
                // 6: Usia (dilewati/kalkulasi)
                'customer_phone' => 7,     // H: No. Handphone (WA)
                'customer_email' => 8,     // I: Alamat Email
                'package_name' => 9,       // J: Jenis Layanan (Paket)
                'speed' => 10,             // K: Kecepatan
                'price' => 11,             // L: Harga (Rp)
                'address' => 12,           // M: Alamat
                'village' => 13,           // N: Wilayah / Desa
                'technician' => 14,        // O: Teknisi
                'assigned_odp' => 15,      // P: ODP
                'installation_date' => 16, // Q: Tanggal Pemasangan
                'status' => 17,            // R: Status Berlangganan
                'registration_date' => 18, // S: Tanggal Terdaftar
                'admin_notes' => 19,       // T: Catatan
            ];

            foreach ($templateColumnPositions as $field => $pos) {
                if (!isset($fieldMap[$field]) && !in_array($pos, $fieldMap, true) && isset($cleanedRows[$headerIndex][$pos])) {
                    $fieldMap[$field] = $pos;
                }
            }
        }

        $dataRows = array_slice($cleanedRows, $headerIndex + 1);
        if (empty($dataRows)) {
            $this->processedSheets[] = [
                'name' => $sheetName,
                'imported' => 0,
                'updated' => 0,
                'empty' => true,
            ];
            return;
        }

        $sheetImported = 0;
        $sheetUpdated = 0;

        // Pre-resolusi wilayah desa antar baris
        $rowVillages = [];
        foreach ($dataRows as $idx => $r) {
            $cAddress = $this->getVal($r, $fieldMap, 'address');
            $cVillageRaw = $this->getVal($r, $fieldMap, 'village');
            $cNotes = $this->getVal($r, $fieldMap, 'admin_notes');
            $rowVillages[$idx] = CustomerImportService::resolveVillage($cVillageRaw, $sheetName, $cAddress, $cNotes);
        }

        // Interpolasi desa jika ada yang kosong
        $lastKnownVillage = null;
        $totalDataCount = count($dataRows);
        for ($idx = 0; $idx < $totalDataCount; $idx++) {
            if (!empty($rowVillages[$idx])) {
                $lastKnownVillage = $rowVillages[$idx];
            } elseif ($lastKnownVillage) {
                $rowVillages[$idx] = $lastKnownVillage;
            }
        }

        foreach ($dataRows as $index => $row) {
            $rowNum = $index + $headerIndex + 2;
            $rowPrefix = "[Sheet: {$sheetName}, Baris #{$rowNum}]";

            $name = $this->getVal($row, $fieldMap, 'customer_name');
            $address = $this->getVal($row, $fieldMap, 'address');

            // Tangani penggabungan komponen alamat tambahan jika ada
            $rtRwRaw = $this->getVal($row, $fieldMap, 'rt_rw');
            $villageRaw = $this->getVal($row, $fieldMap, 'village');
            $districtRaw = $this->getVal($row, $fieldMap, 'district');
            $regencyRaw = $this->getVal($row, $fieldMap, 'regency');

            $addressParts = [];
            if (!empty($address) && $address !== '-') {
                $addressParts[] = $address;
            }
            if (!empty($rtRwRaw) && $rtRwRaw !== '-' && (!empty($address) ? !str_contains(strtolower($address), strtolower($rtRwRaw)) : true)) {
                $addressParts[] = (str_starts_with(strtolower($rtRwRaw), 'rt') ? '' : 'RT/RW ') . $rtRwRaw;
            }
            if (!empty($villageRaw) && $villageRaw !== '-' && (!empty($address) ? !str_contains(strtolower($address), strtolower($villageRaw)) : true)) {
                $addressParts[] = (str_starts_with(strtolower($villageRaw), 'desa') || str_starts_with(strtolower($villageRaw), 'kel') ? '' : 'Desa ') . $villageRaw;
            }
            if (!empty($districtRaw) && $districtRaw !== '-' && (!empty($address) ? !str_contains(strtolower($address), strtolower($districtRaw)) : true)) {
                $addressParts[] = (str_starts_with(strtolower($districtRaw), 'kec') ? '' : 'Kec. ') . $districtRaw;
            }
            if (!empty($regencyRaw) && $regencyRaw !== '-' && (!empty($address) ? !str_contains(strtolower($address), strtolower($regencyRaw)) : true)) {
                $addressParts[] = (str_starts_with(strtolower($regencyRaw), 'kab') || str_starts_with(strtolower($regencyRaw), 'kota') ? '' : 'Kab. ') . $regencyRaw;
            }
            if (!empty($addressParts)) {
                $address = implode(', ', $addressParts);
            }

            // NIK
            $nikRaw = $this->getVal($row, $fieldMap, 'id_card_number');
            $nik = $this->cleanNik($nikRaw);

            // Handphone
            $phoneRaw = $this->getVal($row, $fieldMap, 'customer_phone');
            $phone = $this->cleanPhone($phoneRaw);

            // Baris kosong atau baris rekap total diabaikan
            if (empty($name) && empty($address) && empty($nik) && ($phone === '-' || empty($phone))) {
                continue;
            }
            if (stripos($name, 'TOTAL') !== false || stripos($address, 'TOTAL') !== false) {
                continue;
            }

            // Jika nama kosong tetapi NIK/HP ada
            if (empty($name) || $name === '-') {
                if (!empty($nik)) {
                    $name = "Pelanggan ({$nik})";
                } elseif ($phone !== '-') {
                    $name = "Pelanggan ({$phone})";
                } else {
                    $this->skipped++;
                    $this->errors[] = "{$rowPrefix}: Dilewati karena nama pelanggan kosong.";
                    continue;
                }
            }

            // Desa
            $detectedVillage = $rowVillages[$index] ?? CustomerImportService::resolveVillage($villageRaw, $sheetName, $address);
            if (empty($address) || $address === '-') {
                $address = (!empty($detectedVillage) && !CustomerImportService::isGenericSheetName($detectedVillage)) ? "Desa {$detectedVillage}" : '-';
            }

            // ID / No. Pelanggan
            $orderNumberRaw = $this->getVal($row, $fieldMap, 'order_number');
            $orderNumber = (!empty($orderNumberRaw) && $orderNumberRaw !== '-') ? trim((string)$orderNumberRaw) : null;

            // Email
            $emailRaw = $this->getVal($row, $fieldMap, 'customer_email');
            $email = (!empty($emailRaw) && $emailRaw !== '-') ? trim((string)$emailRaw) : '-';

            // Paket & Kecepatan
            $packageNameRaw = $this->getVal($row, $fieldMap, 'package_name');
            $packageName = (!empty($packageNameRaw) && $packageNameRaw !== '-') ? trim((string)$packageNameRaw) : '-';

            $speedRaw = $this->getVal($row, $fieldMap, 'speed');
            $speed = (!empty($speedRaw) && $speedRaw !== '-') ? trim((string)$speedRaw) : null;
            if (empty($speed) && !empty($packageName) && $packageName !== '-') {
                if (preg_match('/(\d+\s*(?:mbps|kbps|gbps))/i', $packageName, $sm)) {
                    $speed = $sm[1];
                }
            }

            // Harga
            $priceRaw = $this->getVal($row, $fieldMap, 'price');
            $price = CustomerImportService::parsePrice($priceRaw);

            if ($price <= 0 && !empty($packageNameRaw) && $packageNameRaw !== '-') {
                if (preg_match('/(?:rp\.?\s*)?(\d{1,3}(?:\.\d{3})+|\d{5,})/i', $packageNameRaw, $pm)) {
                    $price = CustomerImportService::parsePrice($pm[1]);
                }
            }

            if ($price <= 0 && (!empty($speed) || (!empty($packageName) && $packageName !== '-'))) {
                $searchSpeed = $speed ?: $packageName;
                if (preg_match('/50\s*mbps/i', $searchSpeed)) {
                    $price = 220000.0;
                } elseif (preg_match('/30\s*mbps/i', $searchSpeed)) {
                    $price = 165000.0;
                } elseif (preg_match('/20\s*mbps/i', $searchSpeed) || preg_match('/15\s*mbps/i', $searchSpeed)) {
                    $price = 110000.0;
                }
            }

            if (($packageName === '-' || empty($packageName)) && $price > 0) {
                if ($price >= 200000) {
                    $packageName = '50 Mbps';
                    $speed = $speed ?: '50 Mbps';
                } elseif ($price >= 150000) {
                    $packageName = '30 Mbps';
                    $speed = $speed ?: '30 Mbps';
                } else {
                    $packageName = '20 Mbps';
                    $speed = $speed ?: '20 Mbps';
                }
            }

            // Tempat & Tanggal Lahir
            $birthPlaceRaw = $this->getVal($row, $fieldMap, 'birth_place');
            $birthPlace = (!empty($birthPlaceRaw) && $birthPlaceRaw !== '-') ? trim((string)$birthPlaceRaw) : null;

            $birthDateRaw = $this->getVal($row, $fieldMap, 'birth_date');
            $birthDate = $this->parseDateVal($birthDateRaw);

            // Tangani TTL gabungan
            $ttlRaw = $this->getVal($row, $fieldMap, 'ttl');
            if (!empty($ttlRaw) && $ttlRaw !== '-' && (empty($birthPlace) || empty($birthDate))) {
                $parts = preg_split('/[,\|\/]/', $ttlRaw, 2);
                if (count($parts) === 2) {
                    if (empty($birthPlace)) {
                        $p = trim($parts[0]);
                        if (!empty($p) && $p !== '-') {
                            $birthPlace = $p;
                        }
                    }
                    if (empty($birthDate)) {
                        $birthDate = $this->parseDateVal(trim($parts[1]));
                    }
                }
            }

            // Teknisi & ODP
            $technicianRaw = $this->getVal($row, $fieldMap, 'technician');
            $technician = (!empty($technicianRaw) && $technicianRaw !== '-') ? trim((string)$technicianRaw) : null;

            $assignedOdpRaw = $this->getVal($row, $fieldMap, 'assigned_odp');
            $assignedOdp = (!empty($assignedOdpRaw) && $assignedOdpRaw !== '-') ? trim((string)$assignedOdpRaw) : null;

            // Catatan
            $notesRaw = $this->getVal($row, $fieldMap, 'admin_notes');
            $adminNotes = (!empty($notesRaw) && $notesRaw !== '-') ? trim((string)$notesRaw) : null;

            // Status Berlangganan
            $statusRaw = $this->getVal($row, $fieldMap, 'status');
            $status = $this->resolveStatus($statusRaw);

            // Status Pembayaran & Metode
            $paymentStatusRaw = $this->getVal($row, $fieldMap, 'payment_status');
            $paymentStatus = (!empty($paymentStatusRaw) && $paymentStatusRaw !== '-') ? trim((string)$paymentStatusRaw) : ($status === 'Selesai' ? 'Lunas' : '-');

            $paymentMethodRaw = $this->getVal($row, $fieldMap, 'payment_method');
            $paymentMethod = (!empty($paymentMethodRaw) && $paymentMethodRaw !== '-') ? trim((string)$paymentMethodRaw) : null;

            // Tanggal Pemasangan
            $installationDateRaw = $this->getVal($row, $fieldMap, 'installation_date');
            $installationDate = $this->parseDateVal($installationDateRaw);

            $installationTimeRaw = $this->getVal($row, $fieldMap, 'installation_time');
            $installationTime = (!empty($installationTimeRaw) && $installationTimeRaw !== '-') ? trim((string)$installationTimeRaw) : null;

            // Tanggal Terdaftar (registration_date / created_at)
            $registrationDateRaw = $this->getVal($row, $fieldMap, 'registration_date');
            $registrationDate = $this->parseDateVal($registrationDateRaw);
            $createdAt = $registrationDate ? Carbon::parse($registrationDate) : now();

            $installedAt = ($status === 'Selesai' && $installationDate) ? Carbon::parse($installationDate) : ($status === 'Selesai' ? $createdAt : null);

            // PPPoE Username
            $pppoeRaw = $this->getVal($row, $fieldMap, 'pppoe');
            $pppoe = (!empty($pppoeRaw) && $pppoeRaw !== '-') ? trim((string)$pppoeRaw) : Order::generatePppoeUsername($name, $orderNumber);

            // Cek apakah pelanggan sudah terdaftar
            $existingOrderId = $this->findExistingOrderId($orderNumber, $nik, $phone, $name, $address);

            if ($existingOrderId) {
                if ($this->updateExisting) {
                    $existingOrder = Order::find($existingOrderId);
                    if ($existingOrder) {
                        $existingOrder->customer_name = $name;
                        $existingOrder->address = $address;
                        if (!empty($detectedVillage)) {
                            $existingOrder->village = $detectedVillage;
                        }
                        if ($phone !== '-') {
                            $existingOrder->customer_phone = $phone;
                        }
                        if ($email !== '-') {
                            $existingOrder->customer_email = $email;
                        }
                        if ($packageName !== '-') {
                            $existingOrder->package_name = $packageName;
                        }
                        if ($speed !== null) {
                            $existingOrder->speed = $speed;
                        }
                        if ($price > 0) {
                            $existingOrder->price = $price;
                            $existingOrder->total = $price;
                        }
                        if ($birthDate !== null) {
                            $existingOrder->birth_date = $birthDate;
                        }
                        if ($birthPlace !== null) {
                            $existingOrder->birth_place = $birthPlace;
                        }
                        if ($nik !== null) {
                            $existingOrder->id_card_number = $nik;
                        }
                        if ($technician !== null) {
                            $existingOrder->technician = $technician;
                        }
                        if ($assignedOdp !== null) {
                            $existingOrder->assigned_odp = $assignedOdp;
                        }
                        if ($adminNotes !== null) {
                            $existingOrder->admin_notes = $adminNotes;
                        }
                        if ($status !== '-') {
                            $existingOrder->status = $status;
                        }
                        if ($paymentStatus !== '-') {
                            $existingOrder->payment_status = $paymentStatus;
                        }
                        if ($paymentMethod !== null) {
                            $existingOrder->payment_method = $paymentMethod;
                        }
                        if ($installationDate !== null) {
                            $existingOrder->installation_date = $installationDate;
                        }
                        if ($installationTime !== null) {
                            $existingOrder->installation_time = $installationTime;
                        }
                        if ($installedAt !== null) {
                            $existingOrder->installed_at = $installedAt;
                        }
                        if ($registrationDate !== null) {
                            $existingOrder->created_at = $createdAt;
                        }
                        if (!empty($pppoeRaw) && $pppoeRaw !== '-') {
                            $existingOrder->pppoe = trim((string)$pppoeRaw);
                        }

                        $existingOrder->save();
                        $this->updateCacheRecord($existingOrder);

                        $this->updated++;
                        $sheetUpdated++;
                    } else {
                        $this->skipped++;
                    }
                } else {
                    $this->skipped++;
                }
            } else {
                // Buat Pelanggan Baru
                if (empty($orderNumber)) {
                    do {
                        $this->maxSeq++;
                        $orderNumber = $this->yearPrefix . str_pad((string)$this->maxSeq, 4, '0', STR_PAD_LEFT);
                    } while (isset($this->existingByNumber[$orderNumber]));
                }

                $newOrder = Order::create([
                    'order_number' => $orderNumber,
                    'customer_name' => $name,
                    'pppoe' => $pppoe,
                    'id_card_number' => $nik,
                    'birth_place' => $birthPlace,
                    'birth_date' => $birthDate,
                    'customer_phone' => $phone,
                    'customer_email' => $email,
                    'package_name' => $packageName,
                    'speed' => $speed,
                    'price' => $price,
                    'installation_fee' => 0,
                    'tax' => 0,
                    'total' => $price,
                    'address' => $address,
                    'village' => $detectedVillage,
                    'status' => $status,
                    'payment_status' => $paymentStatus,
                    'payment_method' => $paymentMethod,
                    'installation_date' => $installationDate,
                    'installation_time' => $installationTime,
                    'installed_at' => $installedAt,
                    'technician' => $technician,
                    'assigned_odp' => $assignedOdp,
                    'admin_notes' => $adminNotes,
                    'created_at' => $createdAt,
                ]);

                $this->updateCacheRecord($newOrder);

                $this->imported++;
                $sheetImported++;
            }
        }

        $this->processedSheets[] = [
            'name' => $sheetName,
            'imported' => $sheetImported,
            'updated' => $sheetUpdated,
        ];
    }

    /**
     * Dapatkan hasil impor lengkap
     */
    public function getResults(): array
    {
        $totalSheets = count($this->processedSheets);
        if ($totalSheets === 0) {
            return [
                'success' => false,
                'message' => 'Tidak ditemukan sheet dengan kolom data pelanggan ("Nama Lengkap" atau "Alamat") yang valid pada berkas Excel yang diunggah.',
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => !empty($this->skippedSheets)
                    ? ['Sheet berikut dilewati karena tidak memuat format kolom data pelanggan: ' . implode(', ', $this->skippedSheets)]
                    : ['Pastikan berkas Excel memiliki format kolom sesuai template resmi Banterpool.'],
                'sheets' => [],
            ];
        }

        // Jika sheet terdeteksi tetapi tidak ada baris data yang diisi
        if ($this->imported === 0 && $this->updated === 0) {
            $hasEmpty = false;
            foreach ($this->processedSheets as $ps) {
                if (!empty($ps['empty'])) {
                    $hasEmpty = true;
                    break;
                }
            }
            if ($hasEmpty) {
                return [
                    'success' => false,
                    'message' => 'Berkas Excel memiliki kolom template yang valid, namun belum ada baris data pelanggan yang diisi di bawah baris judul.',
                    'imported' => 0,
                    'updated' => 0,
                    'skipped' => $this->skipped,
                    'errors' => ['Silakan isi data pelanggan pada baris di bawah judul kolom template sebelum mengunggah.'],
                    'sheets' => $this->processedSheets,
                ];
            }
        }

        $sheetNames = array_column($this->processedSheets, 'name');
        if ($totalSheets > 1) {
            $sheetListStr = implode('", "', $sheetNames);
            $message = "Proses import selesai dari {$totalSheets} sheet (\"{$sheetListStr}\"). {$this->imported} pelanggan baru berhasil ditambahkan, {$this->updated} pelanggan diperbarui, dan {$this->skipped} baris dilewati.";
        } else {
            $message = "Proses import selesai (Sheet: \"{$sheetNames[0]}\"). {$this->imported} pelanggan baru berhasil ditambahkan, {$this->updated} pelanggan diperbarui, dan {$this->skipped} baris dilewati.";
        }

        if (!empty($this->skippedSheets)) {
            $this->errors[] = "Sheet [" . implode(', ', $this->skippedSheets) . "] dilewati karena tidak memuat format kolom data pelanggan.";
        }

        return [
            'success' => true,
            'message' => $message,
            'imported' => $this->imported,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
            'sheets' => $this->processedSheets,
        ];
    }

    /**
     * Ambil nilai dari baris berdasarkan pemetaan kolom
     */
    protected function getVal(array $row, array $fieldMap, string $field): ?string
    {
        if (!isset($fieldMap[$field])) {
            return null;
        }

        $idx = $fieldMap[$field];
        if (!isset($row[$idx])) {
            return null;
        }

        $val = trim((string)$row[$idx]);
        return ($val !== '') ? $val : null;
    }

    /**
     * Bersihkan NIK (dukung notasi eksponensial ilmiah Excel)
     */
    protected function cleanNik(?string $raw): ?string
    {
        if (empty($raw) || $raw === '-') {
            return null;
        }

        if (is_numeric($raw) && preg_match('/[eE]/i', (string)$raw)) {
            $raw = sprintf('%.0f', (float)$raw);
        }

        $nik = preg_replace('/[^0-9]/', '', (string)$raw);
        return (!empty($nik) && $nik !== '0') ? $nik : null;
    }

    /**
     * Bersihkan No Handphone Indonesia
     */
    protected function cleanPhone(?string $raw): string
    {
        if (empty($raw) || $raw === '-') {
            return '-';
        }

        if (is_numeric($raw) && preg_match('/[eE]/i', (string)$raw)) {
            $raw = sprintf('%.0f', (float)$raw);
        }

        $phone = preg_replace('/[^0-9+]/', '', (string)$raw);
        if (str_starts_with($phone, '62')) {
            $phone = '0' . substr($phone, 2);
        } elseif (str_starts_with($phone, '8')) {
            $phone = '0' . $phone;
        }

        return !empty($phone) ? $phone : '-';
    }

    /**
     * Parse tanggal dari format Excel Serial Number maupun string
     */
    protected function parseDateVal($raw): ?string
    {
        if (empty($raw) || $raw === '-') {
            return null;
        }

        // 1. Angka serial tanggal Microsoft Excel (misal: 45678)
        if (is_numeric($raw) && (float)$raw > 1000 && (float)$raw < 100000) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float)$raw))->format('Y-m-d');
            } catch (\Throwable $e) {}
        }

        // 2. String tanggal umum atau format d/m/Y / Y-m-d
        return CustomerImportService::parseDate((string)$raw);
    }

    /**
     * Normalisasi status
     */
    protected function resolveStatus(?string $raw): string
    {
        if (empty($raw) || $raw === '-') {
            return 'Selesai';
        }

        $statusMapping = [
            'selesai' => 'Selesai',
            'aktif' => 'Selesai',
            'selesai / aktif' => 'Selesai',
            'menunggu konfirmasi' => 'Menunggu Konfirmasi',
            'pending' => 'Menunggu Konfirmasi',
            'jadwal pemasangan' => 'Jadwal Pemasangan',
            'jadwal teknisi' => 'Jadwal Teknisi',
            'jadwal' => 'Jadwal Teknisi',
            'sedang dipasang' => 'Sedang Dipasang',
            'dipasang' => 'Sedang Dipasang',
            'dibatalkan' => 'Dibatalkan',
            'batal' => 'Dibatalkan',
        ];

        $key = strtolower(trim((string)$raw));
        return $statusMapping[$key] ?? (trim((string)$raw) ?: 'Selesai');
    }

    /**
     * Cari ID order yang sudah ada berdasarkan identitas unik
     */
    protected function findExistingOrderId(?string $orderNumber, ?string $nik, string $phone, string $name, string $address): ?int
    {
        if (!empty($orderNumber) && isset($this->existingByNumber[$orderNumber])) {
            return $this->existingByNumber[$orderNumber];
        }

        $cleanName = strtolower(trim($name));

        if (!empty($nik) && strlen($nik) >= 10) {
            $nikKey = $nik . '_' . $cleanName;
            if (isset($this->existingByNikAndName[$nikKey])) {
                return $this->existingByNikAndName[$nikKey];
            }
        }

        if ($phone !== '-' && !empty($phone)) {
            $phoneKey = $phone . '_' . $cleanName;
            if (isset($this->existingByPhoneAndName[$phoneKey])) {
                return $this->existingByPhoneAndName[$phoneKey];
            }
        }

        if (!empty($name) && !empty($address) && $address !== '-') {
            $nameKey = $cleanName . '_' . strtolower(trim($address));
            if (isset($this->existingByNameAndAddress[$nameKey])) {
                return $this->existingByNameAndAddress[$nameKey];
            }
        }

        return null;
    }

    /**
     * Perbarui entri in-memory lookup cache
     */
    protected function updateCacheRecord(Order $order): void
    {
        $id = $order->id;
        $cleanName = strtolower(trim((string)$order->customer_name));

        if (!empty($order->order_number)) {
            $this->existingByNumber[$order->order_number] = $id;
        }
        if (!empty($order->id_card_number)) {
            $this->existingByNikAndName[$order->id_card_number . '_' . $cleanName] = $id;
        }
        if (!empty($order->customer_phone) && $order->customer_phone !== '-') {
            $this->existingByPhoneAndName[$order->customer_phone . '_' . $cleanName] = $id;
        }
        if (!empty($order->customer_name) && !empty($order->address) && $order->address !== '-') {
            $this->existingByNameAndAddress[$cleanName . '_' . strtolower(trim((string)$order->address))] = $id;
        }
    }
}
