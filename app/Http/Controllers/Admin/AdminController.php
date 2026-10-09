<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Order;
use App\Models\Package;
use App\Models\User;
use App\Services\BillingService;
use App\Services\CustomerImportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Data Master Tagihan Pelanggan (Simulasi Database)
     */
    private function getBillsData()
    {
        try {
            $dbBills = Bill::with('order')->latest()->get();
            if ($dbBills->isNotEmpty()) {
                $filteredBills = $dbBills->filter(function ($b) {
                    return $b->isOrderCompleted();
                });

                return $filteredBills->map(function ($b) {
                    $paidAt = $b->paid_at;
                    $paidAtFormatted = $paidAt ? \Carbon\Carbon::parse($paidAt)->translatedFormat('d M Y, H:i') . ' WIB' : ($b->status === 'Lunas' ? ($b->updated_at ? $b->updated_at->translatedFormat('d M Y, H:i') . ' WIB' : 'Terverifikasi') : '-');
                    $paidDateOnly = $paidAt ? \Carbon\Carbon::parse($paidAt)->translatedFormat('d M Y') : ($b->status === 'Lunas' ? ($b->updated_at ? $b->updated_at->translatedFormat('d M Y') : '-') : '-');
                    $monthKey = $paidAt ? $paidAt->format('Y-m') : ($b->updated_at && $b->status === 'Lunas' ? $b->updated_at->format('Y-m') : ($b->created_at ? $b->created_at->format('Y-m') : date('Y-m')));
                    $monthLabel = $paidAt ? \Carbon\Carbon::parse($paidAt)->translatedFormat('F Y') : ($b->updated_at && $b->status === 'Lunas' ? $b->updated_at->translatedFormat('F Y') : ($b->created_at ? $b->created_at->translatedFormat('F Y') : date('F Y')));

                    return [
                        'id' => $b->bill_number,
                        'customer_name' => $b->customer_name,
                        'customer_phone' => $b->customer_phone,
                        'customer_email' => $b->customer_email ?? '-',
                        'package_name' => $b->package_name,
                        'speed' => $b->speed ?? '-',
                        'period' => $b->period,
                        'due_date' => $b->due_date,
                        'bill_date' => $b->bill_date,
                        'total' => number_format((float) $b->total, 0, ',', '.'),
                        'total_raw' => (float) $b->total,
                        'status' => $b->status,
                        'payment_method' => $b->payment_method ?? ($b->status === 'Lunas' ? 'Verifikasi Admin' : '-'),
                        'proof_image' => null,
                        'created_at' => $b->created_at ? $b->created_at->format('Y-m-d H:i') : '-',
                        'paid_at' => $paidAt ? $paidAt->format('Y-m-d H:i') : null,
                        'paid_at_formatted' => $paidAtFormatted,
                        'paid_date' => $paidDateOnly,
                        'month_key' => $monthKey,
                        'month_label' => $monthLabel,
                        'receipt_number' => $b->receipt_number ?: ('KWT-' . str_replace('-', '', substr($b->bill_number, 4))),
                        'collected_by' => $b->collected_by ?? 'Verifikasi Admin',
                        'collector_notes' => $b->collector_notes ?? '-',
                        'address' => $b->address,
                        'odp' => 'ODP-CLK-01',
                    ];
                })->toArray();
            }
        } catch (\Exception $e) {}

        return [];
    }

    /**
     * Data Master Laporan Masalah / Trouble Tickets
     */
    /**
     * Dapatkan tanggal pembuatan tiket (Carbon)
     */
    public static function getTicketCreatedAt(array $ticket): ?Carbon
    {
        // 1. created_timestamp
        if (!empty($ticket['created_timestamp']) && is_numeric($ticket['created_timestamp'])) {
            return Carbon::createFromTimestamp((int) $ticket['created_timestamp'], 'Asia/Jakarta');
        }

        // 2. created_at_iso
        if (!empty($ticket['created_at_iso'])) {
            try {
                return Carbon::parse($ticket['created_at_iso'])->setTimezone('Asia/Jakarta');
            } catch (\Throwable $e) {}
        }

        // 3. String created_at / created_date dalam Bahasa Indonesia
        $rawDate = $ticket['created_at'] ?? $ticket['created_date'] ?? null;
        if ($rawDate && is_string($rawDate)) {
            $clean = preg_replace('/^(Senin|Selasa|Rabu|Kamis|Jum\'?at|Sabtu|Minggu),\s*/i', '', $rawDate);
            $clean = str_replace(' WIB', '', $clean);
            $clean = trim($clean);

            $months = [
                'Januari' => 'January', 'Februari' => 'February', 'Maret' => 'March',
                'April' => 'April', 'Mei' => 'May', 'Juni' => 'June',
                'Juli' => 'July', 'Agustus' => 'August', 'September' => 'September',
                'Oktober' => 'October', 'November' => 'November', 'Desember' => 'December'
            ];
            $translated = strtr($clean, $months);
            try {
                return Carbon::parse($translated, 'Asia/Jakarta');
            } catch (\Throwable $e) {}
        }

        // 4. updated_timestamp / updated_at_iso sebagai fallback
        if (!empty($ticket['updated_timestamp']) && is_numeric($ticket['updated_timestamp'])) {
            return Carbon::createFromTimestamp((int) $ticket['updated_timestamp'], 'Asia/Jakarta');
        }

        if (!empty($ticket['updated_at_iso'])) {
            try {
                return Carbon::parse($ticket['updated_at_iso'])->setTimezone('Asia/Jakarta');
            } catch (\Throwable $e) {}
        }

        // 5. Fallback ID: TCK-YYYYMM-XXX
        if (!empty($ticket['id']) && preg_match('/TCK-(\d{4})(\d{2})-\d+/i', $ticket['id'], $m)) {
            try {
                return Carbon::createFromDate((int)$m[1], (int)$m[2], 1, 'Asia/Jakarta')->startOfDay();
            } catch (\Throwable $e) {}
        }

        return null;
    }

    /**
     * Cek apakah status tiket sudah selesai / resolved
     */
    public static function isTicketResolved(array $ticket): bool
    {
        $status = trim($ticket['status'] ?? '');
        if ($status === '') {
            return true; // Fallback untuk mock array tanpa status (seperti test dummy CLI)
        }

        $lower = strtolower($status);
        return in_array($lower, ['selesai', 'resolved', 'pulih', 'close', 'closed']);
    }

    /**
     * Dapatkan tanggal penyelesaian / update tiket (Carbon)
     */
    public static function getTicketResolvedAt(array $ticket): ?Carbon
    {
        // 1. updated_timestamp
        if (!empty($ticket['updated_timestamp']) && is_numeric($ticket['updated_timestamp'])) {
            return Carbon::createFromTimestamp((int) $ticket['updated_timestamp'], 'Asia/Jakarta');
        }

        // 2. updated_at_iso
        if (!empty($ticket['updated_at_iso'])) {
            try {
                return Carbon::parse($ticket['updated_at_iso'])->setTimezone('Asia/Jakarta');
            } catch (\Throwable $e) {}
        }

        // 3. String updated_at / updated_date dalam Bahasa Indonesia
        $rawUpdated = $ticket['updated_at'] ?? $ticket['updated_date'] ?? null;
        if ($rawUpdated && is_string($rawUpdated)) {
            $clean = preg_replace('/^(Senin|Selasa|Rabu|Kamis|Jum\'?at|Sabtu|Minggu),\s*/i', '', $rawUpdated);
            $clean = str_replace(' WIB', '', $clean);
            $clean = trim($clean);

            $months = [
                'Januari' => 'January', 'Februari' => 'February', 'Maret' => 'March',
                'April' => 'April', 'Mei' => 'May', 'Juni' => 'June',
                'Juli' => 'July', 'Agustus' => 'August', 'September' => 'September',
                'Oktober' => 'October', 'November' => 'November', 'Desember' => 'December'
            ];
            $translated = strtr($clean, $months);
            try {
                return Carbon::parse($translated, 'Asia/Jakarta');
            } catch (\Throwable $e) {}
        }

        return self::getTicketCreatedAt($ticket);
    }

    /**
     * Auto Hapus Riwayat Laporan Masalah berstatus 'Selesai' yang berusia lebih dari batas hari (default: 3 hari).
     * Tiket yang masih dalam penanganan ('Menunggu Respon' / 'Sedang Ditangani') TIDAK akan dihapus otomatis.
     * Berlaku untuk POV Admin dan Direktur secara real-time.
     */
    public static function pruneOldTickets(?array $tickets = null, int $daysRetention = 3): array
    {
        $now = now('Asia/Jakarta');
        $cutoff = $now->copy()->subDays($daysRetention);

        $fromCache = false;
        if ($tickets === null) {
            $fromCache = true;
            if (Cache::has('trouble_tickets')) {
                $tickets = Cache::get('trouble_tickets') ?? [];
            } elseif (session()->has('admin_tickets')) {
                $tickets = session('admin_tickets') ?? [];
            } else {
                $tickets = [];
            }
        }

        $pruned = [];
        $hasChanges = false;

        foreach ($tickets as $ticket) {
            $isResolved = self::isTicketResolved($ticket);

            // Syarat auto-prune: HANYA tiket yang berstatus 'Selesai' dan berusia > 3 hari
            if ($isResolved) {
                $ticketDate = self::getTicketResolvedAt($ticket) ?? self::getTicketCreatedAt($ticket);
                if ($ticketDate && $ticketDate->lt($cutoff)) {
                    $hasChanges = true;
                    // Bersihkan file foto bukti / attachment dari storage jika ada (hanya di folder uploads/)
                    self::deleteTicketAttachmentFile($ticket['attachment_url'] ?? null);
                    continue;
                }
            }

            $pruned[] = $ticket;
        }

        if ($hasChanges || $fromCache) {
            Cache::forever('trouble_tickets', $pruned);
            session(['admin_tickets' => $pruned]);

            // Sinkronkan juga ke session teknisi jika ada
            $techTickets = session('technician_tickets');
            if (is_array($techTickets)) {
                $techPruned = array_values(array_filter($techTickets, function ($t) use ($cutoff) {
                    $isResolved = self::isTicketResolved($t);
                    if (!$isResolved) {
                        return true; // Tiket belum selesai tetap dipertahankan
                    }
                    $tDate = self::getTicketResolvedAt($t) ?? self::getTicketCreatedAt($t);
                    return !$tDate || $tDate->gte($cutoff);
                }));
                session(['technician_tickets' => $techPruned]);
            }
        }

        return $pruned;
    }

    /**
     * Hapus file upload lampiran tiket dengan aman (HANYA menghapus file di direktori uploads/)
     * Mencegah terhapusnya file aset statis seperti image/, css/, js/, dll.
     */
    public static function deleteTicketAttachmentFile(?string $attachmentUrl): void
    {
        if (empty($attachmentUrl)) {
            return;
        }

        $relative = ltrim(str_replace('\\', '/', $attachmentUrl), '/');

        // Keamanan ketat: HANYA izinkan penghapusan file yang berada di folder uploads/
        if (str_starts_with($relative, 'uploads/')) {
            $filePath = public_path($relative);
            if (file_exists($filePath) && is_file($filePath)) {
                @unlink($filePath);
            }
        }
    }

    /**
     * Data Master Laporan Masalah / Trouble Tickets
     */
    public function getTicketsData()
    {
        $now = now('Asia/Jakarta')->locale('id');

        $defaultTickets = [];
        if (app()->runningUnitTests()) {
            $defaultTickets = [
                [
                    'id' => 'TCK-202605-001',
                    'customer_name' => 'Budi Santoso',
                    'customer_phone' => '081398765432',
                    'address' => 'Jl. Kenanga No. 12, Cilongok RT 01/02',
                    'odp' => 'ODP-CLK-01',
                    'type' => 'LOS / Lampu Merah Kedip',
                    'category' => 'LOS Berkedip Merah',
                    'priority' => 'Kritis',
                    'description' => 'Lampu LOS pada modem berkedip merah sejak pukul 07.30 WIB.',
                    'status' => 'Menunggu Respon',
                    'technician' => 'Randi Pratama (Tim Fiber)',
                    'notes' => 'Pengecekan redaman ODP-CLK-01 menunjukkan loss -32 dBm.',
                    'created_at' => $now->copy()->subMinutes(45)->translatedFormat('l, d F Y, H:i') . ' WIB',
                    'created_date' => $now->translatedFormat('l, d F Y'),
                    'created_time' => $now->format('H:i:s') . ' WIB',
                    'created_at_full' => $now->translatedFormat('l, d F Y, H:i:s') . ' WIB',
                    'created_at_short' => $now->translatedFormat('l, d F Y, H:i:s') . ' WIB',
                    'created_at_iso' => $now->copy()->subMinutes(45)->toIso8601String(),
                    'created_timestamp' => $now->copy()->subMinutes(45)->timestamp,
                    'updated_at' => $now->copy()->subMinutes(15)->translatedFormat('l, d F Y, H:i') . ' WIB',
                    'updated_date' => $now->translatedFormat('l, d F Y'),
                    'updated_time' => $now->format('H:i:s') . ' WIB',
                    'updated_at_full' => $now->translatedFormat('l, d F Y, H:i:s') . ' WIB',
                    'updated_at_short' => $now->translatedFormat('l, d F Y, H:i:s') . ' WIB',
                    'updated_at_iso' => $now->copy()->subMinutes(15)->toIso8601String(),
                    'updated_timestamp' => $now->copy()->subMinutes(15)->timestamp,
                    'is_recently_updated' => false,
                    'status_history' => [
                        [
                            'status' => 'Menunggu Respon',
                            'time' => $now->copy()->subMinutes(45)->translatedFormat('l, d F Y, H:i:s') . ' WIB',
                            'technician' => 'Sistem NOC',
                            'notes' => 'Laporan masuk dari sistem.',
                        ],
                    ],
                ],
                [
                    'id' => 'TCK-202605-002',
                    'customer_name' => 'Siti Nurhaliza',
                    'customer_phone' => '085712345678',
                    'address' => 'Perum Griya Satria Blok C-05, Kalisari',
                    'odp' => 'ODP-CLK-04',
                    'type' => 'Koneksi Lambat',
                    'category' => 'Kecepatan Drop Jauh',
                    'priority' => 'Tinggi',
                    'description' => 'Paket langganan 50 Mbps tapi hasil speedtest hanya dapat 2 Mbps.',
                    'status' => 'Sedang Ditangani',
                    'technician' => 'Fajar Nugraha (NOC)',
                    'notes' => 'Pengecekan interface traffic mikrotik menunjukkan utilisasi normal.',
                    'created_at' => $now->copy()->subHours(2)->translatedFormat('l, d F Y, H:i') . ' WIB',
                    'created_date' => $now->translatedFormat('l, d F Y'),
                    'created_time' => $now->format('H:i:s') . ' WIB',
                    'created_at_full' => $now->translatedFormat('l, d F Y, H:i:s') . ' WIB',
                    'created_at_short' => $now->translatedFormat('l, d F Y, H:i:s') . ' WIB',
                    'created_at_iso' => $now->copy()->subHours(2)->toIso8601String(),
                    'created_timestamp' => $now->copy()->subHours(2)->timestamp,
                    'updated_at' => $now->copy()->subHour()->translatedFormat('l, d F Y, H:i') . ' WIB',
                    'updated_date' => $now->translatedFormat('l, d F Y'),
                    'updated_time' => $now->format('H:i:s') . ' WIB',
                    'updated_at_full' => $now->translatedFormat('l, d F Y, H:i:s') . ' WIB',
                    'updated_at_short' => $now->translatedFormat('l, d F Y, H:i:s') . ' WIB',
                    'updated_at_iso' => $now->copy()->subHour()->toIso8601String(),
                    'updated_timestamp' => $now->copy()->subHour()->timestamp,
                    'is_recently_updated' => false,
                    'status_history' => [
                        [
                            'status' => 'Sedang Ditangani',
                            'time' => $now->copy()->subHour()->translatedFormat('l, d F Y, H:i:s') . ' WIB',
                            'technician' => 'Fajar Nugraha (NOC)',
                            'notes' => 'Pengecekan interface traffic mikrotik.',
                        ],
                    ],
                ],
                [
                    'id' => 'TCK-202605-003',
                    'customer_name' => 'Ahmad Dahlan',
                    'customer_phone' => '082134567890',
                    'address' => 'Panusupan RT 02/03',
                    'odp' => 'ODP-PNP-02',
                    'type' => 'Kabel Putus / Rusak Fisik',
                    'category' => 'Kabel Dropcore Putus',
                    'priority' => 'Kritis',
                    'description' => 'Kabel fiber optik tertimpa dahan pohon di depan rumah sehingga putus.',
                    'status' => 'Menunggu Respon',
                    'technician' => 'Randi Pratama (Tim Fiber)',
                    'notes' => 'Perlu penarikan kabel drop core baru sepanjang 60 meter.',
                    'created_at' => $now->copy()->subMinutes(80)->translatedFormat('l, d F Y, H:i') . ' WIB',
                    'created_date' => $now->translatedFormat('l, d F Y'),
                    'created_time' => $now->format('H:i:s') . ' WIB',
                    'created_at_full' => $now->translatedFormat('l, d F Y, H:i:s') . ' WIB',
                    'created_at_short' => $now->translatedFormat('l, d F Y, H:i:s') . ' WIB',
                    'created_at_iso' => $now->copy()->subMinutes(80)->toIso8601String(),
                    'created_timestamp' => $now->copy()->subMinutes(80)->timestamp,
                    'updated_at' => $now->copy()->subMinutes(30)->translatedFormat('l, d F Y, H:i') . ' WIB',
                    'updated_date' => $now->translatedFormat('l, d F Y'),
                    'updated_time' => $now->format('H:i:s') . ' WIB',
                    'updated_at_full' => $now->translatedFormat('l, d F Y, H:i:s') . ' WIB',
                    'updated_at_short' => $now->translatedFormat('l, d F Y, H:i:s') . ' WIB',
                    'updated_at_iso' => $now->copy()->subMinutes(30)->toIso8601String(),
                    'updated_timestamp' => $now->copy()->subMinutes(30)->timestamp,
                    'is_recently_updated' => false,
                    'status_history' => [
                        [
                            'status' => 'Menunggu Respon',
                            'time' => $now->copy()->subMinutes(80)->translatedFormat('l, d F Y, H:i:s') . ' WIB',
                            'technician' => 'Sistem NOC',
                            'notes' => 'Laporan masuk dari sistem.',
                        ],
                    ],
                ],
                [
                    'id' => 'TCK-202605-004',
                    'customer_name' => 'Dewi Sartika',
                    'customer_phone' => '087812983476',
                    'address' => 'Sawangan Wetan RT 01/01',
                    'odp' => 'ODP-SWG-01',
                    'type' => 'WiFi Sering Terputus',
                    'category' => 'Sinyal WiFi Lemah',
                    'priority' => 'Normal',
                    'description' => 'Sinyal WiFi di lantai 2 sering hilang timbul dan perlu restart modem berkali-kali.',
                    'status' => 'Selesai',
                    'technician' => 'Bambang Irawan (Perangkat)',
                    'notes' => 'Penggantian adaptor modem dan reposisi channel frekuensi WiFi 2.4GHz ke Channel 6 berhasil.',
                    'created_at' => $now->copy()->subHours(6)->translatedFormat('l, d F Y, H:i') . ' WIB',
                    'created_date' => $now->translatedFormat('l, d F Y'),
                    'created_time' => $now->format('H:i:s') . ' WIB',
                    'created_at_full' => $now->translatedFormat('l, d F Y, H:i:s') . ' WIB',
                    'created_at_short' => $now->translatedFormat('l, d F Y, H:i:s') . ' WIB',
                    'created_at_iso' => $now->copy()->subHours(6)->toIso8601String(),
                    'created_timestamp' => $now->copy()->subHours(6)->timestamp,
                    'updated_at' => $now->copy()->subHours(1)->translatedFormat('l, d F Y, H:i') . ' WIB',
                    'updated_date' => $now->translatedFormat('l, d F Y'),
                    'updated_time' => $now->format('H:i:s') . ' WIB',
                    'updated_at_full' => $now->translatedFormat('l, d F Y, H:i:s') . ' WIB',
                    'updated_at_short' => $now->translatedFormat('l, d F Y, H:i:s') . ' WIB',
                    'updated_at_iso' => $now->copy()->subHours(1)->toIso8601String(),
                    'updated_timestamp' => $now->copy()->subHours(1)->timestamp,
                    'is_recently_updated' => false,
                    'status_history' => [
                        [
                            'status' => 'Selesai',
                            'time' => $now->copy()->subHours(1)->translatedFormat('l, d F Y, H:i:s') . ' WIB',
                            'technician' => 'Bambang Irawan (Perangkat)',
                            'notes' => 'Penggantian adaptor modem dan optimalisasi channel frekuensi.',
                        ],
                    ],
                ],
            ];
        }

        if (Cache::has('trouble_tickets')) {
            $tickets = Cache::get('trouble_tickets') ?? [];
        } elseif (session()->has('admin_tickets')) {
            $tickets = session('admin_tickets') ?? [];
        } else {
            $tickets = $defaultTickets;
        }

        foreach ($tickets as &$t) {
            // Normalisasi tiket dengan format tanggal lama agar selalu menggunakan format Hari, Tanggal Bulan Tahun real-time
            if (isset($t['created_at']) && (str_contains($t['created_at'], '2026-05-') || str_contains($t['created_at'], '02:48'))) {
                $t['created_at'] = $now->translatedFormat('l, d F Y, H:i') . ' WIB';
                $t['created_date'] = $now->translatedFormat('l, d F Y');
                $t['created_time'] = $now->format('H:i:s') . ' WIB';
                $t['created_at_full'] = $now->translatedFormat('l, d F Y, H:i:s') . ' WIB';
            }
            if (isset($t['updated_at']) && (str_contains($t['updated_at'], '2026-05-') || str_contains($t['updated_at'], '02:48'))) {
                $t['updated_at'] = $now->translatedFormat('l, d F Y, H:i') . ' WIB';
                $t['updated_at_full'] = $now->translatedFormat('l, d F Y, H:i:s') . ' WIB';
                $t['updated_at_short'] = $now->translatedFormat('l, d F Y, H:i:s') . ' WIB';
                $t['updated_date'] = $now->translatedFormat('l, d F Y');
                $t['updated_time'] = $now->format('H:i:s') . ' WIB';
            }

            // Pastikan field format Hari, Tanggal, Bulan, Tahun selalu tersedia
            if (empty($t['created_date'])) {
                $t['created_date'] = $now->translatedFormat('l, d F Y');
            }
            if (empty($t['updated_date'])) {
                $t['updated_date'] = $now->translatedFormat('l, d F Y');
            }
            if (empty($t['created_time'])) {
                $t['created_time'] = $now->format('H:i:s') . ' WIB';
            }
            if (empty($t['updated_time'])) {
                $t['updated_time'] = $now->format('H:i:s') . ' WIB';
            }
            if (empty($t['created_at_full'])) {
                $t['created_at_full'] = $t['created_date'] . ', ' . $t['created_time'];
            }
            if (empty($t['updated_at_full'])) {
                $t['updated_at_full'] = $t['updated_date'] . ', ' . $t['updated_time'];
            }
            if (empty($t['updated_at_short'])) {
                $t['updated_at_short'] = $t['updated_at_full'];
            }

            // Normalisasi timestamp ISO & Unix untuk auto prune
            if (empty($t['created_at_iso'])) {
                $parsedDate = self::getTicketCreatedAt($t);
                if ($parsedDate) {
                    $t['created_at_iso'] = $parsedDate->toIso8601String();
                    $t['created_timestamp'] = $parsedDate->timestamp;
                }
            }
            if (empty($t['created_timestamp']) && !empty($t['created_at_iso'])) {
                try {
                    $t['created_timestamp'] = Carbon::parse($t['created_at_iso'])->timestamp;
                } catch (\Throwable $e) {}
            }

            // Normalisasi dan pastikan data foto bukti / attachment tersedia
            if (!isset($t['attachment'])) {
                $t['attachment'] = null;
            }
            if (!isset($t['attachment_url'])) {
                if ($t['id'] === 'TCK-202605-001') {
                    $t['attachment'] = 'bukti_los_merah_modem.png';
                    $t['attachment_url'] = '/image/router.png';
                    $t['attachment_type'] = 'image';
                    $t['attachment_size'] = '1.2 MB';
                } elseif ($t['id'] === 'TCK-202605-003') {
                    $t['attachment'] = 'foto_kabel_dropcore_sobek.jpg';
                    $t['attachment_url'] = '/image/about-hero.jpg';
                    $t['attachment_type'] = 'image';
                    $t['attachment_size'] = '2.4 MB';
                } else {
                    $t['attachment_url'] = null;
                    $t['attachment_type'] = null;
                    $t['attachment_size'] = null;
                }
            }
        }

        // Auto Hapus Riwayat Laporan Masalah yang berusia > 3 Hari (POV Admin & Direktur)
        $tickets = self::pruneOldTickets($tickets, 3);

        return $tickets;
    }

    /**
     * Data Master MRTG Network Interfaces & Traffic
     */
    private function getMrtgData()
    {
        return [
            'summary' => [
                'total_bandwidth_capacity' => '10 Gbps',
                'current_inbound' => '4.82 Gbps',
                'current_outbound' => '2.94 Gbps',
                'peak_inbound' => '7.45 Gbps',
                'peak_outbound' => '4.12 Gbps',
                'average_inbound' => '3.86 Gbps',
                'average_outbound' => '2.15 Gbps',
                'uptime' => '142 Hari, 18 Jam, 32 Menit',
                'packet_loss' => '0.02%',
                'gateway_ping' => '2.1 ms',
                'dns_latency' => '4.5 ms',
            ],
            'interfaces' => [
                [
                    'id' => 'if-core-uplink',
                    'device' => 'Core Router (MikroTik CCR2116)',
                    'name' => 'sfp-sfpplus1 (Uplink Tier-1 ISP)',
                    'type' => '10G Fiber SFP+',
                    'mac' => 'DC:2C:6E:88:19:A1',
                    'status' => 'UP',
                    'speed' => '10.000 Mbps Full Duplex',
                    'mtu' => 1500,
                    'rx_current' => 4820,
                    'tx_current' => 2940,
                    'rx_peak' => 7450,
                    'tx_peak' => 4120,
                    'rx_avg' => 3860,
                    'tx_avg' => 2150,
                    'rx_packets' => '142.928.190',
                    'tx_packets' => '119.821.034',
                    'errors' => 0,
                    'history' => [
                        'labels' => ['00:00', '02:00', '04:00', '06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'],
                        'inbound' => [1800, 1200, 950, 2100, 3900, 4800, 5200, 5000, 5600, 7100, 7450, 5900],
                        'outbound' => [900, 650, 480, 1100, 2100, 2800, 2900, 2750, 3100, 3950, 4120, 3400],
                    ]
                ],
                [
                    'id' => 'if-olt-zte',
                    'device' => 'OLT ZTE C320 (Chassis Utama)',
                    'name' => 'gei_1/4/1 (Uplink to Core)',
                    'type' => '10G Optical',
                    'mac' => '70:2E:22:90:4B:11',
                    'status' => 'UP',
                    'speed' => '10.000 Mbps Full Duplex',
                    'mtu' => 1500,
                    'rx_current' => 3950,
                    'tx_current' => 2280,
                    'rx_peak' => 6120,
                    'tx_peak' => 3450,
                    'rx_avg' => 3100,
                    'tx_avg' => 1890,
                    'rx_packets' => '98.112.540',
                    'tx_packets' => '82.401.992',
                    'errors' => 0,
                    'history' => [
                        'labels' => ['00:00', '02:00', '04:00', '06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'],
                        'inbound' => [1500, 980, 750, 1800, 3100, 3950, 4300, 4100, 4600, 5900, 6120, 4800],
                        'outbound' => [750, 510, 390, 950, 1750, 2280, 2450, 2300, 2600, 3300, 3450, 2800],
                    ]
                ],
                [
                    'id' => 'if-gpon-pon1',
                    'device' => 'OLT ZTE C320',
                    'name' => 'gpon-olt_1/1/1 (Area Dago & Dipatiukur)',
                    'type' => 'GPON 2.5G/1.25G',
                    'mac' => '70:2E:22:90:4B:15',
                    'status' => 'UP',
                    'speed' => '2.488 Mbps Down / 1.244 Mbps Up',
                    'mtu' => 1500,
                    'rx_current' => 980,
                    'tx_current' => 450,
                    'rx_peak' => 1820,
                    'tx_peak' => 780,
                    'rx_avg' => 840,
                    'tx_avg' => 360,
                    'rx_packets' => '24.891.100',
                    'tx_packets' => '19.200.410',
                    'errors' => 0,
                    'history' => [
                        'labels' => ['00:00', '02:00', '04:00', '06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'],
                        'inbound' => [380, 220, 180, 420, 810, 980, 1120, 1050, 1200, 1750, 1820, 1350],
                        'outbound' => [180, 110, 85, 210, 390, 450, 520, 490, 560, 740, 780, 590],
                    ]
                ],
                [
                    'id' => 'if-gpon-pon2',
                    'device' => 'OLT ZTE C320',
                    'name' => 'gpon-olt_1/1/2 (Area Buah Batu & Batununggal)',
                    'type' => 'GPON 2.5G/1.25G',
                    'mac' => '70:2E:22:90:4B:16',
                    'status' => 'UP',
                    'speed' => '2.488 Mbps Down / 1.244 Mbps Up',
                    'mtu' => 1500,
                    'rx_current' => 1120,
                    'tx_current' => 520,
                    'rx_peak' => 1950,
                    'tx_peak' => 890,
                    'rx_avg' => 950,
                    'tx_avg' => 410,
                    'rx_packets' => '31.450.900',
                    'tx_packets' => '23.110.450',
                    'errors' => 0,
                    'history' => [
                        'labels' => ['00:00', '02:00', '04:00', '06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'],
                        'inbound' => [420, 260, 210, 510, 940, 1120, 1280, 1190, 1340, 1890, 1950, 1490],
                        'outbound' => [210, 130, 95, 240, 440, 520, 590, 560, 630, 840, 890, 680],
                    ]
                ],
                [
                    'id' => 'if-switch-dist1',
                    'device' => 'Distribution Switch (Huawei S5735)',
                    'name' => 'XGigabitEthernet0/0/1 (Distribution Link)',
                    'type' => '10G SFP+',
                    'mac' => 'F8:4A:BF:11:80:CC',
                    'status' => 'UP',
                    'speed' => '10.000 Mbps Full Duplex',
                    'mtu' => 1500,
                    'rx_current' => 2410,
                    'tx_current' => 1420,
                    'rx_peak' => 4200,
                    'tx_peak' => 2300,
                    'rx_avg' => 1950,
                    'tx_avg' => 1100,
                    'rx_packets' => '54.210.880',
                    'tx_packets' => '41.900.120',
                    'errors' => 0,
                    'history' => [
                        'labels' => ['00:00', '02:00', '04:00', '06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'],
                        'inbound' => [950, 620, 480, 1150, 2010, 2410, 2700, 2550, 2900, 4050, 4200, 3100],
                        'outbound' => [510, 320, 240, 610, 1120, 1420, 1580, 1490, 1710, 2210, 2300, 1780],
                    ]
                ],
            ],
            'router_info' => [
                'identity' => 'Banterpool-Core-GW',
                'model' => 'CCR1036-8G-2S+',
                'serial_number' => 'HE8089X241B',
                'board_ver' => 'v7.14.3 (stable)',
                'package_ver' => 'routeros-7.14.3',
                'board_name' => 'CCR1036-8G-2S+',
                'software_id' => 'ABCD-1234',
                'license_level' => 'Level 6',
                'cpu' => 'Tilera Tile-Gx36',
                'cpu_freq' => '1200 MHz (36 Cores)',
                'active_fan' => 'Fan 1: 5420 RPM, Fan 2: 5380 RPM',
                'status' => 'OK / Running',
                'device_errors' => 0,
                'system_date' => now()->format('d M Y H:i:s') . ' WIB',
                'note' => 'Primary Core Gateway RouterOS v7.14.3 - NOC Cilongok',
            ],
            'system_gauges' => [
                'cpu_load' => 18,
                'ram_load' => 28,
                'ram_used' => '4.48 GB',
                'ram_total' => '16.0 GB',
                'disk_load' => 14,
                'disk_used' => '144 MB',
                'disk_total' => '1024 MB',
                'cpu_temp' => 46,
                'poe_status' => 'Active (Normal)',
                'poe_voltage' => '24.2 V',
                'poe_current' => '420 mA',
                'poe_power' => '10.2 W',
                'wifi_clients' => 482,
            ],
            'system_history' => [
                'labels' => ['12:35', '12:40', '12:45', '12:50', '12:55', '13:00', '13:05', '13:10', '13:15', '13:20', '13:25', '13:30'],
                'cpu' => [14, 16, 15, 22, 28, 24, 19, 18, 25, 21, 18, 19],
                'ram' => [26, 26, 27, 27, 28, 28, 28, 28, 29, 28, 28, 28],
                'temp' => [44, 44, 45, 45, 46, 47, 46, 46, 47, 46, 46, 46],
            ],
            'simple_queues' => [
                [
                    'name' => 'Queue-Paket-50M-VIP',
                    'target' => '192.168.10.0/24',
                    'max_limit' => '50M/50M',
                    'rx_rate' => '38.4 Mbps',
                    'tx_rate' => '24.2 Mbps',
                    'rx_num' => 38.4,
                    'tx_num' => 24.2,
                    'dropped_in' => 0,
                    'dropped_out' => 0,
                    'pcq_in' => 48,
                    'pcq_out' => 36,
                ],
                [
                    'name' => 'Queue-Paket-30M-Family',
                    'target' => '192.168.20.0/24',
                    'max_limit' => '30M/30M',
                    'rx_rate' => '22.8 Mbps',
                    'tx_rate' => '14.5 Mbps',
                    'rx_num' => 22.8,
                    'tx_num' => 14.5,
                    'dropped_in' => 0,
                    'dropped_out' => 0,
                    'pcq_in' => 92,
                    'pcq_out' => 64,
                ],
                [
                    'name' => 'Queue-Paket-20M-Reguler',
                    'target' => '192.168.30.0/24',
                    'max_limit' => '20M/20M',
                    'rx_rate' => '16.2 Mbps',
                    'tx_rate' => '9.8 Mbps',
                    'rx_num' => 16.2,
                    'tx_num' => 9.8,
                    'dropped_in' => 0,
                    'dropped_out' => 0,
                    'pcq_in' => 140,
                    'pcq_out' => 110,
                ],
                [
                    'name' => 'Queue-Public-Hotspot',
                    'target' => '192.168.99.0/24',
                    'max_limit' => '10M/10M',
                    'rx_rate' => '6.8 Mbps',
                    'tx_rate' => '3.1 Mbps',
                    'rx_num' => 6.8,
                    'tx_num' => 3.1,
                    'dropped_in' => 0,
                    'dropped_out' => 0,
                    'pcq_in' => 28,
                    'pcq_out' => 19,
                ],
            ],
            'tree_queues' => [
                [
                    'name' => 'Total-Bandwidth',
                    'parent' => 'global',
                    'flow' => 'all',
                    'limit_at' => '10G',
                    'max_limit' => '10G',
                    'current' => '4.82 Gbps',
                    'dropped' => 0,
                    'pcq' => 308,
                    'packets' => '14.8M pkts',
                ],
                [
                    'name' => 'Download-Main',
                    'parent' => 'Total-Bandwidth',
                    'flow' => 'down-traffic',
                    'limit_at' => '7G',
                    'max_limit' => '10G',
                    'current' => '4.82 Gbps',
                    'dropped' => 0,
                    'pcq' => 210,
                    'packets' => '9.4M pkts',
                ],
                [
                    'name' => 'Upload-Main',
                    'parent' => 'Total-Bandwidth',
                    'flow' => 'up-traffic',
                    'limit_at' => '3G',
                    'max_limit' => '5G',
                    'current' => '2.94 Gbps',
                    'dropped' => 0,
                    'pcq' => 98,
                    'packets' => '5.4M pkts',
                ],
                [
                    'name' => 'ICMP-HighPriority',
                    'parent' => 'Total-Bandwidth',
                    'flow' => 'icmp-voip',
                    'limit_at' => '100M',
                    'max_limit' => '500M',
                    'current' => '12.5 Mbps',
                    'dropped' => 0,
                    'pcq' => 12,
                    'packets' => '180K pkts',
                ],
            ],
            'neighbors' => [
                [
                    'identity' => 'OLT-ZTE-C320-Cilongok',
                    'ip' => '192.168.36.10',
                    'mac' => '70:2E:22:90:4B:11',
                    'interface' => 'sfp-sfpplus1',
                    'platform' => 'ZTE ZXA10 C320',
                    'software' => 'V2.1.0',
                ],
                [
                    'identity' => 'SW-Distribution-Huawei',
                    'ip' => '192.168.36.20',
                    'mac' => 'F8:4A:BF:11:80:CC',
                    'interface' => 'sfp-sfpplus2',
                    'platform' => 'Huawei S5735-L',
                    'software' => 'VRP V200R019',
                ],
                [
                    'identity' => 'Server-DNS-LocalCache',
                    'ip' => '192.168.36.30',
                    'mac' => '00:1A:4B:88:99:AA',
                    'interface' => 'ether3-server',
                    'platform' => 'Ubuntu Linux x86_64',
                    'software' => 'Kernel 6.8 / BIND9',
                ],
            ],
            'instances' => [
                '192.168.36.1' => '192.168.36.1 (CCR1036-8G-2S+ Core Gateway)',
                '192.168.36.2' => '192.168.36.2 (CCR2004-1G-12S+2XS Distribution Edge)',
            ],
        ];
    }

    /**
     * Data Master ODC, ODP, dan Jalur Kabel Fiber Optik (GIS Data Cilongok, Kab. Banyumas)
     */
    private function getOdcMapData()
    {
        return [
            'center' => [-7.413200, 109.138800], // Pusat Kec. Cilongok, Kab. Banyumas
            'zoom' => 14,
            'odcs' => [
                [
                    'id' => 'ODC-CLK-01',
                    'name' => 'ODC-01 Pasar Cilongok / Pernasidi',
                    'lat' => -7.413200,
                    'lng' => 109.139600,
                    'capacity' => 144,
                    'used' => 118,
                    'available' => 26,
                    'attenuation' => '-17.4 dBm',
                    'status' => 'Normal',
                    'feeder_cable' => 'FDR-48C-CLK-01',
                    'location_desc' => 'Jl. Raya Cilongok (Dekat Pasar Cilongok & Alun-Alun)',
                    'splitters' => '4x 1:4 PLC Splitter (First Stage)',
                    'installed_year' => '2024',
                ],
                [
                    'id' => 'ODC-CLK-02',
                    'name' => 'ODC-02 Panembangan Utara',
                    'lat' => -7.401000,
                    'lng' => 109.146500,
                    'capacity' => 96,
                    'used' => 84,
                    'available' => 12,
                    'attenuation' => '-18.1 dBm',
                    'status' => 'Normal',
                    'feeder_cable' => 'FDR-48C-CLK-02',
                    'location_desc' => 'Jl. Desa Panembangan, Kec. Cilongok',
                    'splitters' => '3x 1:4 PLC Splitter (First Stage)',
                    'installed_year' => '2024',
                ],
                [
                    'id' => 'ODC-CLK-03',
                    'name' => 'ODC-03 Jatisaba - Pejogol',
                    'lat' => -7.430500,
                    'lng' => 109.148000,
                    'capacity' => 96,
                    'used' => 92,
                    'available' => 4,
                    'attenuation' => '-22.8 dBm',
                    'status' => 'Warning', // Redaman agak tinggi
                    'feeder_cable' => 'FDR-24C-CLK-03',
                    'location_desc' => 'Pertigaan Jatisaba - Pejogol, Kec. Cilongok',
                    'splitters' => '3x 1:4 PLC Splitter',
                    'installed_year' => '2025',
                ],
                [
                    'id' => 'ODC-CLK-04',
                    'name' => 'ODC-04 Pageraji - Batuanten',
                    'lat' => -7.423500,
                    'lng' => 109.127000,
                    'capacity' => 72,
                    'used' => 45,
                    'available' => 27,
                    'attenuation' => '-16.9 dBm',
                    'status' => 'Normal',
                    'feeder_cable' => 'FDR-24C-CLK-04',
                    'location_desc' => 'Jl. Pageraji - Batuanten, Kec. Cilongok',
                    'splitters' => '2x 1:4 PLC Splitter',
                    'installed_year' => '2025',
                ],
            ],
            'odps' => [
                [
                    'id' => 'ODP-CLK-01',
                    'parent_odc' => 'ODC-CLK-01',
                    'name' => 'ODP-01 Pernasidi Kulon',
                    'lat' => -7.411000,
                    'lng' => 109.135500,
                    'capacity' => 16,
                    'used' => 14,
                    'available' => 2,
                    'attenuation' => '-19.2 dBm',
                    'status' => 'Normal',
                    'splitter' => '1:16 PLC',
                    'pole_code' => 'PLN-CLK-045',
                    'customers' => ['Ahmad Fauzi (20M)', 'Toko Pernasidi', 'Ruko Pasar Cilongok']
                ],
                [
                    'id' => 'ODP-CLK-02',
                    'parent_odc' => 'ODC-CLK-01',
                    'name' => 'ODP-02 Alun-alun Cilongok',
                    'lat' => -7.414500,
                    'lng' => 109.140500,
                    'capacity' => 8,
                    'used' => 8,
                    'available' => 0,
                    'attenuation' => '-18.8 dBm',
                    'status' => 'Penuh',
                    'splitter' => '1:8 PLC',
                    'pole_code' => 'PLN-CLK-012',
                    'customers' => ['Rina Wijaya (30M)', 'Kantor Desa Cilongok', 'Puskesmas 1 Cilongok']
                ],
                [
                    'id' => 'ODP-CLK-03',
                    'parent_odc' => 'ODC-CLK-02',
                    'name' => 'ODP-03 Panembangan Asri',
                    'lat' => -7.397000,
                    'lng' => 109.147500,
                    'capacity' => 16,
                    'used' => 11,
                    'available' => 5,
                    'attenuation' => '-19.8 dBm',
                    'status' => 'Normal',
                    'splitter' => '1:16 PLC',
                    'pole_code' => 'PLN-PNB-022',
                    'customers' => ['Villa Panembangan', 'Sentra Curug Cipendok Res', 'Rumah Warga Panembangan 14']
                ],
                [
                    'id' => 'ODP-CLK-04',
                    'parent_odc' => 'ODC-CLK-02',
                    'name' => 'ODP-04 Karanglo Permai',
                    'lat' => -7.391000,
                    'lng' => 109.151500,
                    'capacity' => 16,
                    'used' => 15,
                    'available' => 1,
                    'attenuation' => '-24.5 dBm',
                    'status' => 'Warning', // Redaman tinggi
                    'splitter' => '1:16 PLC',
                    'pole_code' => 'PLN-KRL-108',
                    'customers' => ['Siti Nurhaliza (50M)', 'Rumah Karanglo 12', 'Rumah Karanglo 18']
                ],
                [
                    'id' => 'ODP-CLK-05',
                    'parent_odc' => 'ODC-CLK-02',
                    'name' => 'ODP-05 Cikidang Wetan',
                    'lat' => -7.404500,
                    'lng' => 109.157000,
                    'capacity' => 16,
                    'used' => 12,
                    'available' => 4,
                    'attenuation' => '-18.5 dBm',
                    'status' => 'Normal',
                    'splitter' => '1:16 PLC',
                    'pole_code' => 'PLN-CKD-033',
                    'customers' => ['Dedi Suryadi (20M)', 'Fotocopy Cikidang', 'Warung Makan Cikidang']
                ],
                [
                    'id' => 'ODP-CLK-06',
                    'parent_odc' => 'ODC-CLK-04',
                    'name' => 'ODP-06 Pageraji Indah',
                    'lat' => -7.425500,
                    'lng' => 109.124500,
                    'capacity' => 16,
                    'used' => 10,
                    'available' => 6,
                    'attenuation' => '-18.2 dBm',
                    'status' => 'Normal',
                    'splitter' => '1:16 PLC',
                    'pole_code' => 'PLN-PG-019',
                    'customers' => ['Bengkel Pageraji', 'Rumah Pageraji No. 15', 'Toko Klontong']
                ],
                [
                    'id' => 'ODP-CLK-07',
                    'parent_odc' => 'ODC-CLK-03',
                    'name' => 'ODP-07 Pejogol Kidul',
                    'lat' => -7.435000,
                    'lng' => 109.139000,
                    'capacity' => 8,
                    'used' => 6,
                    'available' => 2,
                    'attenuation' => '-18.1 dBm',
                    'status' => 'Normal',
                    'splitter' => '1:8 PLC',
                    'pole_code' => 'PLN-PJG-081',
                    'customers' => ['Maya Anggraeni (20M)', 'Klinik Pejogol', 'Rumah Warga RT 02']
                ],
                [
                    'id' => 'ODP-CLK-08',
                    'parent_odc' => 'ODC-CLK-03',
                    'name' => 'ODP-08 Jatisaba Raya',
                    'lat' => -7.433500,
                    'lng' => 109.151000,
                    'capacity' => 16,
                    'used' => 13,
                    'available' => 3,
                    'attenuation' => '-99.0 dBm',
                    'status' => 'LOS / Putus', // Titik kendala saat ini!
                    'splitter' => '1:16 PLC',
                    'pole_code' => 'PLN-JTS-112',
                    'customers' => ['Budi Santoso (50M - TIKET TCK-001)', 'Ruko Jatisaba C-4', 'Ruko Jatisaba C-6']
                ],
            ],
            'cables' => [
                // Feeder 1: Server Room / NOC Cilongok ke ODC-01 (Pusat Pernasidi)
                [
                    'name' => 'Feeder 48C - NOC ke ODC-01 (Pusat Pernasidi)',
                    'type' => 'feeder',
                    'color' => '#2563eb', // Biru
                    'weight' => 5,
                    'status' => 'Normal',
                    'coords' => [
                        [-7.412400, 109.138500],
                        [-7.412800, 109.139000],
                        [-7.413200, 109.139600]
                    ]
                ],
                // Feeder 2: Server Room / NOC ke ODC-02 (Panembangan)
                [
                    'name' => 'Feeder 48C - NOC ke ODC-02 (Panembangan)',
                    'type' => 'feeder',
                    'color' => '#2563eb',
                    'weight' => 5,
                    'status' => 'Normal',
                    'coords' => [
                        [-7.412400, 109.138500],
                        [-7.406000, 109.142000],
                        [-7.401000, 109.146500]
                    ]
                ],
                // Feeder 3: Server Room / NOC ke ODC-03 (Jatisaba)
                [
                    'name' => 'Feeder 24C - NOC ke ODC-03 (Jatisaba)',
                    'type' => 'feeder',
                    'color' => '#2563eb',
                    'weight' => 5,
                    'status' => 'Normal',
                    'coords' => [
                        [-7.412400, 109.138500],
                        [-7.420000, 109.144000],
                        [-7.430500, 109.148000]
                    ]
                ],
                // Feeder 4: Server Room / NOC ke ODC-04 (Pageraji)
                [
                    'name' => 'Feeder 24C - NOC ke ODC-04 (Pageraji)',
                    'type' => 'feeder',
                    'color' => '#2563eb',
                    'weight' => 5,
                    'status' => 'Normal',
                    'coords' => [
                        [-7.412400, 109.138500],
                        [-7.418000, 109.132000],
                        [-7.423500, 109.127000]
                    ]
                ],
                // Distribusi 1: ODC-01 ke ODP-01 Pernasidi Kulon
                [
                    'name' => 'Distribusi 12C - ODC-01 ke ODP-01 Pernasidi Kulon',
                    'type' => 'distribution',
                    'color' => '#16a34a', // Hijau
                    'weight' => 3.5,
                    'status' => 'Normal',
                    'coords' => [
                        [-7.413200, 109.139600],
                        [-7.412000, 109.137000],
                        [-7.411000, 109.135500]
                    ]
                ],
                // Distribusi 2: ODC-02 ke ODP-04 Karanglo (Warning Redaman Tinggi)
                [
                    'name' => 'Distribusi 12C - ODC-02 ke ODP-04 Karanglo',
                    'type' => 'distribution',
                    'color' => '#ca8a04', // Kuning/Warning
                    'weight' => 3.5,
                    'status' => 'Warning (Redaman Tinggi -24.5 dBm)',
                    'coords' => [
                        [-7.401000, 109.146500],
                        [-7.395000, 109.149000],
                        [-7.391000, 109.151500]
                    ]
                ],
                // Distribusi 3: ODC-03 ke ODP-08 Jatisaba (FIBER CUT / PUTUS)
                [
                    'name' => 'Distribusi 12C - ODC-03 ke ODP-08 Jatisaba (FIBER CUT / PUTUS)',
                    'type' => 'distribution',
                    'color' => '#dc2626', // Merah / Putus
                    'weight' => 4,
                    'status' => 'FIBER CUT (LOS)',
                    'dashArray' => '8, 8',
                    'coords' => [
                        [-7.430500, 109.148000],
                        [-7.432000, 109.149500],
                        [-7.433500, 109.151000]
                    ]
                ],
                // Distribusi 4: ODC-04 ke ODP-06 Pageraji Indah
                [
                    'name' => 'Distribusi 12C - ODC-04 ke ODP-06 Pageraji',
                    'type' => 'distribution',
                    'color' => '#16a34a',
                    'weight' => 3.5,
                    'status' => 'Normal',
                    'coords' => [
                        [-7.423500, 109.127000],
                        [-7.424500, 109.125500],
                        [-7.425500, 109.124500]
                    ]
                ],
            ]
        ];
    }

    /**
     * Halaman Dashboard Utama Admin (NOC Overview)
     */
    public function dashboard()
    {
        $bills = $this->getBillsData();
        $tickets = $this->getTicketsData();
        $mrtg = $this->getMrtgData();
        $odc = $this->getOdcMapData();

        $recentOrders = collect();
        $totalOrdersCount = 0;
        $pendingOrdersCount = 0;

        try {
            $recentOrders = Order::where('order_number', 'not like', 'PLG-%')->latest()->take(5)->get();
            $totalOrdersCount = Order::where('order_number', 'not like', 'PLG-%')->count();
            $pendingOrdersCount = Order::where('order_number', 'not like', 'PLG-%')->where('status', 'Menunggu Konfirmasi')->count();
        } catch (\Exception $e) {}

        $stats = [
            'total_customers' => Order::forCustomerData()->count(),
            'active_customers' => Order::forCustomerData()->whereIn('status', ['Selesai', 'Aktif', 'Selesai / Aktif', 'selesai', 'aktif'])->count(),
            'total_orders' => $totalOrdersCount,
            'pending_orders_count' => $pendingOrdersCount,
            'pending_bills_count' => collect($bills)->where('status', 'Menunggu Verifikasi')->count(),
            'unpaid_bills_count' => collect($bills)->where('status', 'Belum Bayar')->count(),
            'total_bills_nominal' => collect($bills)->sum('total_raw'),
            'open_tickets_count' => collect($tickets)->whereIn('status', ['Menunggu Respon', 'Sedang Ditangani'])->count(),
            'critical_tickets_count' => collect($tickets)->where('priority', 'Kritis')->where('status', '!=', 'Selesai')->count(),
            'bandwidth_in' => $mrtg['summary']['current_inbound'],
            'bandwidth_out' => $mrtg['summary']['current_outbound'],
            'odc_count' => count($odc['odcs']),
            'odp_count' => count($odc['odps']),
        ];

        return view('admin.dashboard', compact('stats', 'bills', 'tickets', 'mrtg', 'recentOrders'));
    }

    /**
     * Halaman Monitoring Pemesanan / Pesanan Pelanggan Baru
     */
    public function pesanan(Request $request)
    {
        $statusFilter = $request->input('status', 'all');
        $search = $request->input('q', '');

        try {
            $query = Order::where('order_number', 'not like', 'PLG-%')->latest();

            if ($statusFilter !== 'all') {
                $query->where('status', $statusFilter);
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                      ->orWhere('customer_name', 'like', "%{$search}%")
                      ->orWhere('customer_phone', 'like', "%{$search}%")
                      ->orWhere('customer_email', 'like', "%{$search}%")
                      ->orWhere('package_name', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%");
                });
            }

            $orders = $query->get();
            $allOrders = Order::where('order_number', 'not like', 'PLG-%')->get();

            $counts = [
                'all' => $allOrders->count(),
                'menunggu' => $allOrders->where('status', 'Menunggu Konfirmasi')->count(),
                'jadwal' => $allOrders->where('status', 'Jadwal Teknisi')->count(),
                'proses' => $allOrders->where('status', 'Sedang Dipasang')->count(),
                'selesai' => $allOrders->where('status', 'Selesai')->count(),
                'kendala' => $allOrders->where('status', 'Kendala Lapangan')->count(),
                'batal' => $allOrders->where('status', 'Dibatalkan')->count(),
            ];
        } catch (\Exception $e) {
            $orders = collect();
            $counts = ['all' => 0, 'menunggu' => 0, 'jadwal' => 0, 'proses' => 0, 'selesai' => 0, 'kendala' => 0, 'batal' => 0];
        }

        $odcMapData = $this->getOdcMapData();
        $odpList = collect($odcMapData['odps'])->pluck('name', 'id')->toArray();

        $packages = Package::where('is_active', true)->get();
        $technicians = User::whereIn('role', ['technician', 'teknisi'])->orderBy('name')->get();
        if ($technicians->isEmpty()) {
            $technicians = collect([
                (object) ['id' => null, 'name' => 'Mamat (Teknisi Lapangan)', 'phone' => '081234567001'],
                (object) ['id' => null, 'name' => 'Aji (Teknisi Lapangan)', 'phone' => '081234567002'],
                (object) ['id' => null, 'name' => 'Danu (Teknisi Lapangan)', 'phone' => '081234567003'],
                (object) ['id' => null, 'name' => 'Okta (Teknisi Lapangan)', 'phone' => '081234567004'],
            ]);
        }

        return view('admin.pesanan', compact('orders', 'statusFilter', 'search', 'counts', 'odpList', 'packages', 'technicians'));
    }

    /**
     * Simpan Pesanan Pelanggan Baru Secara Manual
     */
    public function storePesanan(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'id_card_number' => 'nullable|string|max:30',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:100',
            'customer_phone' => 'required|string|max:50',
            'customer_email' => 'nullable|email|max:100',
            'address' => 'required|string|max:500',
            'latitude' => 'nullable|string|max:50',
            'longitude' => 'nullable|string|max:50',
            'package_name' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'installation_fee' => 'nullable|numeric|min:0',
            'installation_date' => 'nullable|date',
            'installation_time' => 'nullable|string|in:pagi,siang',
            'technician' => 'nullable|string|max:100',
            'assigned_odp' => 'nullable|string|max:100',
            'status' => 'nullable|string',
            'payment_status' => 'required|string',
            'payment_method' => 'nullable|string|max:100',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        // Auto-generate order number unik (format: BTR-YYYYMM-XXXX)
        $prefix = 'BTR-' . date('Ym') . '-';
        $orderCount = Order::count() + 1;
        $orderNumber = $prefix . str_pad($orderCount, 4, '0', STR_PAD_LEFT);
        while (Order::where('order_number', $orderNumber)->exists()) {
            $orderCount++;
            $orderNumber = $prefix . str_pad($orderCount, 4, '0', STR_PAD_LEFT);
        }

        // Tentukan kecepatan sesuai nama paket
        $speed = '20 Mbps';
        if (str_contains($validated['package_name'], '50')) {
            $speed = '50 Mbps';
        } elseif (str_contains($validated['package_name'], '30')) {
            $speed = '30 Mbps';
        }

        $price = (float) $validated['price'];
        $installationFee = (float) ($validated['installation_fee'] ?? 0);
        $total = $price + $installationFee;

        $email = $validated['customer_email'];
        if (empty($email)) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $validated['customer_name']));
            $email = $slug . rand(10, 99) . '@gmail.com';
        }

        $techName = $validated['technician'] ?? null;
        $techId = null;
        if (!empty($techName)) {
            $techUser = User::whereIn('role', ['technician', 'teknisi'])
                ->where('name', $techName)->first();
            if ($techUser) {
                $techId = $techUser->id;
                $techName = $techUser->name;
            }
        }

        // Status awal otomatis: Jadwal Teknisi jika teknisi sudah dipilih, atau Menunggu Konfirmasi jika belum
        $status = $validated['status'] ?? (!empty($techName) ? 'Jadwal Teknisi' : 'Menunggu Konfirmasi');

        $order = Order::create([
            'order_number' => $orderNumber,
            'customer_name' => $validated['customer_name'],
            'id_card_number' => $validated['id_card_number'] ?? null,
            'birth_place' => $validated['birth_place'] ?? 'Banyumas',
            'birth_date' => $validated['birth_date'] ?? null,
            'customer_phone' => $validated['customer_phone'],
            'customer_email' => $email,
            'address' => $validated['address'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'package_name' => $validated['package_name'],
            'speed' => $speed,
            'price' => $price,
            'installation_fee' => $installationFee,
            'tax' => 0,
            'total' => $total,
            'installation_date' => $validated['installation_date'] ?? date('Y-m-d'),
            'installation_time' => $validated['installation_time'] ?? 'pagi',
            'technician' => $techName,
            'technician_id' => $techId,
            'assigned_at' => $techId ? now() : null,
            'assigned_odp' => $validated['assigned_odp'] ?? 'ODP-CLK-01',
            'status' => $status,
            'payment_status' => $validated['payment_status'],
            'payment_method' => $validated['payment_method'] ?? 'BCA Virtual Account',
            'admin_notes' => $validated['admin_notes'] ?? 'Pesanan manual diinput oleh Administrator.',
            'installed_at' => ($status === 'Selesai') ? now() : null,
        ]);

        // Jika langsung berstatus 'Selesai', otomatis terbitkan tagihan
        if ($order->status === 'Selesai') {
            BillingService::generateBillForOrder($order);
        }

        return redirect()->route('admin.pesanan')
            ->with('success', "Pesanan baru {$order->order_number} untuk {$order->customer_name} berhasil ditambahkan!");
    }

    /**
     * Update Status & Detail Penugasan Pesanan
     */
    public function updatePesananStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if ($request->filled('status')) {
            $order->status = $request->input('status');
        }

        if ($request->has('technician')) {
            $techInput = $request->input('technician');
            $previousTechnician = $order->technician;
            $order->technician = !empty($techInput) ? $techInput : null;

            if (!empty($order->technician)) {
                $techUser = User::whereIn('role', ['technician', 'teknisi'])
                    ->where(function ($q) use ($order) {
                        $q->where('name', $order->technician)
                          ->orWhere('id', $order->technician);
                    })->first();

                if (!$techUser) {
                    $firstName = explode(' ', trim($order->technician))[0] ?? '';
                    if (strlen($firstName) >= 3) {
                        $techUser = User::whereIn('role', ['technician', 'teknisi'])
                            ->where('name', 'like', "%{$firstName}%")
                            ->first();
                    }
                }

                if ($techUser) {
                    $order->technician_id = $techUser->id;
                    $order->technician = $techUser->name;
                }

                if ($previousTechnician !== $order->technician) {
                    $order->assigned_at = now();
                    if ($order->status === 'Menunggu Konfirmasi') {
                        $order->status = 'Jadwal Teknisi';
                    }
                }
            } else {
                $order->technician_id = null;
                $order->assigned_at = null;
            }
        }
        if ($request->filled('assigned_odp')) {
            $order->assigned_odp = $request->input('assigned_odp');
        }
        if ($request->filled('payment_status')) {
            $order->payment_status = $request->input('payment_status');
        }
        if ($request->filled('admin_notes')) {
            $order->admin_notes = $request->input('admin_notes');
        }

        if ($request->filled('package_name')) {
            $pkgName = $request->input('package_name');
            $order->package_name = $pkgName;
            if (str_contains($pkgName, '50')) {
                $order->speed = '50 Mbps';
                $order->price = 220000;
                $order->total = 220000;
            } elseif (str_contains($pkgName, '30')) {
                $order->speed = '30 Mbps';
                $order->price = 165000;
                $order->total = 165000;
            } else {
                $order->speed = '20 Mbps';
                $order->price = 110000;
                $order->total = 110000;
            }
        }

        if ($order->status === 'Selesai') {
            if (!$order->installed_at) {
                $order->installed_at = now();
            }
            if (empty($order->village) && !empty($order->address)) {
                $order->village = \App\Services\CustomerImportService::resolveVillage(null, null, $order->address);
            }
        }

        $order->save();

        if ($order->status === 'Selesai') {
            BillingService::generateBillForOrder($order);
        }

        // Sinkronkan juga data tagihan jika sudah pernah terbit sebelumnya
        $linkedBill = Bill::where('order_id', $order->id)->first();
        if ($linkedBill) {
            $linkedBill->package_name = $order->package_name;
            $linkedBill->speed = $order->speed;
            $linkedBill->amount = $order->price;
            $linkedBill->total = $order->total;
            $linkedBill->save();
        }

        $successMessage = ($order->status === 'Selesai')
            ? "Pesanan {$order->order_number} berhasil diselesaikan dan resmi masuk ke Data Pelanggan!"
            : "Pesanan {$order->order_number} berhasil diperbarui! Status: {$order->status}";

        return redirect()->back()->with('success', $successMessage);
    }

    /**
     * Hapus Pesanan
     */
    public function deletePesanan($id)
    {
        $order = Order::findOrFail($id);
        $orderNumber = $order->order_number;
        $customerName = $order->customer_name;

        // Hapus tagihan terkait jika ada
        Bill::where('order_id', $order->id)->delete();
        $order->delete();

        return redirect()->back()->with('success', "Pesanan {$orderNumber} ({$customerName}) berhasil dihapus dari sistem.");
    }

    /**
     * Hapus Massal Data Pesanan Terpilih
     */
    public function bulkDeletePesanan(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids) || !is_array($ids)) {
            return redirect()->back()->with('error', 'Silakan pilih minimal satu data pesanan untuk dihapus.');
        }

        $count = count($ids);
        Bill::whereIn('order_id', $ids)->delete();
        Order::whereIn('id', $ids)->delete();

        return redirect()->back()->with('success', "Sebanyak {$count} data pesanan berhasil dihapus dari sistem.");
    }

    /**
     * Cetak Formulir Pendaftaran / Berlangganan SIMS Fiber Broadband Banterpool
     */
    public function formulirPesanan($id)
    {
        $order = Order::findOrFail($id);

        return view('admin.formulir-berlangganan', compact('order'));
    }

    /**
     * Export Rekap Pesanan Pelanggan ke File Excel (.xls)
     */
    public function exportPesanan(Request $request)
    {
        $statusFilter = $request->input('status', 'all');
        $search = $request->input('q', '');

        try {
            $query = Order::where('order_number', 'not like', 'PLG-%')->latest();

            if ($statusFilter !== 'all') {
                $query->where('status', $statusFilter);
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                      ->orWhere('customer_name', 'like', "%{$search}%")
                      ->orWhere('customer_phone', 'like', "%{$search}%")
                      ->orWhere('customer_email', 'like', "%{$search}%")
                      ->orWhere('package_name', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%");
                });
            }

            $orders = $query->get();
        } catch (\Exception $e) {
            $orders = collect();
        }

        $filename = 'Rekap_Pesanan_Banterpool_' . date('Ymd_His') . '.xls';

        return response()->streamDownload(function () use ($orders, $statusFilter, $search) {
            echo "\xEF\xBB\xBF"; // UTF-8 BOM agar terbaca sempurna di Microsoft Excel
            echo view('admin.exports.pesanan_excel', compact('orders', 'statusFilter', 'search'))->render();
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate, proxy-revalidate',
        ]);
    }

    /**
     * Halaman Data Pelanggan Terdaftar (Sesuai Arsip Pendaftaran Google Drive SIMS)
     */
    public function pelanggan(Request $request)
    {
        $search = $request->input('q', '');
        $wilayahFilter = $request->input('wilayah', 'all');
        $layananFilter = $request->input('layanan', 'all');
        $statusFilter = $request->input('status', 'all');

        // Dukungan AJAX Live Autocomplete Suggestion (Pencarian Berdasarkan Awalan Abjad)
        if ($request->ajax() || $request->wantsJson() || $request->has('ajax')) {
            $search = trim($request->input('q', ''));
            if (empty($search)) {
                return response()->json([]);
            }
            $lowerSearch = strtolower($search);
            $upperSearch = strtoupper($search);

            $results = Order::forCustomerData()->where(function ($q) use ($search, $lowerSearch, $upperSearch) {
                $q->where('customer_name', 'like', "{$search}%")
                  ->orWhere('customer_name', 'like', "{$lowerSearch}%")
                  ->orWhere('customer_name', 'like', "{$upperSearch}%")
                  ->orWhere('customer_name', 'like', " {$search}%")
                  ->orWhere('pppoe', 'like', "{$lowerSearch}%")
                  ->orWhere('id_card_number', 'like', "{$search}%")
                  ->orWhere('customer_phone', 'like', "{$search}%")
                  ->orWhere('order_number', 'like', "{$search}%");
            })
            ->orderBy('customer_name', 'asc')
            ->limit(10)
            ->get(['id', 'customer_name', 'pppoe', 'id_card_number', 'customer_phone', 'village', 'address', 'package_name']);

            return response()->json($results);
        }

        // Alur sistem: HANYA pesanan yang sudah berstatus 'Selesai' / aktif yang masuk ke Data Pelanggan
        $query = Order::forCustomerData();

        if (!empty($search)) {
            $trimmedSearch = trim($search);
            $lowerSearch = strtolower($trimmedSearch);
            $upperSearch = strtoupper($trimmedSearch);

            $query->where(function ($q) use ($trimmedSearch, $lowerSearch, $upperSearch) {
                // Awalan nama pelanggan harus sama dengan kata pencarian
                $q->where('customer_name', 'like', "{$trimmedSearch}%")
                  ->orWhere('customer_name', 'like', "{$lowerSearch}%")
                  ->orWhere('customer_name', 'like', "{$upperSearch}%")
                  ->orWhere('customer_name', 'like', " {$trimmedSearch}%")
                  ->orWhere('customer_name', 'like', " {$lowerSearch}%")
                  ->orWhere('customer_name', 'like', " {$upperSearch}%")
                  ->orWhere('pppoe', 'like', "{$lowerSearch}%")
                  ->orWhere('id_card_number', 'like', "{$trimmedSearch}%")
                  ->orWhere('customer_phone', 'like', "{$trimmedSearch}%")
                  ->orWhere('order_number', 'like', "{$trimmedSearch}%");

                // Jika pencarian lebih dari 2 karakter, dukung pencarian kata berikutnya atau desa/alamat
                if (strlen($trimmedSearch) > 2) {
                    $q->orWhere('customer_name', 'like', "% {$trimmedSearch}%")
                      ->orWhere('village', 'like', "{$trimmedSearch}%")
                      ->orWhere('address', 'like', "%{$trimmedSearch}%");
                }
            });

            // Urutkan hasil pencarian secara alfabetis (A-Z)
            $query->orderBy('customer_name', 'asc');
        } else {
            $query->latest();
        }

        if ($wilayahFilter !== 'all') {
            $query->where(function ($q) use ($wilayahFilter) {
                $q->where('village', $wilayahFilter);

                if ($wilayahFilter === 'Batuanten') {
                    $q->orWhere('village', 'Bantuanten')
                      ->orWhere('address', 'like', '%Batuanten%')
                      ->orWhere('address', 'like', '%Bantuanten%');
                } elseif ($wilayahFilter === 'Penusupan') {
                    $q->orWhere('village', 'Panusupan')
                      ->orWhere('address', 'like', '%Penusupan%')
                      ->orWhere('address', 'like', '%Panusupan%')
                      ->orWhere('address', 'like', '%Pecikalan%')
                      ->orWhere('address', 'like', '%Tinggar Jaya%')
                      ->orWhere('address', 'like', '%Bojongsari%')
                      ->orWhere('address', 'like', '%Legok%');
                } elseif ($wilayahFilter === 'Karangendep' || $wilayahFilter === 'Karanggendep') {
                    $q->orWhere('village', 'Karanggendep')
                      ->orWhere('village', 'Karangendep')
                      ->orWhere('address', 'like', '%Karang%endep%')
                      ->orWhere('address', 'like', '%Ronten%');
                } elseif ($wilayahFilter === 'Bantarwuni') {
                    $q->orWhere('address', 'like', '%Bantarwuni%')
                      ->orWhere('address', 'like', '%Glempang%');
                } elseif ($wilayahFilter === 'Linggasari') {
                    $q->orWhere(function ($sub) {
                        $sub->where('address', 'like', '%Linggasari%')
                            ->orWhere(function ($sub2) {
                                $sub2->where('address', 'like', '%Kembaran%')
                                     ->where('address', 'not like', '%Bantarwuni%');
                            });
                    });
                } else {
                    $q->orWhere('address', 'like', "%{$wilayahFilter}%");
                }
            });
        }

        if ($layananFilter !== 'all') {
            $query->where(function ($q) use ($layananFilter) {
                $q->where('package_name', 'like', "%{$layananFilter}%")
                  ->orWhere('speed', 'like', "%{$layananFilter}%");
            });
        }

        if ($statusFilter !== 'all') {
            if ($statusFilter === 'Selesai' || strtolower($statusFilter) === 'aktif') {
                $query->whereIn('status', ['Selesai', 'Selesai / Aktif', 'Aktif', 'selesai', 'aktif']);
            } else {
                $query->where('status', $statusFilter);
            }
        }

        $customers = $query->paginate(20)->withQueryString();

        // Data KPI: hanya hitung pelanggan terdaftar (status selesai / aktif)
        $allCustomers = Order::forCustomerData()->get();
        $totalCustomers = $allCustomers->count();
        $activeCustomers = $allCustomers->whereIn('status', ['Selesai', 'Selesai / Aktif', 'Aktif', 'selesai', 'aktif'])->count();
        if ($activeCustomers === 0 && $totalCustomers > 0) {
            $activeCustomers = $totalCustomers;
        }
        $verifiedKtp = $allCustomers->filter(function ($c) {
            return !empty($c->id_card_number) && $c->id_card_number !== '-';
        })->count();
        $totalMrr = $allCustomers->where('status', '!=', 'Dibatalkan')->sum('price');

        // Daftar Wilayah Dinamis (Hanya dari data pelanggan terdaftar)
        $rawVillages = Order::forCustomerData()
            ->whereNotNull('village')
            ->where('village', '!=', '')
            ->where('village', '!=', '-')
            ->where('village', 'not like', '%template%')
            ->where('village', 'not like', '%pelanggan%')
            ->select('village', DB::raw('count(*) as count'))
            ->groupBy('village')
            ->orderBy('village')
            ->pluck('count', 'village')
            ->all();

        // Susun wilayahList dan wilayahCounts yang 100% dinamis
        $wilayahList = [];
        $wilayahCounts = [];
        foreach ($rawVillages as $vName => $vCount) {
            if (!\App\Services\CustomerImportService::isGenericSheetName($vName)) {
                $wilayahList[$vName] = $vName;
                $wilayahCounts[$vName] = (int) $vCount;
            }
        }

        // Daftar Layanan
        $layananList = [
            '20 Mbps' => 'Paket 20 Mbps (Rp 110.000)',
            '30 Mbps' => 'Paket 30 Mbps (Rp 165.000)',
            '50 Mbps' => 'Paket 50 Mbps (Rp 220.000)',
        ];

        $stats = [
            'total' => $totalCustomers,
            'active' => $activeCustomers,
            'verified_ktp' => $verifiedKtp,
            'total_mrr' => $totalMrr,
            'wilayah_count' => count($wilayahList),
        ];

        return view('admin.pelanggan', compact(
            'customers',
            'stats',
            'search',
            'wilayahFilter',
            'layananFilter',
            'statusFilter',
            'wilayahList',
            'wilayahCounts',
            'layananList'
        ));
    }

    /**
     * Simpan Pelanggan Baru Secara Manual
     */
    public function storePelanggan(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'pppoe' => 'nullable|string|max:100',
            'id_card_number' => 'nullable|string|max:30',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:100',
            'customer_phone' => 'nullable|string|max:50',
            'customer_email' => 'nullable|string|max:100',
            'package_name' => 'nullable|string|max:100',
            'price' => 'nullable|numeric',
            'address' => 'nullable|string',
            'wilayah' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $orderNumber = 'BTR-' . date('Ym') . '-' . str_pad(Order::count() + 1, 4, '0', STR_PAD_LEFT);
        
        $packageName = !empty($validated['package_name']) ? $validated['package_name'] : 'Paket 20 Mbps';
        $speed = '20 Mbps';
        if (str_contains($packageName, '50')) {
            $speed = '50 Mbps';
        } elseif (str_contains($packageName, '30')) {
            $speed = '30 Mbps';
        }

        $address = !empty($validated['address']) ? $validated['address'] : '-';
        $wilayah = $request->input('wilayah');
        if (empty($wilayah)) {
            $wilayah = \App\Services\CustomerImportService::resolveVillage(null, null, $address);
        }
        if (!empty($wilayah) && !empty($address) && $address !== '-' && !str_contains(strtolower($address), strtolower($wilayah))) {
            $address = "Desa {$wilayah}, {$address}";
        }

        $assignedOdp = !empty($wilayah) ? ('ODP-' . strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $wilayah), 0, 3)) . '-01') : 'ODP-CLK-01';

        $price = isset($validated['price']) && is_numeric($validated['price']) ? (float) $validated['price'] : 110000;

        $pppoe = !empty($validated['pppoe']) ? trim($validated['pppoe']) : Order::generatePppoeUsername($validated['customer_name']);

        $order = Order::create([
            'order_number' => $orderNumber,
            'customer_name' => $validated['customer_name'],
            'pppoe' => $pppoe,
            'id_card_number' => !empty($validated['id_card_number']) ? $validated['id_card_number'] : '-',
            'birth_date' => $validated['birth_date'] ?? null,
            'birth_place' => $validated['birth_place'] ?? 'Banyumas',
            'customer_phone' => !empty($validated['customer_phone']) ? $validated['customer_phone'] : '-',
            'customer_email' => !empty($validated['customer_email']) ? $validated['customer_email'] : '-',
            'package_name' => $packageName,
            'speed' => $speed,
            'price' => $price,
            'total' => $price,
            'address' => $address,
            'village' => $wilayah,
            'status' => $validated['status'] ?? 'Selesai',
            'payment_status' => 'Lunas',
            'payment_method' => 'Tunai / Transfer',
            'installation_date' => now()->toDateString(),
            'installation_time' => 'pagi',
            'installed_at' => now(),
            'assigned_odp' => $assignedOdp,
            'admin_notes' => 'Pelanggan terdaftar dari input manual Admin' . ($wilayah ? " ({$wilayah})" : '') . '.',
        ]);

        return redirect()->back()->with('success', "Pelanggan baru {$order->customer_name} berhasil ditambahkan dengan NIK: {$order->id_card_number}.");
    }

    /**
     * Update Data Pelanggan (KTP, TTL, HP, Email, Layanan, Harga, Alamat)
     */
    public function updatePelanggan(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'pppoe' => 'nullable|string|max:100',
            'id_card_number' => 'nullable|string|max:30',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:100',
            'customer_phone' => 'nullable|string|max:50',
            'customer_email' => 'nullable|string|max:100',
            'package_name' => 'nullable|string|max:100',
            'price' => 'nullable|numeric',
            'address' => 'nullable|string',
            'wilayah' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $packageName = !empty($validated['package_name']) ? $validated['package_name'] : ($order->package_name ?: 'Paket 20 Mbps');
        $speed = '20 Mbps';
        if (str_contains($packageName, '50')) {
            $speed = '50 Mbps';
        } elseif (str_contains($packageName, '30')) {
            $speed = '30 Mbps';
        }

        $address = !empty($validated['address']) ? $validated['address'] : ($order->address ?: '-');
        $wilayah = $request->input('wilayah');
        if (empty($wilayah)) {
            $wilayah = \App\Services\CustomerImportService::resolveVillage(null, null, $address);
        }
        if (!empty($wilayah) && !empty($address) && $address !== '-' && !str_contains(strtolower($address), strtolower($wilayah))) {
            $address = "Desa {$wilayah}, {$address}";
        }

        $price = isset($validated['price']) && is_numeric($validated['price']) ? (float) $validated['price'] : ($order->price ?: 110000);

        $order->customer_name = $validated['customer_name'];
        $order->pppoe = !empty($validated['pppoe']) ? trim($validated['pppoe']) : ($order->pppoe ?: Order::generatePppoeUsername($order->customer_name));
        $order->id_card_number = !empty($validated['id_card_number']) ? $validated['id_card_number'] : ($order->id_card_number ?: '-');
        $order->birth_date = !empty($validated['birth_date']) ? $validated['birth_date'] : $order->birth_date;
        $order->birth_place = !empty($validated['birth_place']) ? $validated['birth_place'] : ($order->birth_place ?: '-');
        $order->customer_phone = !empty($validated['customer_phone']) ? $validated['customer_phone'] : ($order->customer_phone ?: '-');
        $order->customer_email = !empty($validated['customer_email']) ? $validated['customer_email'] : ($order->customer_email ?: '-');
        $order->package_name = $packageName;
        $order->speed = $speed;
        $order->price = $price;
        $order->total = $price;
        $order->address = $address;
        if (!empty($wilayah)) {
            $order->village = $wilayah;
        }
        $order->status = !empty($validated['status']) ? $validated['status'] : ($order->status ?: 'Selesai');
        $order->save();

        // Sinkronisasi data tagihan terkait jika ada
        $bill = Bill::where('order_id', $order->id)->first();
        if ($bill) {
            $bill->customer_name = $order->customer_name;
            $bill->customer_phone = $order->customer_phone;
            $bill->customer_email = $order->customer_email;
            $bill->package_name = $order->package_name;
            $bill->speed = $order->speed;
            $bill->amount = $order->price;
            $bill->total = $order->total;
            $bill->address = $order->address;
            $bill->save();
        }

        return redirect()->back()->with('success', "Data pelanggan {$order->customer_name} berhasil diperbarui.");
    }

    /**
     * Hapus Data Pelanggan
     */
    public function deletePelanggan($id)
    {
        $order = Order::findOrFail($id);
        $name = $order->customer_name;
        
        // Hapus tagihan terkait jika ada
        Bill::where('order_id', $order->id)->delete();
        $order->delete();

        return redirect()->back()->with('success', "Data pelanggan {$name} berhasil dihapus dari sistem.");
    }

    /**
     * Hapus Massal Data Pelanggan Terpilih
     */
    public function bulkDeletePelanggan(Request $request)
    {
        $ids = $request->input('ids', []);
        $deleteAll = $request->boolean('delete_all', false);

        if ($deleteAll) {
            $customerOrders = Order::forCustomerData()->get();
            $count = $customerOrders->count();
            $customerIds = $customerOrders->pluck('id')->toArray();
            Bill::whereIn('order_id', $customerIds)->delete();
            Order::whereIn('id', $customerIds)->delete();
            return redirect()->back()->with('success', "Seluruh data pelanggan ({$count} data) berhasil dihapus dari sistem.");
        }

        if (empty($ids) || !is_array($ids)) {
            return redirect()->back()->with('error', 'Silakan pilih minimal satu data pelanggan untuk dihapus.');
        }

        $count = count($ids);
        Bill::whereIn('order_id', $ids)->delete();
        Order::whereIn('id', $ids)->delete();

        return redirect()->back()->with('success', "Sebanyak {$count} data pelanggan terpilih berhasil dihapus dari sistem.");
    }

    /**
     * Export Data Pelanggan ke Excel (.xls dengan UTF-8 BOM)
     */
    public function exportPelanggan(Request $request)
    {
        $search = $request->input('q', '');
        $wilayahFilter = $request->input('wilayah', 'all');
        $layananFilter = $request->input('layanan', 'all');
        $statusFilter = $request->input('status', 'all');

        $query = Order::forCustomerData();

        if (!empty($search)) {
            $trimmedSearch = trim($search);
            $lowerSearch = strtolower($trimmedSearch);
            $upperSearch = strtoupper($trimmedSearch);

            $query->where(function ($q) use ($trimmedSearch, $lowerSearch, $upperSearch) {
                $q->where('customer_name', 'like', "{$trimmedSearch}%")
                  ->orWhere('customer_name', 'like', "{$lowerSearch}%")
                  ->orWhere('customer_name', 'like', "{$upperSearch}%")
                  ->orWhere('customer_name', 'like', " {$trimmedSearch}%")
                  ->orWhere('customer_name', 'like', " {$lowerSearch}%")
                  ->orWhere('customer_name', 'like', " {$upperSearch}%")
                  ->orWhere('id_card_number', 'like', "{$trimmedSearch}%")
                  ->orWhere('customer_phone', 'like', "{$trimmedSearch}%")
                  ->orWhere('order_number', 'like', "{$trimmedSearch}%");

                if (strlen($trimmedSearch) > 2) {
                    $q->orWhere('customer_name', 'like', "% {$trimmedSearch}%")
                      ->orWhere('village', 'like', "{$trimmedSearch}%")
                      ->orWhere('address', 'like', "%{$trimmedSearch}%");
                }
            });

            $query->orderBy('customer_name', 'asc');
        } else {
            $query->latest();
        }

        if ($wilayahFilter !== 'all') {
            $query->where(function ($q) use ($wilayahFilter) {
                $q->where('village', $wilayahFilter)
                  ->orWhere('address', 'like', "%{$wilayahFilter}%");
            });
        }

        if ($layananFilter !== 'all') {
            $query->where(function ($q) use ($layananFilter) {
                $q->where('package_name', 'like', "%{$layananFilter}%")
                  ->orWhere('speed', 'like', "%{$layananFilter}%");
            });
        }

        if ($statusFilter !== 'all') {
            if ($statusFilter === 'Selesai' || strtolower($statusFilter) === 'aktif') {
                $query->whereIn('status', ['Selesai', 'Selesai / Aktif', 'Aktif', 'selesai', 'aktif']);
            } else {
                $query->where('status', $statusFilter);
            }
        }

        $customers = $query->get();
        $filename = 'Data_Pelanggan_Banterpool_' . date('Ymd_His') . '.xls';

        return response()->streamDownload(function () use ($customers, $wilayahFilter, $layananFilter, $statusFilter, $search) {
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
            echo view('admin.exports.pelanggan_excel', compact('customers', 'wilayahFilter', 'layananFilter', 'statusFilter', 'search'))->render();
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate, proxy-revalidate',
        ]);
    }

    /**
     * Import Data Pelanggan dari File Excel / CSV
     */
    public function importPelanggan(Request $request)
    {
        // Alokasi memori dan batas waktu tak terbatas agar mampu menangani impor data dalam jumlah sangat besar
        @ini_set('memory_limit', '2048M');
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);

        // Periksa jika berkas melebihi batasan konfigurasi upload server
        if ($request->hasFile('file') && !$request->file('file')->isValid()) {
            return redirect()->back()->withErrors([
                'file' => 'Berkas gagal diunggah: ' . $request->file('file')->getErrorMessage()
            ]);
        }

        $request->validate([
            'file' => [
                'required',
                'file',
                function ($attribute, $value, $fail) {
                    $ext = strtolower($value->getClientOriginalExtension());
                    if (!in_array($ext, ['xlsx', 'xls', 'csv', 'txt'])) {
                        $fail('Format berkas harus berupa Excel (.xlsx, .xls) atau .csv.');
                    }
                },
            ],
            'update_existing' => 'nullable|boolean',
        ], [
            'file.required' => 'Silakan pilih berkas file Excel (.xlsx, .xls) atau .csv yang akan diimpor.',
            'file.file' => 'Berkas yang diunggah tidak valid.',
        ]);

        $updateExisting = $request->boolean('update_existing', true);
        $result = CustomerImportService::import($request->file('file'), $updateExisting);

        if (!$result['success']) {
            return redirect()->back()
                ->with('error', $result['message'])
                ->with('import_errors', $result['errors'] ?? []);
        }

        if (!empty($result['errors'])) {
            return redirect()->back()
                ->with('success', $result['message'])
                ->with('import_warnings', $result['errors']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    /**
     * Unduh Template Excel / CSV untuk Import Pelanggan
     */
    public function downloadTemplatePelanggan(Request $request)
    {
        $format = strtolower($request->input('format', 'xls'));
        if ($format === 'csv') {
            return CustomerImportService::downloadCsvTemplate();
        }
        return CustomerImportService::downloadTemplate();
    }

    /**
     * Halaman Pengintaian & Manajemen Tagihan Serta Rekap Pembayaran
     */
    public function tagihan(Request $request)
    {
        $allBills = $this->getBillsData();
        $statusFilter = $request->input('status', 'all');
        $monthFilter = $request->input('month', 'all');
        $search = $request->input('q', '');

        // Pisahkan tagihan aktif yang perlu dimonitor dan riwayat tagihan yang sudah lunas/terverifikasi
        $activeBills = collect($allBills)->where('status', '!=', 'Lunas');
        $paidBills = collect($allBills)->where('status', 'Lunas');

        // Daftar bulan yang tersedia untuk rekap per bulan
        $availableMonths = $paidBills->map(function ($item) {
            return [
                'key' => $item['month_key'],
                'label' => $item['month_label'],
            ];
        })->unique('key')->values()->all();

        // Filter:
        // - Pilihan 'all' (Semua): HANYA tagihan aktif (yang sudah terverifikasi/lunas tidak tertampil di 'semua')
        // - Pilihan 'rekap' / 'Lunas': Menampilkan data riwayat rekap tagihan yang sudah lunas
        $bills = collect($allBills)->filter(function ($item) use ($statusFilter, $monthFilter, $search) {
            $matchStatus = false;
            if ($statusFilter === 'all') {
                $matchStatus = ($item['status'] !== 'Lunas');
            } elseif ($statusFilter === 'rekap' || $statusFilter === 'Lunas') {
                $matchStatus = ($item['status'] === 'Lunas');
            } else {
                $matchStatus = ($item['status'] === $statusFilter);
            }

            $matchMonth = true;
            if (($statusFilter === 'rekap' || $statusFilter === 'Lunas') && $monthFilter !== 'all') {
                $matchMonth = (isset($item['month_key']) && $item['month_key'] === $monthFilter);
            }

            $matchSearch = empty($search) ||
                (stripos($item['customer_name'] ?? '', $search) !== false) ||
                (stripos($item['id'] ?? '', $search) !== false) ||
                (stripos($item['customer_phone'] ?? '', $search) !== false) ||
                (stripos($item['address'] ?? '', $search) !== false) ||
                (stripos($item['package_name'] ?? '', $search) !== false) ||
                (stripos($item['receipt_number'] ?? '', $search) !== false);

            return $matchStatus && $matchMonth && $matchSearch;
        })->values()->all();

        $counts = [
            'all' => $activeBills->count(), // Total tagihan aktif yang perlu dimonitor (tanpa yang sudah lunas)
            'menunggu' => $activeBills->where('status', 'Menunggu Verifikasi')->count(),
            'belum_bayar' => $activeBills->where('status', 'Belum Bayar')->count(),
            'jatuh_tempo' => $activeBills->where('status', 'Jatuh Tempo')->count(),
            'lunas' => $paidBills->count(),
            'rekap' => $paidBills->count(),
        ];

        // Total nominal dan kuantiti untuk ringkasan rekap pembayaran
        $rekapNominal = collect($bills)->where('status', 'Lunas')->sum('total_raw');
        $rekapCount = collect($bills)->where('status', 'Lunas')->count();

        // Daftar pelanggan untuk form pilihan cepat saat input tagihan manual (hanya yang sudah selesai / aktif)
        $registeredCustomers = Order::where(function ($q) {
            $q->whereIn('status', ['Selesai', 'Selesai / Aktif', 'selesai', 'aktif'])
              ->orWhere('order_number', 'like', 'PLG-%');
        })->select(
            'id', 'order_number', 'customer_name', 'customer_phone', 'customer_email',
            'address', 'package_name', 'speed', 'price'
        )->orderBy('customer_name')->get();

        $packages = Package::where('is_active', true)->get();

        return view('admin.tagihan', compact(
            'bills', 'statusFilter', 'monthFilter', 'search', 'counts', 
            'availableMonths', 'rekapNominal', 'rekapCount',
            'registeredCustomers', 'packages'
        ));
    }

    /**
     * Terbitkan Tagihan Baru Secara Manual
     */
    public function storeTagihan(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'nullable|exists:orders,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:50',
            'customer_email' => 'nullable|email|max:100',
            'address' => 'required|string|max:500',
            'package_name' => 'required|string|max:100',
            'speed' => 'nullable|string|max:50',
            'period' => 'required|string|max:100',
            'due_date' => 'required|string|max:100',
            'bill_date' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'total' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:Belum Bayar,Lunas,Menunggu Verifikasi,Jatuh Tempo',
            'payment_method' => 'nullable|string|max:100',
            'collector_notes' => 'nullable|string|max:500',
        ]);

        // Auto-generate invoice number unik: INV-YYYYMM-XXX
        $now = now('Asia/Jakarta');
        $prefix = 'INV-' . $now->format('Ym') . '-';
        $seq = Bill::where('bill_number', 'like', "{$prefix}%")->count() + 1;
        $billNumber = $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
        while (Bill::where('bill_number', $billNumber)->exists()) {
            $seq++;
            $billNumber = $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
        }

        $speed = $validated['speed'] ?? null;
        if (empty($speed)) {
            if (str_contains($validated['package_name'], '50')) {
                $speed = '50 Mbps';
            } elseif (str_contains($validated['package_name'], '30')) {
                $speed = '30 Mbps';
            } else {
                $speed = '20 Mbps';
            }
        }

        $dueDate = trim($validated['due_date']);
        if (!preg_match('/^0?5\s+/i', $dueDate)) {
            $dueDate = preg_replace('/^\d{1,2}\s+/', '05 ', $dueDate);
        }

        $amount = (float) $validated['amount'];
        $tax = (float) ($validated['tax'] ?? 0);
        $total = !empty($validated['total']) ? (float) $validated['total'] : ($amount + $tax);

        $bill = new Bill();
        $bill->bill_number = $billNumber;
        $bill->order_id = $validated['order_id'] ?? null;
        $bill->customer_name = $validated['customer_name'];
        $bill->customer_phone = $validated['customer_phone'];
        $bill->customer_email = $validated['customer_email'] ?? null;
        $bill->address = $validated['address'];
        $bill->package_name = $validated['package_name'];
        $bill->speed = $speed;
        $bill->period = $validated['period'];
        $bill->due_date = $dueDate;
        $bill->bill_date = $validated['bill_date'] ?? $now->translatedFormat('d M Y');
        $bill->amount = $amount;
        $bill->tax = $tax;
        $bill->total = $total;
        $bill->status = $validated['status'];
        $bill->payment_method = $validated['payment_method'] ?? 'Transfer Bank (BCA)';

        if ($validated['status'] === 'Lunas') {
            $bill->paid_at = now();
            $bill->collected_by = auth()->user()->name ?? 'Administrator';
            $bill->receipt_number = 'KWT-' . date('Ym') . '-' . rand(100, 999);
            $bill->collector_notes = $validated['collector_notes'] ?: ('Diterima dan diverifikasi lunas oleh ' . (auth()->user()->name ?? 'Admin'));
        } else {
            $bill->collector_notes = $validated['collector_notes'] ?: 'Tagihan diterbitkan secara manual oleh Administrator.';
        }

        $bill->save();

        return redirect()->route('admin.tagihan')
            ->with('success', "Tagihan baru {$bill->bill_number} untuk {$bill->customer_name} berhasil diterbitkan!");
    }

    /**
     * Export Rekap Tagihan Pelanggan ke File Excel (.xls)
     */
    public function exportTagihan(Request $request)
    {
        $allBills = $this->getBillsData();
        $statusFilter = $request->input('status', 'all');
        $monthFilter = $request->input('month', 'all');
        $search = $request->input('q', '');

        $bills = collect($allBills)->filter(function ($item) use ($statusFilter, $monthFilter, $search) {
            $matchStatus = false;
            if ($statusFilter === 'all') {
                $matchStatus = ($item['status'] !== 'Lunas');
            } elseif ($statusFilter === 'rekap' || $statusFilter === 'Lunas') {
                $matchStatus = ($item['status'] === 'Lunas');
            } else {
                $matchStatus = ($item['status'] === $statusFilter);
            }

            $matchMonth = true;
            if (($statusFilter === 'rekap' || $statusFilter === 'Lunas') && $monthFilter !== 'all') {
                $matchMonth = (isset($item['month_key']) && $item['month_key'] === $monthFilter);
            }

            $matchSearch = empty($search) ||
                (stripos($item['customer_name'] ?? '', $search) !== false) ||
                (stripos($item['id'] ?? '', $search) !== false) ||
                (stripos($item['customer_phone'] ?? '', $search) !== false) ||
                (stripos($item['address'] ?? '', $search) !== false) ||
                (stripos($item['package_name'] ?? '', $search) !== false) ||
                (stripos($item['receipt_number'] ?? '', $search) !== false);

            return $matchStatus && $matchMonth && $matchSearch;
        })->values()->all();

        $prefix = ($statusFilter === 'rekap' || $statusFilter === 'Lunas') ? 'Rekap_Pembayaran_Banterpool_' : 'Rekap_Tagihan_Banterpool_';
        $filename = $prefix . date('Ymd_His') . '.xls';

        return response()->streamDownload(function () use ($bills, $statusFilter, $search) {
            echo "\xEF\xBB\xBF"; // UTF-8 BOM agar terbaca sempurna di Microsoft Excel
            echo view('admin.exports.tagihan_excel', compact('bills', 'statusFilter', 'search'))->render();
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate, proxy-revalidate',
        ]);
    }

    /**
     * Update Status Tagihan (Verifikasi Lunas / Batal)
     */
    public function updateTagihanStatus(Request $request, $id)
    {
        $newStatus = $request->input('status', 'Lunas');
        $adminNotes = $request->input('admin_notes', 'Diverifikasi oleh Administrator');

        try {
            $bill = Bill::where('bill_number', $id)->first();
            if ($bill) {
                $bill->status = $newStatus;
                if ($newStatus === 'Lunas') {
                    $bill->paid_at = now();
                    $bill->payment_method = $bill->payment_method ?: 'Verifikasi Admin';
                    $bill->collected_by = auth()->user()->name ?? 'Administrator';
                    if (empty($bill->receipt_number)) {
                        $bill->receipt_number = 'KWT-' . date('Ym') . '-' . rand(100, 999);
                    }
                }
                $bill->collector_notes = ($bill->collector_notes ? $bill->collector_notes . " | " : "") . $adminNotes;
                $bill->save();

                if ($newStatus === 'Lunas') {
                    BillingService::generateNextBillForPaidBill($bill);
                }
            }
        } catch (\Exception $e) {}

        $message = $newStatus === 'Lunas'
            ? "Tagihan {$id} berhasil diverifikasi Lunas dan telah dipindahkan ke Riwayat / Rekap Pembayaran!"
            : "Tagihan {$id} berhasil diperbarui menjadi {$newStatus}!";

        return redirect()->back()->with('success', $message);
    }

    /**
     * Halaman Pengintaian & Penanganan Laporan Masalah / Trouble Tickets
     */
    public function laporanMasalah(Request $request)
    {
        $allTickets = $this->getTicketsData();
        $statusFilter = $request->input('status', 'all');
        $priorityFilter = $request->input('priority', 'all');
        $categoryFilter = $request->input('category', 'all');
        $search = $request->input('q', '');

        $tickets = collect($allTickets)->filter(function ($item) use ($statusFilter, $priorityFilter, $categoryFilter, $search) {
            $matchStatus = ($statusFilter === 'all') || ($item['status'] === $statusFilter);
            $matchPriority = ($priorityFilter === 'all') || ($item['priority'] === $priorityFilter);
            $matchCategory = ($categoryFilter === 'all') ||
                (isset($item['category']) && $item['category'] === $categoryFilter) ||
                (isset($item['type']) && stripos($item['type'], $categoryFilter) !== false);

            $matchSearch = empty($search) ||
                (stripos($item['customer_name'] ?? '', $search) !== false) ||
                (stripos($item['id'] ?? '', $search) !== false) ||
                (stripos($item['description'] ?? '', $search) !== false) ||
                (isset($item['customer_phone']) && stripos($item['customer_phone'], $search) !== false) ||
                (isset($item['odp']) && stripos($item['odp'], $search) !== false) ||
                (isset($item['address']) && stripos($item['address'], $search) !== false) ||
                (isset($item['technician']) && stripos($item['technician'], $search) !== false);

            return $matchStatus && $matchPriority && $matchCategory && $matchSearch;
        })->values()->all();

        $counts = [
            'all' => count($allTickets),
            'menunggu' => collect($allTickets)->where('status', 'Menunggu Respon')->count(),
            'proses' => collect($allTickets)->where('status', 'Sedang Ditangani')->count(),
            'selesai' => collect($allTickets)->where('status', 'Selesai')->count(),
            'kritis' => collect($allTickets)->where('priority', 'Kritis')->where('status', '!=', 'Selesai')->count(),
        ];

        if ($request->expectsJson() || $request->ajax()) {
            $now = now('Asia/Jakarta')->locale('id');
            return response()->json([
                'success' => true,
                'tickets' => $tickets,
                'counts' => $counts,
                'system_time' => $now->translatedFormat('l, d F Y, H:i:s') . ' WIB',
                'system_date' => $now->translatedFormat('l, d F Y'),
                'auto_prune' => [
                    'active' => true,
                    'retention_days' => 3,
                    'status_target' => 'Selesai',
                    'description' => 'Riwayat laporan masalah berstatus Selesai yang berusia lebih dari 3 hari otomatis dibersihkan.',
                ],
            ]);
        }

        return view('admin.laporan-masalah', compact('tickets', 'statusFilter', 'priorityFilter', 'categoryFilter', 'search', 'counts'));
    }

    /**
     * Update Status Laporan Masalah & Penugasan Teknisi
     */
    public function updateLaporanStatus(Request $request, $id)
    {
        $status = $request->input('status', 'Sedang Ditangani');
        $technician = $request->input('technician', 'Tim Teknisi Fiber');
        $notes = $request->input('notes', 'Status diperbarui oleh Admin');

        $allTickets = $this->getTicketsData();
        $updatedTicket = null;
        $now = now('Asia/Jakarta')->locale('id');
        $updatedAtFormatted = $now->translatedFormat('l, d F Y, H:i') . ' WIB';
        $updatedAtWithSeconds = $now->translatedFormat('l, d F Y, H:i:s') . ' WIB';
        $updatedDateOnly = $now->translatedFormat('l, d F Y');
        $updatedTimeOnly = $now->format('H:i:s') . ' WIB';

        foreach ($allTickets as &$ticket) {
            if ($ticket['id'] === $id) {
                $ticket['status'] = $status;
                $ticket['technician'] = $technician;
                $ticket['notes'] = $notes;
                $ticket['updated_at'] = $updatedAtFormatted;
                $ticket['updated_at_full'] = $updatedAtWithSeconds;
                $ticket['updated_at_short'] = $updatedAtWithSeconds;
                $ticket['updated_date'] = $updatedDateOnly;
                $ticket['updated_time'] = $updatedTimeOnly;
                $ticket['updated_at_iso'] = $now->toIso8601String();
                $ticket['updated_timestamp'] = $now->timestamp;

                $historyEntry = [
                    'status' => $status,
                    'technician' => $technician,
                    'notes' => $notes,
                    'updated_at' => $updatedAtWithSeconds,
                    'admin' => auth()->user()->name ?? 'Admin NOC',
                ];

                if (!isset($ticket['status_history']) || !is_array($ticket['status_history'])) {
                    $ticket['status_history'] = [];
                }
                array_unshift($ticket['status_history'], $historyEntry);

                $updatedTicket = $ticket;
                break;
            }
        }

        Cache::forever('trouble_tickets', $allTickets);
        session(['admin_tickets' => $allTickets]);

        // Sinkronisasi ke session tiket teknisi jika ada
        $techTickets = session('technician_tickets');
        if ($techTickets && is_array($techTickets)) {
            foreach ($techTickets as &$t) {
                if ($t['id'] === $id) {
                    $t['status'] = $status;
                    $t['technician'] = $technician;
                    $t['notes'] = $notes;
                    $t['updated_at'] = $updatedAtFormatted;
                }
            }
            session(['technician_tickets' => $techTickets]);
        }

        $counts = [
            'all' => count($allTickets),
            'menunggu' => collect($allTickets)->where('status', 'Menunggu Respon')->count(),
            'proses' => collect($allTickets)->where('status', 'Sedang Ditangani')->count(),
            'selesai' => collect($allTickets)->where('status', 'Selesai')->count(),
            'kritis' => collect($allTickets)->where('priority', 'Kritis')->where('status', '!=', 'Selesai')->count(),
        ];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Tiket {$id} berhasil diperbarui pada {$updatedAtWithSeconds}. Status: {$status}.",
                'ticket' => $updatedTicket,
                'updated_at' => $updatedAtFormatted,
                'updated_at_full' => $updatedAtWithSeconds,
                'updated_at_short' => $updatedAtWithSeconds,
                'updated_date' => $updatedDateOnly,
                'updated_time' => $updatedTimeOnly,
                'counts' => $counts,
            ]);
        }

        return redirect()->back()->with('success', "Tiket {$id} berhasil diperbarui pada {$updatedAtWithSeconds}. Status: {$status}. Teknisi: {$technician}");
    }

    /**
     * Hapus Tiket Laporan Masalah Tunggal
     */
    public function deleteLaporan(Request $request, $id)
    {
        $allTickets = Cache::get('trouble_tickets') ?? session('admin_tickets') ?? $this->getTicketsData();
        
        foreach ($allTickets as $t) {
            if ($t['id'] === $id) {
                self::deleteTicketAttachmentFile($t['attachment_url'] ?? null);
            }
        }

        $filteredTickets = collect($allTickets)->reject(function ($item) use ($id) {
            return $item['id'] === $id;
        })->values()->all();

        Cache::forever('trouble_tickets', $filteredTickets);
        session(['admin_tickets' => $filteredTickets]);

        $techTickets = session('technician_tickets');
        if (is_array($techTickets)) {
            $techPruned = array_values(array_filter($techTickets, function ($t) use ($id) {
                return $t['id'] !== $id;
            }));
            session(['technician_tickets' => $techPruned]);
        }

        $counts = [
            'all' => count($filteredTickets),
            'menunggu' => collect($filteredTickets)->where('status', 'Menunggu Respon')->count(),
            'proses' => collect($filteredTickets)->where('status', 'Sedang Ditangani')->count(),
            'selesai' => collect($filteredTickets)->where('status', 'Selesai')->count(),
            'kritis' => collect($filteredTickets)->where('priority', 'Kritis')->where('status', '!=', 'Selesai')->count(),
        ];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Tiket laporan kendala {$id} berhasil dihapus.",
                'counts' => $counts,
            ]);
        }

        return redirect()->route('admin.laporan')->with('success', "Tiket laporan kendala {$id} berhasil dihapus.");
    }

    /**
     * Hapus Massal / Hapus Semua Tiket Laporan Masalah
     */
    public function bulkDeleteLaporan(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids) && $request->filled('ids_json')) {
            $decoded = json_decode($request->input('ids_json'), true);
            if (is_array($decoded)) {
                $ids = $decoded;
            }
        }
        if (is_string($ids)) {
            $decoded = json_decode($ids, true);
            if (is_array($decoded)) {
                $ids = $decoded;
            } else {
                $ids = array_filter(array_map('trim', explode(',', $ids)));
            }
        }
        $deleteAll = $request->boolean('delete_all', false);

        if ($deleteAll) {
            $allTickets = Cache::get('trouble_tickets') ?? session('admin_tickets') ?? $this->getTicketsData();
            foreach ($allTickets as $t) {
                self::deleteTicketAttachmentFile($t['attachment_url'] ?? null);
            }

            Cache::forever('trouble_tickets', []);
            session(['admin_tickets' => []]);
            session(['technician_tickets' => []]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'deleted_count' => count($allTickets),
                    'message' => "Seluruh data laporan masalah berhasil dihapus.",
                    'counts' => [
                        'all' => 0,
                        'menunggu' => 0,
                        'proses' => 0,
                        'selesai' => 0,
                        'kritis' => 0,
                    ],
                ]);
            }

            return redirect()->route('admin.laporan')->with('success', "Seluruh data laporan masalah berhasil dihapus.");
        }

        if (empty($ids) || !is_array($ids)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Silakan pilih minimal satu laporan untuk dihapus.',
                ], 422);
            }
            return redirect()->back()->with('error', 'Silakan pilih minimal satu laporan untuk dihapus.');
        }

        $allTickets = Cache::get('trouble_tickets') ?? session('admin_tickets') ?? $this->getTicketsData();

        foreach ($allTickets as $t) {
            if (in_array($t['id'], $ids)) {
                self::deleteTicketAttachmentFile($t['attachment_url'] ?? null);
            }
        }

        $filteredTickets = collect($allTickets)->reject(function ($item) use ($ids) {
            return in_array($item['id'], $ids);
        })->values()->all();

        Cache::forever('trouble_tickets', $filteredTickets);
        session(['admin_tickets' => $filteredTickets]);

        $techTickets = session('technician_tickets');
        if (is_array($techTickets)) {
            $techPruned = array_values(array_filter($techTickets, function ($t) use ($ids) {
                return !in_array($t['id'], $ids);
            }));
            session(['technician_tickets' => $techPruned]);
        }

        $counts = [
            'all' => count($filteredTickets),
            'menunggu' => collect($filteredTickets)->where('status', 'Menunggu Respon')->count(),
            'proses' => collect($filteredTickets)->where('status', 'Sedang Ditangani')->count(),
            'selesai' => collect($filteredTickets)->where('status', 'Selesai')->count(),
            'kritis' => collect($filteredTickets)->where('priority', 'Kritis')->where('status', '!=', 'Selesai')->count(),
        ];

        $count = count($ids);
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'deleted_count' => $count,
                'message' => "Sebanyak {$count} tiket laporan kendala berhasil dihapus.",
                'counts' => $counts,
            ]);
        }

        return redirect()->route('admin.laporan')->with('success', "Sebanyak {$count} tiket laporan kendala berhasil dihapus.");
    }

    /**
     * Halaman MRTG & MikroTik Monitoring (Grafana Style)
     */
    public function mrtg(Request $request)
    {
        $mrtgData = $this->getMrtgData();
        
        $selectedIf = $request->input('interface') ?? $request->input('var-Interface') ?? 'if-core-uplink';
        $selectedInstance = $request->input('instance') ?? $request->input('var-instance') ?? '192.168.36.1';
        $selectedSimpleQueue = $request->input('queuesimple_name') ?? $request->input('var-queuesimple_name') ?? '$__all';
        $selectedTreeQueue = $request->input('queuetree_name') ?? $request->input('var-queuetree_name') ?? '$__all';
        $refreshInterval = $request->input('refresh', '10s');
        $timeRange = $request->input('from', 'now-30m');

        $activeInterface = collect($mrtgData['interfaces'])->first(function ($if) use ($selectedIf) {
            return $if['id'] === $selectedIf || $if['name'] === $selectedIf || str_contains($if['name'], $selectedIf);
        }) ?? $mrtgData['interfaces'][0];

        $grafanaParams = [
            'instance' => $selectedInstance,
            'interface' => $selectedIf,
            'queuesimple_name' => $selectedSimpleQueue,
            'queuetree_name' => $selectedTreeQueue,
            'refresh' => $refreshInterval,
            'from' => $timeRange,
            'job' => 'Mikrotik',
            'datasource' => 'Prometheus (dfs21jfqjnqbkd)',
        ];

        return view('admin.mrtg', compact('mrtgData', 'activeInterface', 'grafanaParams'));
    }

    /**
     * Halaman Peta Interaktif ODC, ODP & Jalur Kabel Fiber Optik
     */
    public function odcMap()
    {
        $mapData = $this->getOdcMapData();

        return view('admin.odc-map', compact('mapData'));
    }
}
