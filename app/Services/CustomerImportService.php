<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class CustomerImportService
{
    /**
     * Parsing dan impor file Excel / CSV ke database pelanggan (orders).
     *
     * @param UploadedFile $file
     * @param bool $updateExisting
     * @return array
     */
    public static function import(UploadedFile $file, bool $updateExisting = true): array
    {
        // Alokasi memori dan batas waktu tak terbatas agar mampu menangani impor data dalam jumlah sangat besar
        @ini_set('memory_limit', '2048M');
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);
        @ini_set('pcre.backtrack_limit', '100000000');

        $sheets = [];
        try {
            $sheets = self::extractSheetsFromFile($file);
        } catch (\Throwable $e) {
            Log::error('Error parsing import file: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal membaca isi berkas file: ' . $e->getMessage(),
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => ['Format file tidak dikenali atau rusak: ' . $e->getMessage()],
            ];
        }

        if (empty($sheets)) {
            return [
                'success' => false,
                'message' => 'Berkas tidak memiliki baris data pelanggan yang valid atau seluruh sheet kosong.',
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => ['Berkas kosong atau tidak ada data yang terdeteksi.'],
            ];
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $processedSheets = [];
        $skippedSheets = [];

        DB::beginTransaction();
        try {
            $totalOrdersCount = Order::count();

            // Cache data pelanggan yang sudah ada untuk mempercepat proses (O(1) in-memory lookup)
            $existingOrdersByNumber = Order::whereNotNull('order_number')->pluck('id', 'order_number')->all();
            $existingOrdersByNikAndName = [];
            $existingOrdersByPhoneAndName = [];
            $existingOrdersByNameAndAddress = [];
            foreach (Order::select('id', 'customer_name', 'id_card_number', 'customer_phone', 'address')->get() as $existingOrd) {
                if (!empty($existingOrd->id_card_number)) {
                    $key = $existingOrd->id_card_number . '_' . strtolower(trim($existingOrd->customer_name));
                    $existingOrdersByNikAndName[$key] = $existingOrd->id;
                }
                if (!empty($existingOrd->customer_phone) && $existingOrd->customer_phone !== '-') {
                    $key = $existingOrd->customer_phone . '_' . strtolower(trim($existingOrd->customer_name));
                    $existingOrdersByPhoneAndName[$key] = $existingOrd->id;
                }
                if (!empty($existingOrd->customer_name) && !empty($existingOrd->address) && $existingOrd->address !== '-') {
                    $key = strtolower(trim($existingOrd->customer_name)) . '_' . strtolower(trim($existingOrd->address));
                    $existingOrdersByNameAndAddress[$key] = $existingOrd->id;
                }
            }

            $currentYear = date('Y');
            $yearPrefix = "PLG-{$currentYear}-";
            $maxSeq = $totalOrdersCount;
            foreach (array_keys($existingOrdersByNumber) as $num) {
                if (str_starts_with($num, $yearPrefix)) {
                    $seqPart = substr($num, strlen($yearPrefix));
                    if (is_numeric($seqPart)) {
                        $seqVal = (int) $seqPart;
                        if ($seqVal > $maxSeq) {
                            $maxSeq = $seqVal;
                        }
                    }
                }
            }

            foreach ($sheets as $sheet) {
                $sheetName = $sheet['name'] ?? 'Sheet';
                $sheetRows = $sheet['rows'] ?? [];

                if (empty($sheetRows)) {
                    continue;
                }

                // Cari baris header pada sheet ini dengan sistem scoring presisi (memeriksa baris-baris awal)
                $headerIndex = null;
                $fieldMap = [];
                $bestScore = 0;

                $isDataRow = function (array $r): bool {
                    foreach ($r as $c) {
                        $str = trim((string)$c);
                        if (empty($str)) {
                            continue;
                        }
                        // NIK (10+ digit angka murni)
                        if (preg_match('/^\d{10,}$/', $str)) {
                            return true;
                        }
                        // Email valid
                        if (filter_var($str, FILTER_VALIDATE_EMAIL)) {
                            return true;
                        }
                        // No HP Indonesia
                        if (preg_match('/^(?:08|628|\+628)\d{7,}$/', $str)) {
                            return true;
                        }
                        // Tanggal ISO
                        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) {
                            return true;
                        }
                    }
                    return false;
                };

                $scanLimit = min(15, count($sheetRows));
                for ($rIdx = 0; $rIdx < $scanLimit; $rIdx++) {
                    $rowCandidate = $sheetRows[$rIdx];

                    // Jika baris ini jelas memuat data pelanggan (NIK, email, no hp, tanggal ISO), jangan jadikan header dan hentikan scan
                    if ($isDataRow($rowCandidate)) {
                        if ($headerIndex !== null) {
                            break;
                        }
                    }

                    $candidateMap = self::mapHeaders($rowCandidate);
                    $hasName = isset($candidateMap['customer_name']);
                    $hasAddress = isset($candidateMap['address']) || isset($candidateMap['village']);

                    // Hitung bobot kecocokan header
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
                    if (isset($candidateMap['package_name'])) {
                        $score += 1;
                    }
                    if (isset($candidateMap['customer_phone'])) {
                        $score += 1;
                    }
                    if (isset($candidateMap['id_card_number'])) {
                        $score += 1;
                    }

                    // Penalti jika hanya ada 1 kolom dan baris tersebut memiliki kalimat panjang (seperti judul laporan "LAPORAN DATA...")
                    if (count($candidateMap) === 1 && $hasName) {
                        $matchedCol = $candidateMap['customer_name'];
                        $cellText = (string)($rowCandidate[$matchedCol] ?? '');
                        if (strlen($cellText) > 25 || str_word_count($cellText) > 3) {
                            $score = 0;
                        }
                    }

                    // Jika baris ini memiliki skor lebih tinggi dari yang pernah ditemukan
                    if ($score > $bestScore && ($hasName || $hasAddress)) {
                        $bestScore = $score;
                        $headerIndex = $rIdx;
                        $fieldMap = $candidateMap;

                        // Jika sudah menemukan baris yang memiliki Nama DAN Alamat dengan skor tinggi (>= 8), ini pasti baris header
                        if ($hasName && $hasAddress && $score >= 8) {
                            break;
                        }
                    }
                }

                // Jika sheet tidak memiliki kolom nama atau alamat pelanggan, lewati sheet ini
                if ($headerIndex === null) {
                    $skippedSheets[] = $sheetName;
                    continue;
                }

                $dataRows = array_slice($sheetRows, $headerIndex + 1);
                if (empty($dataRows)) {
                    continue;
                }

                $sheetImportedCount = 0;
                $sheetUpdatedCount = 0;

                // Pre-analisis nama desa untuk setiap baris data agar jika ada baris dengan alamat kosong
                // nama desa dapat diinterpolasi dari baris terdekat dalam satu blok/section
                $rowVillages = [];
                foreach ($dataRows as $idxCandidate => $rCandidate) {
                    $cAddress = self::getValue($rCandidate, $fieldMap, 'address');
                    $cVillageRaw = self::getValue($rCandidate, $fieldMap, 'village');
                    $cNotes = self::getValue($rCandidate, $fieldMap, 'admin_notes');
                    $rowVillages[$idxCandidate] = self::resolveVillage($cVillageRaw, $sheetName, $cAddress, $cNotes);
                }

                // Interpolasi baris yang desanya kosong dari baris sebelum / sesudah
                $lastKnownVillage = null;
                $totalDataCount = count($dataRows);
                for ($idxCandidate = 0; $idxCandidate < $totalDataCount; $idxCandidate++) {
                    if (!empty($rowVillages[$idxCandidate])) {
                        $lastKnownVillage = $rowVillages[$idxCandidate];
                    } else {
                        $prevV = $lastKnownVillage;
                        $nextV = null;
                        for ($k = $idxCandidate + 1; $k < $totalDataCount; $k++) {
                            if (!empty($rowVillages[$k])) {
                                $nextV = $rowVillages[$k];
                                break;
                            }
                        }
                        if ($prevV && $nextV && $prevV === $nextV) {
                            $rowVillages[$idxCandidate] = $prevV;
                        } elseif ($prevV) {
                            $rowVillages[$idxCandidate] = $prevV;
                        } elseif ($nextV) {
                            $rowVillages[$idxCandidate] = $nextV;
                        }
                    }
                }

                foreach ($dataRows as $index => $row) {
                    $rowNum = $index + $headerIndex + 2; // Nomor baris sebenarnya pada sheet
                    $rowPrefix = count($sheets) > 1 ? "[Sheet: {$sheetName}, Baris #{$rowNum}]" : "Baris #{$rowNum}";

                    // Ambil data berdasarkan mapping header
                    $name = self::getValue($row, $fieldMap, 'customer_name');
                    $address = self::getValue($row, $fieldMap, 'address');

                    // Dukung penggabungan komponen alamat jika alamat utama belum lengkap atau dipisah dalam kolom RT/RW, Desa, Kecamatan
                    $rtRwRaw = self::getValue($row, $fieldMap, 'rt_rw');
                    $villageRaw = self::getValue($row, $fieldMap, 'village');
                    $districtRaw = self::getValue($row, $fieldMap, 'district');
                    $regencyRaw = self::getValue($row, $fieldMap, 'regency');

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

                    // Ambil dan bersihkan NIK (dukung notasi ilmiah Excel)
                    $nikRaw = self::getValue($row, $fieldMap, 'id_card_number');
                    if (is_numeric($nikRaw) && preg_match('/[eE]/i', (string)$nikRaw)) {
                        $nikRaw = sprintf('%.0f', (float)$nikRaw);
                    }
                    $nik = preg_replace('/[^0-9]/', '', (string)$nikRaw);
                    $nik = (!empty($nik) && $nik !== '0') ? $nik : null;

                    // Ambil dan bersihkan No. Handphone (dukung notasi ilmiah Excel seperti 8.3149349228E10)
                    $phoneRaw = self::getValue($row, $fieldMap, 'customer_phone');
                    if (!empty($phoneRaw) && $phoneRaw !== '-') {
                        if (is_numeric($phoneRaw) && preg_match('/[eE]/i', (string)$phoneRaw)) {
                            $phoneRaw = sprintf('%.0f', (float)$phoneRaw);
                        }
                        $phone = preg_replace('/[^0-9+]/', '', (string)$phoneRaw);
                        if (str_starts_with($phone, '62')) {
                            $phone = '0' . substr($phone, 2);
                        } elseif (str_starts_with($phone, '8')) {
                            $phone = '0' . $phone;
                        }
                        $phone = !empty($phone) ? $phone : '-';
                    } else {
                        $phone = '-';
                    }

                    // Baris kosong atau baris rekap total diabaikan
                    if (empty($name) && empty($address) && empty($nik) && ($phone === '-' || empty($phone))) {
                        continue;
                    }
                    if (stripos($name, 'TOTAL') !== false || stripos($address, 'TOTAL') !== false) {
                        continue;
                    }

                    // Jika nama kosong tetapi NIK atau No HP ada, tetap simpan pelanggan jangan dilewati
                    if (empty($name) || $name === '-') {
                        if (!empty($nik)) {
                            $name = "Pelanggan ({$nik})";
                        } elseif ($phone !== '-') {
                            $name = "Pelanggan ({$phone})";
                        } else {
                            $skipped++;
                            $errors[] = "{$rowPrefix}: Dilewati karena nama pelanggan kosong.";
                            continue;
                        }
                    }

                    // Deteksi nama desa secara dinamis dari alamat pelanggan, kolom desa, atau interpolasi blok
                    $detectedVillage = $rowVillages[$index] ?? self::resolveVillage($villageRaw, $sheetName, $address);

                    // Jika alamat kosong, gunakan nama desa jika terdeteksi, atau tanda '-' (jangan simpan nama template)
                    if (empty($address) || $address === '-') {
                        $address = (!empty($detectedVillage) && !self::isGenericSheetName($detectedVillage)) ? "Desa {$detectedVillage}" : '-';
                    }

                    $orderNumberRaw = self::getValue($row, $fieldMap, 'order_number');
                    $orderNumber = (!empty($orderNumberRaw) && $orderNumberRaw !== '-') ? $orderNumberRaw : null;

                    $emailRaw = self::getValue($row, $fieldMap, 'customer_email');
                    $email = (!empty($emailRaw) && $emailRaw !== '-') ? $emailRaw : '-';

                    $packageNameRaw = self::getValue($row, $fieldMap, 'package_name');
                    $packageName = (!empty($packageNameRaw) && $packageNameRaw !== '-') ? $packageNameRaw : '-';

                    $speedRaw = self::getValue($row, $fieldMap, 'speed');
                    $speed = (!empty($speedRaw) && $speedRaw !== '-') ? $speedRaw : null;
                    if (empty($speed) && !empty($packageName) && $packageName !== '-') {
                        if (preg_match('/(\d+\s*(?:mbps|kbps|gbps))/i', $packageName, $sm)) {
                            $speed = $sm[1];
                        }
                    }

                    $priceRaw = self::getValue($row, $fieldMap, 'price');
                    $price = self::parsePrice($priceRaw);

                    // Jika kolom harga kosong atau 0, coba ekstrak dari kolom paket jika memuat nominal
                    if ($price <= 0 && !empty($packageNameRaw) && $packageNameRaw !== '-') {
                        if (preg_match('/(?:rp\.?\s*)?(\d{1,3}(?:\.\d{3})+|\d{5,})/i', $packageNameRaw, $pm)) {
                            $price = self::parsePrice($pm[1]);
                        }
                    }

                    // Jika masih 0 atau kosong, tentukan harga otomatis jika kolom paket/kecepatan diisi kecepatan internet
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

                    // Jika paket kosong (-) tetapi ada harga valid, tentukan paket & kecepatan otomatis dari harga
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

                    $birthPlaceRaw = self::getValue($row, $fieldMap, 'birth_place');
                    $birthPlace = (!empty($birthPlaceRaw) && $birthPlaceRaw !== '-') ? $birthPlaceRaw : null;

                    $birthDateRaw = self::getValue($row, $fieldMap, 'birth_date');
                    $birthDate = self::parseDate($birthDateRaw);

                    // Dukung parsing kolom gabungan TTL jika birth_place atau birth_date kosong
                    $ttlRaw = self::getValue($row, $fieldMap, 'ttl');
                    if (!empty($ttlRaw) && $ttlRaw !== '-' && (empty($birthPlace) || empty($birthDate))) {
                        $parts = preg_split('/[,\|\/]/', $ttlRaw, 2);
                        if (count($parts) === 2) {
                            if (empty($birthPlace)) {
                                $parsedPlace = trim($parts[0]);
                                if (!empty($parsedPlace) && $parsedPlace !== '-') {
                                    $birthPlace = $parsedPlace;
                                }
                            }
                            if (empty($birthDate)) {
                                $parsedDate = self::parseDate(trim($parts[1]));
                                if ($parsedDate !== null) {
                                    $birthDate = $parsedDate;
                                }
                            }
                        } else {
                            // Jika hanya 1 bagian (misal hanya tempat atau hanya tanggal)
                            $tryDate = self::parseDate($ttlRaw);
                            if ($tryDate !== null && empty($birthDate)) {
                                $birthDate = $tryDate;
                            } elseif (empty($birthPlace) && !preg_match('/\d/', $ttlRaw)) {
                                $birthPlace = trim($ttlRaw);
                            }
                        }
                    }

                    $technicianRaw = self::getValue($row, $fieldMap, 'technician');
                    $technician = (!empty($technicianRaw) && $technicianRaw !== '-') ? $technicianRaw : null;

                    $assignedOdpRaw = self::getValue($row, $fieldMap, 'assigned_odp');
                    $assignedOdp = (!empty($assignedOdpRaw) && $assignedOdpRaw !== '-') ? $assignedOdpRaw : null;

                    $notesRaw = self::getValue($row, $fieldMap, 'admin_notes');
                    $adminNotes = (!empty($notesRaw) && $notesRaw !== '-') ? $notesRaw : null;

                    // Tangani koordinat GPS jika ada
                    $latRaw = self::getValue($row, $fieldMap, 'latitude');
                    $lngRaw = self::getValue($row, $fieldMap, 'longitude');
                    $coordsRaw = self::getValue($row, $fieldMap, 'coordinates');
                    if (!empty($coordsRaw) && (empty($latRaw) || empty($lngRaw))) {
                        if (preg_match('/(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)/', $coordsRaw, $cm)) {
                            $latRaw = $cm[1];
                            $lngRaw = $cm[2];
                        }
                    }
                    $latitude = (!empty($latRaw) && $latRaw !== '-') ? $latRaw : null;
                    $longitude = (!empty($lngRaw) && $lngRaw !== '-') ? $lngRaw : null;

                    // Tangani status: JANGAN diisi asal jika kosong di berkas Excel
                    $statusRaw = self::getValue($row, $fieldMap, 'status');
                    if (!empty($statusRaw) && $statusRaw !== '-') {
                        $statusMapping = [
                            'selesai' => 'Selesai',
                            'aktif' => 'Selesai',
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
                        $statusKey = strtolower(trim($statusRaw));
                        $status = $statusMapping[$statusKey] ?? $statusRaw;
                    } else {
                        $status = '-';
                    }

                    // Tangani status pembayaran: JANGAN diisi asal jika kosong di berkas Excel
                    $paymentStatusRaw = self::getValue($row, $fieldMap, 'payment_status');
                    if (!empty($paymentStatusRaw) && $paymentStatusRaw !== '-') {
                        $paymentStatus = $paymentStatusRaw;
                    } else {
                        $paymentStatus = '-';
                    }

                    $paymentMethodRaw = self::getValue($row, $fieldMap, 'payment_method');
                    $paymentMethod = (!empty($paymentMethodRaw) && $paymentMethodRaw !== '-') ? $paymentMethodRaw : null;

                    $installationDateRaw = self::getValue($row, $fieldMap, 'installation_date');
                    $installationDate = self::parseDate($installationDateRaw);

                    $installationTimeRaw = self::getValue($row, $fieldMap, 'installation_time');
                    $installationTime = (!empty($installationTimeRaw) && $installationTimeRaw !== '-') ? $installationTimeRaw : null;

                    // Tangani tanggal terdaftar (registration_date) untuk created_at
                    $registrationDateRaw = self::getValue($row, $fieldMap, 'registration_date');
                    $registrationDate = self::parseDate($registrationDateRaw);
                    $createdAt = $registrationDate ? Carbon::parse($registrationDate) : now();

                    // Tangani installed_at: hanya jika installationDate ada dan status Selesai, jangan tebak now()
                    $installedAt = ($status === 'Selesai' && $installationDate) ? Carbon::parse($installationDate) : null;

                    // Cek apakah pelanggan sudah ada (berdasarkan No. Pelanggan atau NIK + Nama)
                    $existingOrderId = null;
                    if (!empty($orderNumber) && isset($existingOrdersByNumber[$orderNumber])) {
                        $existingOrderId = $existingOrdersByNumber[$orderNumber];
                    } elseif (!empty($nik) && strlen($nik) >= 10) {
                        $nikKey = $nik . '_' . strtolower(trim($name));
                        if (isset($existingOrdersByNikAndName[$nikKey])) {
                            $existingOrderId = $existingOrdersByNikAndName[$nikKey];
                        }
                    } elseif ($phone !== '-') {
                        $phoneKey = $phone . '_' . strtolower(trim($name));
                        if (isset($existingOrdersByPhoneAndName[$phoneKey])) {
                            $existingOrderId = $existingOrdersByPhoneAndName[$phoneKey];
                        }
                    } elseif (!empty($name) && $name !== '-') {
                        $nameKey = strtolower(trim($name)) . '_' . strtolower(trim($address));
                        if (isset($existingOrdersByNameAndAddress[$nameKey])) {
                            $existingOrderId = $existingOrdersByNameAndAddress[$nameKey];
                        }
                    }

                    if ($existingOrderId) {
                        if ($updateExisting) {
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
                                if ($latitude !== null) {
                                    $existingOrder->latitude = $latitude;
                                }
                                if ($longitude !== null) {
                                    $existingOrder->longitude = $longitude;
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

                                $existingOrder->save();

                                if (!empty($existingOrder->id_card_number)) {
                                    $existingOrdersByNikAndName[$existingOrder->id_card_number . '_' . strtolower(trim($existingOrder->customer_name))] = $existingOrder->id;
                                }
                                if (!empty($existingOrder->customer_phone) && $existingOrder->customer_phone !== '-') {
                                    $existingOrdersByPhoneAndName[$existingOrder->customer_phone . '_' . strtolower(trim($existingOrder->customer_name))] = $existingOrder->id;
                                }
                                if (!empty($existingOrder->customer_name) && !empty($existingOrder->address) && $existingOrder->address !== '-') {
                                    $existingOrdersByNameAndAddress[strtolower(trim($existingOrder->customer_name)) . '_' . strtolower(trim($existingOrder->address))] = $existingOrder->id;
                                }

                                $updated++;
                                $sheetUpdatedCount++;
                            } else {
                                $skipped++;
                            }
                        } else {
                            $skipped++;
                        }
                    } else {
                        if (empty($orderNumber)) {
                            do {
                                $maxSeq++;
                                $orderNumber = $yearPrefix . str_pad((string)$maxSeq, 4, '0', STR_PAD_LEFT);
                            } while (isset($existingOrdersByNumber[$orderNumber]));
                        }

                        $newOrder = Order::create([
                            'order_number' => $orderNumber,
                            'customer_name' => $name,
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
                            'latitude' => $latitude,
                            'longitude' => $longitude,
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

                        $existingOrdersByNumber[$orderNumber] = $newOrder->id;
                        if (!empty($nik)) {
                            $existingOrdersByNikAndName[$nik . '_' . strtolower(trim($name))] = $newOrder->id;
                        }
                        if ($phone !== '-') {
                            $existingOrdersByPhoneAndName[$phone . '_' . strtolower(trim($name))] = $newOrder->id;
                        }
                        if (!empty($name) && !empty($address) && $address !== '-') {
                            $existingOrdersByNameAndAddress[strtolower(trim($name)) . '_' . strtolower(trim($address))] = $newOrder->id;
                        }

                        $imported++;
                        $sheetImportedCount++;
                    }
                }

                $processedSheets[] = [
                    'name' => $sheetName,
                    'imported' => $sheetImportedCount,
                    'updated' => $sheetUpdatedCount,
                ];
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal mengeksekusi import pelanggan: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data ke database: ' . $e->getMessage(),
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [$e->getMessage()],
            ];
        }

        $totalSheetsCount = count($processedSheets);
        if ($totalSheetsCount === 0) {
            return [
                'success' => false,
                'message' => 'Tidak ditemukan sheet dengan kolom data pelanggan ("Nama Lengkap" atau "Alamat") yang valid pada berkas yang diunggah.',
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => !empty($skippedSheets)
                    ? ['Sheet berikut dilewati karena tidak memuat kolom data pelanggan: ' . implode(', ', $skippedSheets)]
                    : ['Pastikan berkas Excel memiliki judul kolom "Nama Pelanggan" dan "Alamat".'],
            ];
        }

        $sheetNames = array_column($processedSheets, 'name');
        if ($totalSheetsCount > 1) {
            $sheetListStr = implode('", "', $sheetNames);
            $message = "Proses import selesai dari {$totalSheetsCount} sheet (\"{$sheetListStr}\"). {$imported} pelanggan baru berhasil ditambahkan, {$updated} pelanggan diperbarui, dan {$skipped} baris dilewati.";
        } else {
            $message = "Proses import selesai (Sheet: \"{$sheetNames[0]}\"). {$imported} pelanggan baru berhasil ditambahkan, {$updated} pelanggan diperbarui, dan {$skipped} baris dilewati.";
        }

        if (!empty($skippedSheets)) {
            $errors[] = "Sheet [" . implode(', ', $skippedSheets) . "] dilewati karena tidak memuat format kolom data pelanggan.";
        }

        return [
            'success' => true,
            'message' => $message,
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'sheets' => $processedSheets,
        ];
    }

    /**
     * Download Template File Excel (.xls dengan UTF-8 BOM & XML meta)
     * Format ini kompatibel 100% dibuka langsung di Microsoft Excel, LibreOffice, dan Google Sheets.
     */
    public static function downloadTemplate()
    {
        $filename = 'Template_Import_Pelanggan_Banterpool.xls';
        $now = now('Asia/Jakarta')->translatedFormat('d F Y, H:i');

        $sampleData = [
            [
                'order_number' => 'PLG-2026-0501',
                'id_card_number' => '3302190102850001',
                'customer_name' => 'Bambang Sudarsono',
                'birth_place' => 'Banyumas',
                'birth_date' => '1985-02-01',
                'customer_phone' => '081234567890',
                'customer_email' => 'bambang.sudarsono@gmail.com',
                'package_name' => 'Paket 20 Mbps',
                'speed' => '20 Mbps',
                'price' => '110000',
                'address' => 'Batuanten RT 01/RW 02, Kec. Cilongok, Kab. Banyumas',
                'status' => 'Selesai',
            ],
            [
                'order_number' => 'PLG-2026-0502',
                'id_card_number' => '3302191506920002',
                'customer_name' => 'Siti Nurhaliza',
                'birth_place' => 'Purwokerto',
                'birth_date' => '1992-06-15',
                'customer_phone' => '085712345678',
                'customer_email' => 'siti.nurhaliza92@gmail.com',
                'package_name' => 'Paket 30 Mbps',
                'speed' => '30 Mbps',
                'price' => '165000',
                'address' => 'Panusupan RT 03/RW 01, Kec. Cilongok, Kab. Banyumas',
                'status' => 'Selesai',
            ],
            [
                'order_number' => '',
                'id_card_number' => '3302192008980003',
                'customer_name' => 'Rahmat Hidayat',
                'birth_place' => 'Banyumas',
                'birth_date' => '1998-08-20',
                'customer_phone' => '087812345678',
                'customer_email' => 'rahmat.hidayat@gmail.com',
                'package_name' => 'Paket 50 Mbps',
                'speed' => '50 Mbps',
                'price' => '220000',
                'address' => 'Jatisaba RT 02/RW 05, Kec. Cilongok, Kab. Banyumas',
                'status' => 'Menunggu Konfirmasi',
            ],
        ];

        return response()->streamDownload(function () use ($sampleData, $now) {
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
            echo view('admin.exports.pelanggan_template_excel', compact('sampleData', 'now'))->render();
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate, proxy-revalidate',
        ]);
    }

    /**
     * Download Template File CSV
     */
    public static function downloadCsvTemplate()
    {
        $filename = 'Template_Import_Pelanggan_Banterpool.csv';
        $headers = [
            'No. Pelanggan',
            'No. KTP / NIK',
            'Nama Lengkap Pelanggan',
            'Tempat Lahir',
            'Tanggal Lahir',
            'No. Handphone (WA)',
            'Alamat Email',
            'Jenis Layanan (Paket)',
            'Kecepatan',
            'Harga (Rp)',
            'Alamat',
            'Status Berlangganan',
        ];

        $rows = [
            [
                'PLG-2026-0501',
                "'3302190102850001",
                'Bambang Sudarsono',
                'Banyumas',
                '1985-02-01',
                '081234567890',
                'bambang.sudarsono@gmail.com',
                'Paket 20 Mbps',
                '20 Mbps',
                '110000',
                'Batuanten RT 01/RW 02, Kec. Cilongok, Kab. Banyumas',
                'Selesai',
            ],
            [
                'PLG-2026-0502',
                "'3302191506920002",
                'Siti Nurhaliza',
                'Purwokerto',
                '1992-06-15',
                '085712345678',
                'siti.nurhaliza92@gmail.com',
                'Paket 30 Mbps',
                '30 Mbps',
                '165000',
                'Panusupan RT 03/RW 01, Kec. Cilongok, Kab. Banyumas',
                'Selesai',
            ],
            [
                '',
                "'3302192008980003",
                'Rahmat Hidayat',
                'Banyumas',
                '1998-08-20',
                '087812345678',
                'rahmat.hidayat@gmail.com',
                'Paket 50 Mbps',
                '50 Mbps',
                '220000',
                'Jatisaba RT 02/RW 05, Kec. Cilongok, Kab. Banyumas',
                'Menunggu Konfirmasi',
            ],
        ];

        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($handle, $headers, ',');
            foreach ($rows as $row) {
                fputcsv($handle, $row, ',');
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate',
        ]);
    }

    /**
     * Parse CSV File
     */
    public static function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            return [];
        }

        // Cek baris pertama untuk deteksi delimiter (koma, titik koma, atau tab)
        $firstLine = fgets($handle);
        rewind($handle);

        $delimiter = ',';
        if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
            $delimiter = ';';
        } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
            $delimiter = "\t";
        }

        // Hapus UTF-8 BOM jika ada
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $rows = [];
        while (($data = fgetcsv($handle, 4096, $delimiter)) !== false) {
            // Trim tiap kolom
            $trimmed = array_map(function ($val) {
                return trim((string)$val, " \t\n\r\0\x0B'");
            }, $data);

            // Lewati baris yang seluruh kolomnya kosong
            if (count(array_filter($trimmed)) === 0) {
                continue;
            }
            $rows[] = $trimmed;
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Mengekstrak seluruh sheet dari berkas unggahan (.xlsx, .xls, .csv, .txt)
     * Mengembalikan array: [ ['name' => 'Sheet 1', 'rows' => [...]], ['name' => 'Sheet 2', 'rows' => [...]] ]
     */
    public static function extractSheetsFromFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        if ($extension === 'csv' || $extension === 'txt') {
            $rows = self::parseCsv($path);
            return !empty($rows) ? [['name' => 'Sheet 1', 'rows' => $rows]] : [];
        }

        if ($extension === 'xlsx') {
            $sheets = self::parseXlsxSheets($path);
            if (!empty($sheets)) {
                return $sheets;
            }
        }

        if ($extension === 'xls') {
            $sheets = self::parseXlsSheets($path);
            if (!empty($sheets)) {
                return $sheets;
            }
        }

        // Coba deteksi otomatis jika ekstensi tidak cocok
        $sheets = self::parseXlsxSheets($path);
        if (!empty($sheets)) {
            return $sheets;
        }

        $sheets = self::parseXlsSheets($path);
        if (!empty($sheets)) {
            return $sheets;
        }

        $rows = self::parseCsv($path);
        return !empty($rows) ? [['name' => 'Sheet 1', 'rows' => $rows]] : [];
    }

    /**
     * Parse XLS File (HTML table format atau XML 2003) menjadi multi-sheet
     */
    public static function parseXlsSheets(string $path): array
    {
        $content = @file_get_contents($path);
        if (empty($content)) {
            return [];
        }

        // Cek jika ini format XML Spreadsheet 2003 (<Workbook ...>)
        if (str_contains($content, '<Workbook') && str_contains($content, '<Row>')) {
            return self::parseXmlSpreadsheetSheets($content);
        }

        // Jika ini format HTML Table (.xls berbasis HTML yang umum)
        if (str_contains($content, '<table') || str_contains($content, '<tr')) {
            return self::parseHtmlTableSheets($content);
        }

        // Jika file ternyata CSV yang disimpan dengan ekstensi .xls
        $csvRows = self::parseCsv($path);
        return !empty($csvRows) ? [['name' => 'Sheet 1', 'rows' => $csvRows]] : [];
    }

    /**
     * Parse XLS File (Backwards Compatibility wrapper yang menggabungkan seluruh baris)
     */
    public static function parseXls(string $path): array
    {
        $sheets = self::parseXlsSheets($path);
        $merged = [];
        foreach ($sheets as $s) {
            $merged = array_merge($merged, $s['rows'] ?? []);
        }
        return $merged;
    }

    /**
     * Parse HTML Table (.xls) menjadi kumpulan sheet
     */
    protected static function parseHtmlTableSheets(string $html): array
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        if (!str_contains($html, 'charset=UTF-8') && !str_contains($html, 'charset=utf-8')) {
            $html = '<?xml encoding="UTF-8">' . $html;
        }
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $sheets = [];
        $tables = $dom->getElementsByTagName('table');

        if ($tables->length > 0) {
            foreach ($tables as $tIndex => $table) {
                $tableName = $table->getAttribute('id') ?: $table->getAttribute('name') ?: ('Sheet ' . ($tIndex + 1));
                $trList = $table->getElementsByTagName('tr');
                $sheetRows = [];
                foreach ($trList as $tr) {
                    $rowData = [];
                    foreach ($tr->childNodes as $cell) {
                        if ($cell->nodeName === 'th' || $cell->nodeName === 'td') {
                            $colspan = 1;
                            if ($cell instanceof \DOMElement && $cell->hasAttribute('colspan')) {
                                $colspan = max(1, (int)$cell->getAttribute('colspan'));
                            }
                            $val = trim(str_replace(["\xc2\xa0", "\u{00a0}", "&nbsp;"], ' ', $cell->textContent));
                            $rowData[] = $val;
                            for ($k = 1; $k < $colspan; $k++) {
                                $rowData[] = '';
                            }
                        }
                    }
                    if (!empty($rowData) && count(array_filter($rowData, fn($v) => $v !== '')) > 0) {
                        $sheetRows[] = $rowData;
                    }
                }
                if (!empty($sheetRows)) {
                    $sheets[] = [
                        'name' => $tableName,
                        'rows' => $sheetRows,
                    ];
                }
            }
        } else {
            $trList = $dom->getElementsByTagName('tr');
            $sheetRows = [];
            foreach ($trList as $tr) {
                $rowData = [];
                foreach ($tr->childNodes as $cell) {
                    if ($cell->nodeName === 'th' || $cell->nodeName === 'td') {
                        $colspan = 1;
                        if ($cell instanceof \DOMElement && $cell->hasAttribute('colspan')) {
                            $colspan = max(1, (int)$cell->getAttribute('colspan'));
                        }
                        $val = trim(str_replace(["\xc2\xa0", "\u{00a0}", "&nbsp;"], ' ', $cell->textContent));
                        $rowData[] = $val;
                        for ($k = 1; $k < $colspan; $k++) {
                            $rowData[] = '';
                        }
                    }
                }
                if (!empty($rowData) && count(array_filter($rowData, fn($v) => $v !== '')) > 0) {
                    $sheetRows[] = $rowData;
                }
            }
            if (!empty($sheetRows)) {
                $sheets[] = [
                    'name' => 'Sheet 1',
                    'rows' => $sheetRows,
                ];
            }
        }

        return $sheets;
    }

    /**
     * Parse HTML Table (.xls) tunggal (Backwards compatibility)
     */
    protected static function parseHtmlTable(string $html): array
    {
        $sheets = self::parseHtmlTableSheets($html);
        return $sheets[0]['rows'] ?? [];
    }

    /**
     * Parse XML Spreadsheet 2003 menjadi multi-sheet
     */
    protected static function parseXmlSpreadsheetSheets(string $xmlContent): array
    {
        $cleanXml = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $xmlContent);
        $cleanXml = preg_replace('/[a-zA-Z0-9]+:([a-zA-Z0-9]+)/', '$1', $cleanXml);

        $xml = @simplexml_load_string($cleanXml, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);
        if (!$xml) {
            return [];
        }

        $sheets = [];
        $worksheets = $xml->xpath('//Worksheet');
        if (empty($worksheets)) {
            $worksheets = [$xml];
        }

        foreach ($worksheets as $index => $ws) {
            $wsName = (string)($ws['Name'] ?? ('Sheet ' . ($index + 1)));
            $xmlRows = $ws->xpath('.//Row');
            $sheetRows = [];
            foreach ($xmlRows as $r) {
                $cells = $r->xpath('.//Cell');
                $cellsByCol = [];
                $maxCol = 0;
                $colIndex = 0;
                foreach ($cells as $c) {
                    $attrIndex = (int)($c['Index'] ?? 0);
                    if ($attrIndex > 0) {
                        $colIndex = $attrIndex - 1;
                    }

                    $data = $c->xpath('.//Data');
                    $val = !empty($data) ? (string)$data[0] : '';
                    $cellsByCol[$colIndex] = trim(str_replace(["\xc2\xa0", "\u{00a0}", "&nbsp;"], ' ', $val));
                    if ($colIndex > $maxCol) {
                        $maxCol = $colIndex;
                    }

                    $mergeAcross = (int)($c['MergeAcross'] ?? 0);
                    $colIndex += 1 + $mergeAcross;
                }

                $rowData = [];
                for ($i = 0; $i <= $maxCol; $i++) {
                    $rowData[$i] = $cellsByCol[$i] ?? '';
                }

                if (!empty($rowData) && count(array_filter($rowData, fn($v) => $v !== '')) > 0) {
                    $sheetRows[] = $rowData;
                }
            }
            if (!empty($sheetRows)) {
                $sheets[] = [
                    'name' => $wsName,
                    'rows' => $sheetRows,
                ];
            }
        }

        return $sheets;
    }

    /**
     * Parse XML Spreadsheet 2003 tunggal (Backwards compatibility)
     */
    protected static function parseXmlSpreadsheet(string $xmlContent): array
    {
        $sheets = self::parseXmlSpreadsheetSheets($xmlContent);
        $merged = [];
        foreach ($sheets as $s) {
            $merged = array_merge($merged, $s['rows'] ?? []);
        }
        return $merged;
    }

    /**
     * Parse seluruh sheet dalam berkas XLSX (OpenXML ZIP)
     * Mengembalikan array: [ ['name' => 'Nama Sheet', 'rows' => [...]], ... ]
     */
    public static function parseXlsxSheets(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        // 1. Baca sharedStrings.xml jika ada
        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $cleanXml = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $sharedXml);
            $cleanXml = preg_replace('/[a-zA-Z0-9]+:([a-zA-Z0-9]+)/', '$1', $cleanXml);
            $sXml = @simplexml_load_string($cleanXml, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);
            if ($sXml && isset($sXml->si)) {
                foreach ($sXml->si as $item) {
                    if (isset($item->t)) {
                        $sharedStrings[] = (string)$item->t;
                    } elseif (isset($item->r)) {
                        $parts = [];
                        foreach ($item->r as $run) {
                            $parts[] = (string)($run->t ?? '');
                        }
                        $sharedStrings[] = implode('', $parts);
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Baca relationships untuk memetakan relationship ID ke path berkas worksheet
        $relMap = [];
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($relsXml !== false) {
            $cleanRels = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $relsXml);
            $rXml = @simplexml_load_string($cleanRels, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);
            if ($rXml && isset($rXml->Relationship)) {
                foreach ($rXml->Relationship as $rel) {
                    $id = (string)($rel['Id'] ?? '');
                    $target = (string)($rel['Target'] ?? '');
                    if (!empty($id) && !empty($target)) {
                        $target = ltrim($target, '/');
                        if (!str_starts_with($target, 'xl/')) {
                            $target = 'xl/' . $target;
                        }
                        $relMap[$id] = $target;
                    }
                }
            }
        }

        // 3. Baca daftar sheet dari xl/workbook.xml
        $sheetDefinitions = [];
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        if ($workbookXml !== false) {
            $cleanWb = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $workbookXml);
            $cleanWb = preg_replace('/[a-zA-Z0-9]+:([a-zA-Z0-9]+)/', '$1', $cleanWb);
            $wbXml = @simplexml_load_string($cleanWb, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);
            if ($wbXml && isset($wbXml->sheets->sheet)) {
                foreach ($wbXml->sheets->sheet as $s) {
                    $sName = (string)($s['name'] ?? '');
                    $rId = (string)($s['id'] ?? $s['rId'] ?? '');
                    $targetPath = $relMap[$rId] ?? '';
                    if (empty($targetPath)) {
                        $sheetId = (string)($s['sheetId'] ?? '');
                        $targetPath = 'xl/worksheets/sheet' . $sheetId . '.xml';
                    }
                    $sheetDefinitions[] = [
                        'name' => $sName ?: ('Sheet ' . (count($sheetDefinitions) + 1)),
                        'path' => $targetPath,
                    ];
                }
            }
        }

        // Fallback jika xl/workbook.xml tidak ditemukan / kosong: cari seluruh file worksheet di ZIP
        if (empty($sheetDefinitions)) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $entryName = $stat['name'];
                if (preg_match('#^xl/worksheets/sheet\d*\.xml$#i', $entryName)) {
                    $sheetDefinitions[] = [
                        'name' => 'Sheet ' . (count($sheetDefinitions) + 1),
                        'path' => $entryName,
                    ];
                }
            }
        }

        // 4. Parse setiap sheet yang ditemukan
        $sheets = [];
        foreach ($sheetDefinitions as $sDef) {
            $sheetXml = $zip->getFromName($sDef['path']);
            if ($sheetXml === false) {
                continue;
            }
            $rows = self::parseSingleXlsxSheet($sheetXml, $sharedStrings);
            if (!empty($rows)) {
                $sheets[] = [
                    'name' => $sDef['name'],
                    'rows' => $rows,
                ];
            }
        }

        $zip->close();
        return $sheets;
    }

    /**
     * Parse XLSX (Backwards Compatibility wrapper yang menggabungkan seluruh baris dari semua sheet)
     */
    public static function parseXlsx(string $path): array
    {
        $sheets = self::parseXlsxSheets($path);
        $merged = [];
        foreach ($sheets as $s) {
            $merged = array_merge($merged, $s['rows'] ?? []);
        }
        return $merged;
    }

    /**
     * Parse satu berkas XML sheet XLSX menjadi baris array
     */
    public static function parseSingleXlsxSheet(string $sheetXml, array $sharedStrings): array
    {
        $cleanSheet = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $sheetXml);
        $cleanSheet = preg_replace('/[a-zA-Z0-9]+:([a-zA-Z0-9]+)/', '$1', $cleanSheet);
        $sXml = @simplexml_load_string($cleanSheet, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);
        if (!$sXml || !isset($sXml->sheetData->row)) {
            // Fallback dengan XMLReader streaming untuk berkas spreadsheet berukuran masif
            return self::parseXlsxWithXmlReader($sheetXml, $sharedStrings);
        }

        $rows = [];
        foreach ($sXml->sheetData->row as $r) {
            $rowData = [];
            $maxCol = 0;
            $cellsByCol = [];

            foreach ($r->c as $c) {
                $ref = (string)($c['r'] ?? '');
                $type = (string)($c['t'] ?? '');
                $val = isset($c->v) ? (string)$c->v : '';

                if ($type === 's' && isset($sharedStrings[(int)$val])) {
                    $cellVal = $sharedStrings[(int)$val];
                } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                    $cellVal = (string)$c->is->t;
                } else {
                    $cellVal = $val;
                }

                $colIndex = !empty($ref) ? self::columnLetterToIndex($ref) : count($cellsByCol);
                $cellsByCol[$colIndex] = trim($cellVal);
                if ($colIndex > $maxCol) {
                    $maxCol = $colIndex;
                }
            }

            for ($i = 0; $i <= $maxCol; $i++) {
                $rowData[$i] = $cellsByCol[$i] ?? '';
            }

            if (!empty($rowData) && count(array_filter($rowData)) > 0) {
                $rows[] = $rowData;
            }
        }

        return $rows;
    }

    /**
     * Parsing XLSX dengan XMLReader (Streaming, sangat efisien untuk lembar kerja masif / puluhan ribu baris)
     */
    protected static function parseXlsxWithXmlReader(string $sheetXml, array $sharedStrings): array
    {
        $reader = new \XMLReader();
        if (!$reader->XML($sheetXml, null, LIBXML_COMPACT | LIBXML_PARSEHUGE)) {
            return [];
        }

        $rows = [];
        $currentRow = [];
        $currentColIndex = 0;
        $maxCol = 0;
        $currentVal = '';
        $currentType = '';
        $inVal = false;
        $inInline = false;

        while ($reader->read()) {
            if ($reader->nodeType === \XMLReader::ELEMENT) {
                $nodeName = $reader->localName;
                if ($nodeName === 'row') {
                    $currentRow = [];
                    $maxCol = 0;
                } elseif ($nodeName === 'c') {
                    $ref = $reader->getAttribute('r') ?? '';
                    $currentType = $reader->getAttribute('t') ?? '';
                    $currentColIndex = self::columnLetterToIndex($ref);
                    $currentVal = '';
                } elseif ($nodeName === 'v') {
                    $inVal = true;
                } elseif ($nodeName === 't') {
                    $inInline = true;
                }
            } elseif ($reader->nodeType === \XMLReader::TEXT || $reader->nodeType === \XMLReader::CDATA) {
                if ($inVal || $inInline) {
                    $currentVal .= $reader->value;
                }
            } elseif ($reader->nodeType === \XMLReader::END_ELEMENT) {
                $nodeName = $reader->localName;
                if ($nodeName === 'v') {
                    $inVal = false;
                } elseif ($nodeName === 't') {
                    $inInline = false;
                } elseif ($nodeName === 'c') {
                    $cellVal = $currentVal;
                    if ($currentType === 's' && isset($sharedStrings[(int)$cellVal])) {
                        $cellVal = $sharedStrings[(int)$cellVal];
                    }
                    $currentRow[$currentColIndex] = trim($cellVal);
                    if ($currentColIndex > $maxCol) {
                        $maxCol = $currentColIndex;
                    }
                } elseif ($nodeName === 'row') {
                    $rowData = [];
                    for ($i = 0; $i <= $maxCol; $i++) {
                        $rowData[$i] = $currentRow[$i] ?? '';
                    }
                    if (!empty($rowData) && count(array_filter($rowData)) > 0) {
                        $rows[] = $rowData;
                    }
                }
            }
        }
        $reader->close();
        return $rows;
    }

    /**
     * Konversi referensi sel (misal: 'A1', 'B2', 'AA10') ke indeks kolom numerik 0-based.
     */
    protected static function columnLetterToIndex(string $cellRef): int
    {
        $letters = preg_replace('/[^A-Z]/i', '', strtoupper($cellRef));
        if (empty($letters)) {
            return 0;
        }

        $index = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }

    /**
     * Memetakan header baris Excel ke nama atribut model yang dinormalisasi.
     */
    public static function mapHeaders(array $headerRow): array
    {
        $map = [];

        $synonyms = [
            'order_number' => [
                'id_no_pelanggan', 'no_pelanggan', 'nomor_pelanggan', 'id_pelanggan', 'kode_pelanggan',
                'id_member', 'member_id', 'no_order', 'nomor_order', 'no_langganan', 'nomor_langganan',
                'id_cust', 'cust_id', 'customer_id', 'cid', 'user_id', 'id_user', 'order_number',
                'no_pel', 'nopel', 'id',
            ],
            'id_card_number' => [
                'no_ktp_nik_pelanggan', 'no_ktp_nik', 'nomor_ktp_nik', 'no_ktp', 'nomor_ktp',
                'no_nik', 'nomor_nik', 'nik_ktp', 'ktp_nik', 'no_identitas', 'nomor_identitas',
                'identitas', 'nik', 'ktp', 'id_card_number', 'id_card', 'kartu_identitas',
            ],
            'customer_name' => [
                'nama_lengkap_pelanggan', 'nama_lengkap', 'nama_pelanggan', 'nama_konsumen',
                'nama_pemohon', 'nama_user', 'nama_client', 'atas_nama', 'a_n', 'an',
                'pelanggan', 'customer_name', 'customer', 'client', 'nama', 'name',
            ],
            'birth_place' => [
                'tempat_lahir', 'kota_lahir', 'tmp_lahir', 'tmpt_lahir', 'tempat_kelahiran',
                'birth_place', 'pob',
            ],
            'birth_date' => [
                'tanggal_lahir', 'tgl_lahir_pelanggan', 'tgl_lahir', 'tgl_kelahiran',
                'tanggal_kelahiran', 'birth_date', 'date_of_birth', 'dob',
            ],
            'ttl' => [
                'ttl', 'tempat_tanggal_lahir', 'tempat_tgl_lahir', 'tempat_dan_tanggal_lahir',
                'tmp_tgl_lahir', 'tempat_dan_tgl_lahir',
            ],
            'customer_phone' => [
                'no_handphone_wa', 'no_handphone', 'nomor_handphone', 'no_hp_wa', 'no_hp',
                'nomor_hp', 'no_wa', 'nomor_wa', 'whatsapp', 'no_whatsapp', 'nomor_whatsapp',
                'no_telepon', 'nomor_telepon', 'telepon', 'no_telp', 'nomor_telp', 'telp',
                'kontak', 'no_kontak', 'nomor_kontak', 'customer_phone', 'phone', 'mobile',
                'handphone', 'hp', 'wa',
            ],
            'customer_email' => [
                'alamat_email', 'email_pelanggan', 'email_address', 'customer_email',
                'email', 'mail', 'e_mail', 'surel',
            ],
            'package_name' => [
                'jenis_layanan_paket', 'jenis_layanan', 'layanan_paket', 'nama_paket',
                'paket_internet', 'paket_berlangganan', 'paket_layanan', 'paket', 'layanan',
                'produk', 'profil', 'profile', 'plan', 'internet_plan', 'package_name',
                'package', 'service',
            ],
            'speed' => [
                'kecepatan_internet', 'kecepatan', 'bandwidth', 'speed', 'bw', 'mbps',
                'kapasitas', 'speed_mbps',
            ],
            'price' => [
                'harga_bulan_rp', 'harga_bulan', 'harga_bln', 'harga_rp', 'harga',
                'tarif_bulan_rp', 'tarif_bulan', 'tarif_bln', 'tarif_rp', 'tarif',
                'biaya_bulan_rp', 'biaya_bulan', 'biaya_bln', 'biaya_rp', 'biaya_bulanan', 'biaya',
                'harga_bulanan', 'tarif_bulanan', 'iuran_bulanan', 'iuran_bulan', 'iuran_rp', 'iuran',
                'tagihan_bulanan', 'tagihan_bulan', 'tagihan_rp', 'tagihan',
                'nominal_rp', 'nominal_harga', 'nominal',
                'harga_paket', 'tarif_paket', 'biaya_paket',
                'harga_langganan', 'tarif_langganan', 'biaya_langganan', 'biaya_langganan_rp',
                'harga_layanan', 'tarif_layanan', 'biaya_layanan',
                'biaya_per_bulan', 'harga_per_bulan', 'tarif_per_bulan',
                'iuran_per_bulan', 'tagihan_per_bulan',
                'total_harga', 'total_biaya', 'total_tagihan', 'total', 'jumlah_tagihan', 'jumlah', 'rp',
                'price', 'amount', 'cost', 'fee', 'monthly_fee',
            ],
            'address' => [
                'alamat_lengkap_pemasangan', 'alamat_lengkap', 'alamat_pemasangan', 'alamat_rumah',
                'alamat_domisili', 'alamat_pelanggan', 'alamat', 'address', 'lokasi_pemasangan', 'lokasi',
            ],
            'rt_rw' => ['rt_rw', 'rt_dan_rw', 'rt', 'rw'],
            'village' => ['desa_kelurahan', 'kelurahan_desa', 'desa', 'kelurahan', 'dusun', 'dukuh', 'wilayah'],
            'district' => ['kecamatan', 'kec'],
            'regency' => ['kabupaten_kota', 'kabupaten', 'kab', 'kota'],
            'status' => [
                'status_berlangganan', 'status_pelanggan', 'status_order', 'status_layanan',
                'status', 'status_aktif',
            ],
            'payment_status' => [
                'status_pembayaran', 'status_bayar', 'payment_status', 'pembayaran', 'bayar',
            ],
            'payment_method' => [
                'metode_pembayaran', 'cara_pembayaran', 'payment_method', 'cara_bayar', 'metode_bayar',
            ],
            'technician' => [
                'petugas_teknisi', 'nama_teknisi', 'tim_teknisi', 'pic_teknisi', 'teknisi',
                'technician', 'installer',
            ],
            'assigned_odp' => [
                'assigned_odp', 'kode_odp', 'nama_odp', 'port_odp', 'titik_odp', 'odp', 'odc', 'fdt',
            ],
            'admin_notes' => [
                'catatan_admin', 'keterangan_tambahan', 'catatan', 'keterangan', 'admin_notes',
                'notes', 'remark', 'remarks', 'ket',
            ],
            'registration_date' => [
                'tanggal_terdaftar', 'tgl_terdaftar', 'tanggal_registrasi', 'tgl_registrasi',
                'tanggal_daftar', 'tgl_daftar', 'tanggal_masuk', 'tgl_masuk',
                'registration_date', 'registered_at', 'created_at',
            ],
            'installation_date' => [
                'tanggal_pemasangan', 'tgl_pemasangan', 'tanggal_pasang', 'tgl_pasang',
                'tanggal_instalasi', 'tgl_instalasi', 'installation_date', 'install_date',
            ],
            'installation_time' => [
                'waktu_pemasangan', 'jam_pemasangan', 'sesi_pemasangan', 'jam_pasang',
                'installation_time', 'time',
            ],
            'latitude' => ['latitude', 'lat', 'lintang'],
            'longitude' => ['longitude', 'long', 'lng', 'bujur'],
            'coordinates' => ['koordinat', 'titik_koordinat', 'gps', 'coordinates', 'coordinate', 'titik_gps', 'lokasi_gps'],
        ];

        // Pass 1: Exact matches
        foreach ($headerRow as $colIndex => $headerText) {
            $clean = str_replace(["\xc2\xa0", "\u{00a0}", "&nbsp;"], ' ', (string)$headerText);
            $normalized = strtolower(trim($clean));
            $normalized = preg_replace('/[^a-z0-9_]/', '_', $normalized);
            $normalized = preg_replace('/_+/', '_', $normalized);
            $normalized = trim($normalized, '_');

            foreach ($synonyms as $field => $terms) {
                if (!isset($map[$field]) && in_array($normalized, $terms, true)) {
                    $map[$field] = $colIndex;
                    break;
                }
            }
        }

        // Pass 2: Partial matches (excluding mismatched types)
        foreach ($headerRow as $colIndex => $headerText) {
            $clean = str_replace(["\xc2\xa0", "\u{00a0}", "&nbsp;"], ' ', (string)$headerText);
            $normalized = strtolower(trim($clean));
            $normalized = preg_replace('/[^a-z0-9_]/', '_', $normalized);
            $normalized = preg_replace('/_+/', '_', $normalized);
            $normalized = trim($normalized, '_');

            if (in_array($colIndex, $map, true)) {
                continue;
            }

            foreach ($synonyms as $field => $terms) {
                if (!isset($map[$field])) {
                    if ($field === 'address' && (str_contains($normalized, 'email') || str_contains($normalized, 'mail'))) {
                        continue;
                    }
                    if ($field === 'customer_name' && (str_contains($normalized, 'paket') || str_contains($normalized, 'teknisi') || str_contains($normalized, 'odp') || str_contains($normalized, 'desa') || str_contains($normalized, 'ibu') || str_contains($normalized, 'ayah'))) {
                        continue;
                    }
                    if ($field === 'package_name' && (str_contains($normalized, 'harga') || str_contains($normalized, 'tarif') || str_contains($normalized, 'biaya') || str_contains($normalized, 'rp') || str_contains($normalized, 'nominal') || str_contains($normalized, 'tagihan') || str_contains($normalized, 'iuran') || str_contains($normalized, 'kecepatan') || str_contains($normalized, 'speed'))) {
                        continue;
                    }
                    if ($field === 'speed' && (str_contains($normalized, 'harga') || str_contains($normalized, 'tarif') || str_contains($normalized, 'biaya') || str_contains($normalized, 'rp') || str_contains($normalized, 'nominal'))) {
                        continue;
                    }
                    if ($field === 'order_number' && (str_contains($normalized, 'ktp') || str_contains($normalized, 'nik') || str_contains($normalized, 'hp') || str_contains($normalized, 'wa') || str_contains($normalized, 'telp') || str_contains($normalized, 'telepon'))) {
                        continue;
                    }
                    if ($field === 'birth_place' && (str_contains($normalized, 'tanggal') || str_contains($normalized, 'tgl') || str_contains($normalized, 'date'))) {
                        continue;
                    }
                    if ($field === 'birth_date' && (str_contains($normalized, 'tempat') || str_contains($normalized, 'tmp') || str_contains($normalized, 'place') || str_contains($normalized, 'kota'))) {
                        continue;
                    }
                    if ($field === 'registration_date' && (str_contains($normalized, 'pasang') || str_contains($normalized, 'instalasi') || str_contains($normalized, 'lahir'))) {
                        continue;
                    }
                    if ($field === 'installation_date' && (str_contains($normalized, 'daftar') || str_contains($normalized, 'registrasi') || str_contains($normalized, 'lahir'))) {
                        continue;
                    }

                    foreach ($terms as $term) {
                        if (str_contains($normalized, $term)) {
                            $map[$field] = $colIndex;
                            break;
                        }
                    }
                }
            }
        }

        return $map;
    }

    /**
     * Mengambil nilai sel dari baris data berdasarkan fieldMap.
     */
    protected static function getValue(array $row, array $fieldMap, string $field): string
    {
        if (isset($fieldMap[$field]) && isset($row[$fieldMap[$field]])) {
            return trim(str_replace(["\xc2\xa0", "\u{00a0}"], ' ', (string)$row[$fieldMap[$field]]));
        }
        return '';
    }

    /**
     * Parse format tanggal yang fleksibel (YYYY-MM-DD, DD/MM/YYYY, DD-MM-YYYY, teks bahasa Indonesia, atau angka serial Excel)
     */
    protected static function parseDate(?string $dateStr): ?string
    {
        if (empty($dateStr)) {
            return null;
        }

        $dateStr = trim(str_replace(["\xc2\xa0", "\u{00a0}"], ' ', (string)$dateStr));
        if ($dateStr === '' || $dateStr === '-' || $dateStr === '0') {
            return null;
        }

        // Jika angka serial Excel (misal: 31048 untuk tanggal)
        if (is_numeric($dateStr) && (float)$dateStr > 1000 && (float)$dateStr < 60000) {
            try {
                // Serial 1 = 1899-12-30
                $base = Carbon::create(1899, 12, 30);
                return $base->addDays((int)$dateStr)->toDateString();
            } catch (\Exception $e) {}
        }

        // Terjemahkan nama bulan bahasa Indonesia ke bahasa Inggris
        $indoMonths = [
            'januari' => 'january', 'jan' => 'jan',
            'februari' => 'february', 'pebruari' => 'february', 'feb' => 'feb',
            'maret' => 'march', 'mar' => 'mar',
            'april' => 'april', 'apr' => 'apr',
            'mei' => 'may',
            'juni' => 'june', 'jun' => 'jun',
            'juli' => 'july', 'jul' => 'jul',
            'agustus' => 'august', 'ags' => 'aug', 'agt' => 'aug',
            'september' => 'september', 'sep' => 'sep', 'sept' => 'sep',
            'oktober' => 'october', 'okt' => 'oct',
            'november' => 'november', 'nopember' => 'november', 'nov' => 'nov', 'nop' => 'nov',
            'desember' => 'december', 'des' => 'dec',
        ];

        $translated = strtolower($dateStr);
        foreach ($indoMonths as $idName => $enName) {
            $translated = preg_replace('/\b' . $idName . '\b/i', $enName, $translated);
        }

        // Coba parsing standar YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $translated)) {
            try {
                return Carbon::parse($translated)->toDateString();
            } catch (\Exception $e) {}
        }

        // Coba parsing format DD/MM/YYYY atau DD-MM-YYYY
        if (preg_match('/^(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{4})$/', $translated, $m)) {
            try {
                return Carbon::create((int)$m[3], (int)$m[2], (int)$m[1])->toDateString();
            } catch (\Exception $e) {}
        }

        // Coba Carbon::parse setelah nama bulan diterjemahkan
        try {
            return Carbon::parse($translated)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse nilai harga dari berbagai format (Rp 150.000, Rp. 150.000, 150.000, 150,000, 150000, 150k, 150rb, dsb)
     */
    public static function parsePrice(?string $raw): float
    {
        if ($raw === null) {
            return 0.0;
        }

        $raw = trim($raw);
        if ($raw === '' || $raw === '-' || $raw === '0') {
            return 0.0;
        }

        // 1. Cek format singkatan ribuan: 150k, 150rb, 150 rb, 150 ribu
        if (preg_match('/^(\d+(?:[.,]\d+)?)\s*(?:k|rb|ribu)\b/i', $raw, $m)) {
            $num = (float) str_replace(',', '.', $m[1]);
            return round($num * 1000, 2);
        }

        // 2. Bersihkan prefix mata uang: Rp, Rp., IDR, dsb
        $clean = preg_replace('/^(?:rp\.?|idr)\s*/i', '', $raw);
        $clean = trim($clean);

        // 3. Bersihkan postfix: ,-, /bln, /bulan, / bln, per bulan, dsb
        $clean = preg_replace('/(?:,-\s*|\/\s*(?:bln|bulan|month)|per\s*(?:bln|bulan))$/i', '', $clean);
        $clean = trim($clean);

        // 4. Deteksi format angka Indonesia vs Internasional:
        // Format Indonesia khas: "150.000" atau "150.000,00" atau "1.500.000"
        if (str_contains($clean, '.') && str_contains($clean, ',')) {
            // Ada titik dan koma:
            // Jika titik sebelum koma (e.g. 150.000,00) -> format Indonesia (titik = ribuan, koma = desimal)
            if (strrpos($clean, '.') < strrpos($clean, ',')) {
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } else {
                // Koma sebelum titik (e.g. 150,000.00) -> format Inggris (koma = ribuan, titik = desimal)
                $clean = str_replace(',', '', $clean);
            }
        } elseif (str_contains($clean, '.')) {
            // Hanya ada titik:
            // Cek apakah titik merupakan pemisah ribuan (misal: 150.000 atau 1.500.000 atau 20.000)
            if (preg_match('/^\d{1,3}(?:\.\d{3})+$/', $clean)) {
                $clean = str_replace('.', '', $clean);
            } elseif (preg_match('/^\d+\.\d{3}$/', $clean)) {
                $clean = str_replace('.', '', $clean);
            }
        } elseif (str_contains($clean, ',')) {
            // Hanya ada koma:
            // Cek apakah koma merupakan pemisah ribuan (misal: 150,000 atau 1,500,000)
            if (preg_match('/^\d{1,3}(?:,\d{3})+$/', $clean) || preg_match('/^\d+,\d{3}$/', $clean)) {
                $clean = str_replace(',', '', $clean);
            } else {
                // Koma desimal: 150,5
                $clean = str_replace(',', '.', $clean);
            }
        }

        // Hapus karakter sisa non-numerik kecuali titik desimal
        $clean = preg_replace('/[^0-9.]/', '', $clean);
        if ($clean === '' || $clean === '.') {
            return 0.0;
        }

        $val = (float) $clean;

        // Jika angka bulat kecil (antara 20 s/d 999) seperti 100, 110, 150, 165, 200, 220, 250, 300
        // Seringkali user menginput harga dalam kelipatan ribu di Excel (e.g. 150 = 150 ribu)
        if ($val >= 20 && $val < 1000 && floor($val) == $val) {
            $val = $val * 1000;
        }

        return round($val, 2);
    }

    /**
     * Cek apakah sebuah teks / nama sheet merupakan nama generik (misal: "Template Pelanggan", "Sheet 1", "Rekap Data")
     * dan TIDAK BOLEH dianggap sebagai nama desa.
     */
    public static function isGenericSheetName(?string $name): bool
    {
        if (empty($name)) {
            return true;
        }

        $clean = strtolower(trim($name));

        // Format sheet standar bawaan spreadsheet: sheet, sheet 1, worksheet, tabel 1, dsb
        if (preg_match('/^(?:sheet|worksheet|halaman|table|tabel)\s*\d*$/i', $clean)) {
            return true;
        }

        // Kata kunci sistem / berkas yang pasti bukan nama desa
        $genericKeywords = [
            'template', 'pelanggan', 'customer', 'konsumen', 'client', 'user',
            'rekap', 'laporan', 'report', 'export', 'import', 'master',
            'data', 'database', 'pesanan', 'order', 'all', 'semua',
            'wifi', 'banterpool', 'isp', 'billing', 'tagihan', 'pendaftaran',
            'sims', 'formulir', 'daftar'
        ];

        foreach ($genericKeywords as $kw) {
            if (str_contains($clean, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ekstrak dan normalisasi nama desa dari nilai baris Excel (kolom desa, teks alamat, catatan, atau sheet).
     * Memprioritaskan data spesifik pelanggan (Alamat & Kolom Desa) di atas nama sheet.
     */
    public static function resolveVillage(?string $villageCol, ?string $sheetName, ?string $address, ?string $notes = null): ?string
    {
        // 1. Jika ada nilai kolom desa/wilayah eksplisit dari file Excel (dan bukan kata generik)
        if (!empty($villageCol) && $villageCol !== '-') {
            $clean = trim(preg_replace('/^(?:desa|kelurahan|kel\.?)\s+/i', '', $villageCol));
            if (!empty($clean) && !self::isGenericSheetName($clean)) {
                return self::normalizeVillageName($clean);
            }
        }

        // 2. PRIORITAS UTAMA: Ekstrak nama desa dari teks ALAMAT pelanggan
        if (!empty($address) && $address !== '-') {
            // Normalisasi khusus varian nama desa umum jika tertulis di alamat
            $standardMap = [
                'linggasari' => 'Linggasari',
                'bantarwuni' => 'Bantarwuni',
                'kasegeran' => 'Kasegeran',
                'cipete' => 'Cipete',
                'pageraji' => 'Pageraji',
                'sudimara' => 'Sudimara',
                'notog' => 'Notog',
                'sawangan' => 'Sawangan',
                'jatisaba' => 'Jatisaba',
                'batuanten' => 'Batuanten',
                'bantuanten' => 'Batuanten',
                'karangendep' => 'Karangendep',
                'karanggendep' => 'Karangendep',
                'karang endep' => 'Karangendep',
                'karang endp' => 'Karangendep',
                'krangendep' => 'Karangendep',
                'karang ednep' => 'Karangendep',
                'ronten' => 'Karangendep',
                'penusupan' => 'Penusupan',
                'panusupan' => 'Penusupan',
                'pecikalan' => 'Penusupan',
                'tinggar jaya' => 'Penusupan',
                'bojongsari' => 'Penusupan',
                'legok' => 'Penusupan',
            ];
            $addrLower = strtolower($address);
            foreach ($standardMap as $pattern => $normalized) {
                if (str_contains($addrLower, $pattern)) {
                    return $normalized;
                }
            }

            // Cek awalan "Desa [Nama]" atau "Kelurahan [Nama]"
            if (preg_match('/(?:desa|kelurahan|kel\.?)\s+([A-Za-z0-9\s]+?)(?:,|\.|\/|\s+(?:rt|rw|\d)|$)/i', $address, $m)) {
                $clean = trim($m[1]);
                if (!empty($clean) && strlen($clean) >= 3 && !self::isGenericSheetName($clean)) {
                    return self::normalizeVillageName($clean);
                }
            }

            // Ambil potongan kata pertama sebelum koma / tanda baca
            $parts = preg_split('/[,.\/]/', $address);
            if (!empty($parts[0])) {
                $first = trim(preg_replace('/^(?:desa|kelurahan|kel\.?)\s+/i', '', $parts[0]));
                if (strlen($first) >= 3 && !in_array(strtolower($first), ['rt', 'rw', 'jl', 'jalan', 'gang', 'gg', 'blok', 'kec', 'kecamatan']) && !self::isGenericSheetName($first)) {
                    return self::normalizeVillageName($first);
                }
            }
        }

        // 3. Ekstrak dari catatan admin jika ada
        if (!empty($notes) && $notes !== '-') {
            if (preg_match('/\(([A-Za-z0-9\s]+?)\)/', $notes, $nm)) {
                $candidate = trim($nm[1]);
                if (strlen($candidate) >= 3 && !self::isGenericSheetName($candidate)) {
                    return self::normalizeVillageName($candidate);
                }
            }
        }

        // 4. Fallback Terakhir: Hanya gunakan nama sheet jika nama sheet tersebut benar-benar nama desa spesifik (BUKAN generic / template)
        if (!empty($sheetName) && !self::isGenericSheetName($sheetName)) {
            $clean = trim(preg_replace('/^(?:desa|kelurahan|kel\.?)\s+/i', '', $sheetName));
            if (!empty($clean)) {
                return self::normalizeVillageName($clean);
            }
        }

        return null;
    }

    /**
     * Standarisasi format penulisan kapital nama desa.
     */
    public static function normalizeVillageName(string $name): string
    {
        $name = trim($name);
        $lower = strtolower($name);

        if ($lower === 'bantuanten') return 'Batuanten';
        if ($lower === 'panusupan') return 'Penusupan';
        if (in_array($lower, ['karanggendep', 'karang endep', 'karang endp', 'krangendep', 'karang ednep', 'ronten'], true)) return 'Karangendep';

        return ucwords($lower);
    }
}
