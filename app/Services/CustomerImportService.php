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
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        $rows = [];
        try {
            if ($extension === 'csv' || $extension === 'txt') {
                $rows = self::parseCsv($path);
            } elseif ($extension === 'xlsx') {
                $rows = self::parseXlsx($path);
            } elseif ($extension === 'xls') {
                $rows = self::parseXls($path);
            } else {
                // Percobaan deteksi otomatis
                $rows = self::parseXlsx($path);
                if (empty($rows)) {
                    $rows = self::parseXls($path);
                }
                if (empty($rows)) {
                    $rows = self::parseCsv($path);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Error parsing import file: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal membaca isi berkas file: ' . $e->getMessage(),
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => ['Format file tidak dikenali atau rusak.'],
            ];
        }

        if (empty($rows)) {
            return [
                'success' => false,
                'message' => 'File tidak memiliki baris data pelanggan yang valid atau kosong.',
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => ['File kosong atau format baris tidak terdeteksi.'],
            ];
        }

        $headerRow = array_shift($rows);
        $fieldMap = self::mapHeaders($headerRow);

        if (empty($fieldMap['customer_name']) && empty($fieldMap['address'])) {
            return [
                'success' => false,
                'message' => 'Kolom wajib "Nama Lengkap" dan "Alamat" tidak ditemukan pada baris judul file.',
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => ['Pastikan file memiliki header kolom Nama Pelanggan dan Alamat.'],
            ];
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            $totalOrdersCount = Order::count();

            foreach ($rows as $index => $row) {
                $rowNum = $index + 2; // Baris ke-2 (karena baris 1 header)
                
                // Ambil data berdasarkan mapping header
                $name = self::getValue($row, $fieldMap, 'customer_name');
                $address = self::getValue($row, $fieldMap, 'address');
                
                // Baris kosong atau baris rekap total diabaikan
                if (empty($name) && empty($address)) {
                    continue;
                }
                if (stripos($name, 'TOTAL') !== false || stripos($address, 'TOTAL') !== false) {
                    continue;
                }

                if (empty($name)) {
                    $skipped++;
                    $errors[] = "Baris #{$rowNum}: Dilewati karena nama pelanggan kosong.";
                    continue;
                }

                if (empty($address)) {
                    $skipped++;
                    $errors[] = "Baris #{$rowNum} ({$name}): Dilewati karena alamat belum diisi.";
                    continue;
                }

                $nik = self::getValue($row, $fieldMap, 'id_card_number');
                $nik = preg_replace('/[^0-9]/', '', (string)$nik);
                if (empty($nik)) {
                    // Fallback generate NIK sementara jika tidak diisi
                    $nik = '3302' . str_pad((string)($totalOrdersCount + $imported + 1), 12, '0', STR_PAD_LEFT);
                }

                $orderNumber = self::getValue($row, $fieldMap, 'order_number');
                $phone = self::getValue($row, $fieldMap, 'customer_phone');
                if (empty($phone)) {
                    $phone = '08' . rand(1111111111, 9999999999);
                } else {
                    $phone = preg_replace('/[^0-9+]/', '', (string)$phone);
                    if (str_starts_with($phone, '62')) {
                        $phone = '0' . substr($phone, 2);
                    }
                }

                $email = self::getValue($row, $fieldMap, 'customer_email');
                if (empty($email)) {
                    $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
                    $email = ($cleanName ?: 'pelanggan' . ($totalOrdersCount + $imported + 1)) . '@gmail.com';
                }

                $packageName = self::getValue($row, $fieldMap, 'package_name') ?: 'Paket 20 Mbps';
                $speed = self::getValue($row, $fieldMap, 'speed');
                if (empty($speed)) {
                    if (str_contains($packageName, '50')) {
                        $speed = '50 Mbps';
                    } elseif (str_contains($packageName, '30')) {
                        $speed = '30 Mbps';
                    } else {
                        $speed = '20 Mbps';
                    }
                }

                $price = self::getValue($row, $fieldMap, 'price');
                $price = (float) preg_replace('/[^0-9.]/', '', (string)$price);
                if ($price <= 0) {
                    if (str_contains($speed, '50')) {
                        $price = 220000;
                    } elseif (str_contains($speed, '30')) {
                        $price = 165000;
                    } else {
                        $price = 110000;
                    }
                }

                $birthPlace = self::getValue($row, $fieldMap, 'birth_place') ?: 'Banyumas';
                $birthDateRaw = self::getValue($row, $fieldMap, 'birth_date');
                $birthDate = self::parseDate($birthDateRaw);

                $status = self::getValue($row, $fieldMap, 'status') ?: 'Selesai';
                $validStatuses = ['Selesai', 'Menunggu Konfirmasi', 'Jadwal Pemasangan', 'Sedang Dipasang', 'Dibatalkan'];
                if (!in_array($status, $validStatuses)) {
                    $status = 'Selesai';
                }

                // Cek apakah pelanggan sudah ada (berdasarkan NIK atau ID Pelanggan)
                $existingOrder = null;
                if (!empty($orderNumber)) {
                    $existingOrder = Order::where('order_number', $orderNumber)->first();
                }
                if (!$existingOrder && !empty($nik) && strlen($nik) >= 10) {
                    $existingOrder = Order::where('id_card_number', $nik)->first();
                }

                if ($existingOrder) {
                    if ($updateExisting) {
                        $existingOrder->customer_name = $name;
                        $existingOrder->customer_phone = $phone;
                        $existingOrder->customer_email = $email;
                        $existingOrder->address = $address;
                        $existingOrder->package_name = $packageName;
                        $existingOrder->speed = $speed;
                        $existingOrder->price = $price;
                        $existingOrder->total = $price;
                        if (!empty($birthDate)) {
                            $existingOrder->birth_date = $birthDate;
                        }
                        if (!empty($birthPlace)) {
                            $existingOrder->birth_place = $birthPlace;
                        }
                        $existingOrder->status = $status;
                        $existingOrder->save();
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    if (empty($orderNumber)) {
                        $orderNumber = 'PLG-' . date('Y') . '-' . str_pad((string)($totalOrdersCount + $imported + 1), 4, '0', STR_PAD_LEFT);
                        while (Order::where('order_number', $orderNumber)->exists()) {
                            $totalOrdersCount++;
                            $orderNumber = 'PLG-' . date('Y') . '-' . str_pad((string)($totalOrdersCount + $imported + 1), 4, '0', STR_PAD_LEFT);
                        }
                    }

                    Order::create([
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
                        'status' => $status,
                        'payment_status' => 'Lunas',
                        'payment_method' => 'Tunai / Transfer',
                        'installation_date' => now()->toDateString(),
                        'installation_time' => 'pagi',
                        'installed_at' => ($status === 'Selesai') ? now() : null,
                        'technician' => 'Randi Pratama (Tim Fiber)',
                        'assigned_odp' => 'ODP-BAT-01',
                        'admin_notes' => 'Diimpor dari berkas Excel pada ' . now('Asia/Jakarta')->translatedFormat('d M Y H:i'),
                    ]);

                    $imported++;
                }
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

        return [
            'success' => true,
            'message' => "Proses import selesai. {$imported} pelanggan baru berhasil ditambahkan, {$updated} pelanggan diperbarui, dan {$skipped} baris dilewati.",
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
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
            'Harga / Bulan (Rp)',
            'Alamat Lengkap Pemasangan',
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
     * Parse XLS File (HTML table format atau XML 2003)
     */
    protected static function parseXls(string $path): array
    {
        $content = file_get_contents($path);
        if (empty($content)) {
            return [];
        }

        // Cek jika ini format XML Spreadsheet 2003 (<Workbook ...>)
        if (str_contains($content, '<Workbook') && str_contains($content, '<Row>')) {
            return self::parseXmlSpreadsheet($content);
        }

        // Jika ini format HTML Table (.xls berbasis HTML yang umum)
        if (str_contains($content, '<table') || str_contains($content, '<tr')) {
            return self::parseHtmlTable($content);
        }

        // Jika file ternyata CSV yang disimpan dengan ekstensi .xls
        return self::parseCsv($path);
    }

    /**
     * Parse HTML Table (.xls)
     */
    protected static function parseHtmlTable(string $html): array
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        // Tambahkan meta UTF-8 jika belum ada
        if (!str_contains($html, 'charset=UTF-8') && !str_contains($html, 'charset=utf-8')) {
            $html = '<?xml encoding="UTF-8">' . $html;
        }
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $rows = [];
        $trList = $dom->getElementsByTagName('tr');

        foreach ($trList as $tr) {
            $rowData = [];
            $cells = $tr->childNodes;
            foreach ($cells as $cell) {
                if ($cell->nodeName === 'th' || $cell->nodeName === 'td') {
                    $rowData[] = trim($cell->textContent);
                }
            }
            if (!empty($rowData) && count(array_filter($rowData)) > 0) {
                $rows[] = $rowData;
            }
        }

        // Jika ada baris judul pengantar (sebelum baris header utama), cari baris yang berisi kata kunci kolom
        $cleanRows = [];
        $foundHeader = false;
        foreach ($rows as $row) {
            if (!$foundHeader) {
                $joined = strtolower(implode(' ', $row));
                if (str_contains($joined, 'nama') || str_contains($joined, 'ktp') || str_contains($joined, 'nik') || str_contains($joined, 'alamat')) {
                    $foundHeader = true;
                    $cleanRows[] = $row;
                }
            } else {
                $cleanRows[] = $row;
            }
        }

        return !empty($cleanRows) ? $cleanRows : $rows;
    }

    /**
     * Parse XML Spreadsheet 2003
     */
    protected static function parseXmlSpreadsheet(string $xmlContent): array
    {
        $cleanXml = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $xmlContent);
        $cleanXml = preg_replace('/[a-zA-Z0-9]+:([a-zA-Z0-9]+)/', '$1', $cleanXml);

        $xml = @simplexml_load_string($cleanXml);
        if (!$xml) {
            return [];
        }

        $rows = [];
        $worksheets = $xml->xpath('//Worksheet');
        if (empty($worksheets)) {
            $worksheets = [$xml];
        }

        foreach ($worksheets as $ws) {
            $xmlRows = $ws->xpath('.//Row');
            foreach ($xmlRows as $r) {
                $rowData = [];
                $cells = $r->xpath('.//Cell');
                foreach ($cells as $c) {
                    $data = $c->xpath('.//Data');
                    $val = !empty($data) ? (string)$data[0] : '';
                    $rowData[] = trim($val);
                }
                if (!empty($rowData) && count(array_filter($rowData)) > 0) {
                    $rows[] = $rowData;
                }
            }
            if (!empty($rows)) {
                break;
            }
        }

        return $rows;
    }

    /**
     * Parse XLSX (OpenXML ZIP)
     */
    protected static function parseXlsx(string $path): array
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
            $sXml = @simplexml_load_string($cleanXml);
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

        // 2. Baca sheet1.xml
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            // Coba cari nama sheet lain
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (str_starts_with($stat['name'], 'xl/worksheets/sheet') && str_ends_with($stat['name'], '.xml')) {
                    $sheetXml = $zip->getFromIndex($i);
                    break;
                }
            }
        }
        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        $cleanSheet = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $sheetXml);
        $cleanSheet = preg_replace('/[a-zA-Z0-9]+:([a-zA-Z0-9]+)/', '$1', $cleanSheet);
        $sXml = @simplexml_load_string($cleanSheet);
        if (!$sXml || !isset($sXml->sheetData->row)) {
            return [];
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

                $colIndex = self::columnLetterToIndex($ref);
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
            'customer_email' => ['alamat_email', 'email', 'customer_email', 'mail', 'e_mail'],
            'order_number' => ['id_no_pelanggan', 'no_pelanggan', 'id_pelanggan', 'id', 'no_order', 'order_number', 'customer_id'],
            'id_card_number' => ['no_ktp_nik', 'no_ktp', 'nik', 'ktp', 'id_card_number'],
            'customer_name' => ['nama_lengkap_pelanggan', 'nama_lengkap', 'nama_pelanggan', 'nama', 'customer_name', 'name'],
            'birth_place' => ['tempat_lahir', 'birth_place', 'kota_lahir'],
            'birth_date' => ['tanggal_lahir', 'tgl_lahir_pelanggan', 'tgl_lahir', 'birth_date', 'date_of_birth'],
            'customer_phone' => ['no_handphone_wa', 'no_handphone', 'no_hp', 'no_telepon', 'telepon', 'hp', 'wa', 'customer_phone', 'phone', 'whatsapp'],
            'package_name' => ['jenis_layanan_paket', 'jenis_layanan', 'nama_paket', 'paket', 'layanan', 'package_name', 'paket_berlangganan'],
            'speed' => ['kecepatan', 'speed', 'bandwidth'],
            'price' => ['harga_bulan_rp', 'harga_bulan', 'harga', 'tarif', 'price', 'biaya'],
            'address' => ['alamat_lengkap_pemasangan', 'alamat_lengkap', 'alamat_pemasangan', 'alamat', 'address', 'lokasi'],
            'status' => ['status_berlangganan', 'status', 'status_pelanggan'],
        ];

        // Pass 1: Exact matches
        foreach ($headerRow as $colIndex => $headerText) {
            $normalized = strtolower(trim((string)$headerText));
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
            $normalized = strtolower(trim((string)$headerText));
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
            return trim((string)$row[$fieldMap[$field]]);
        }
        return '';
    }

    /**
     * Parse format tanggal yang fleksibel (YYYY-MM-DD, DD/MM/YYYY, DD-MM-YYYY, atau angka serial Excel)
     */
    protected static function parseDate(?string $dateStr): ?string
    {
        if (empty($dateStr)) {
            return null;
        }

        $dateStr = trim($dateStr);

        // Jika angka serial Excel (misal: 31048 untuk tanggal)
        if (is_numeric($dateStr) && (float)$dateStr > 1000 && (float)$dateStr < 60000) {
            try {
                // Serial 1 = 1899-12-30
                $base = Carbon::create(1899, 12, 30);
                return $base->addDays((int)$dateStr)->toDateString();
            } catch (\Exception $e) {}
        }

        // Coba parsing standar YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $dateStr)) {
            try {
                return Carbon::parse($dateStr)->toDateString();
            } catch (\Exception $e) {}
        }

        // Coba parsing format DD/MM/YYYY atau DD-MM-YYYY
        if (preg_match('/^(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{4})$/', $dateStr, $m)) {
            try {
                return Carbon::create((int)$m[3], (int)$m[2], (int)$m[1])->toDateString();
            } catch (\Exception $e) {}
        }

        try {
            return Carbon::parse($dateStr)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }
}
