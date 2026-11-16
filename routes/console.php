<?php

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('tickets:prune {--days=3 : Jumlah batas usia hari retensi riwayat laporan masalah}', function () {
    $days = (int) ($this->option('days') ?: 3);
    $beforeTickets = Cache::get('trouble_tickets', []);
    $beforeCount = is_array($beforeTickets) ? count($beforeTickets) : 0;

    $pruned = AdminController::pruneOldTickets(null, $days);
    $afterCount = count($pruned);
    $deletedCount = max(0, $beforeCount - $afterCount);

    $this->info("Auto-hapus riwayat laporan masalah selesai.");
    $this->line("Batas retensi : {$days} hari");
    $this->line("Kriteria      : Status Selesai");
    $this->line("Total sebelum : {$beforeCount} tiket");
    $this->line("Total setelah : {$afterCount} tiket");
    $this->line("Tiket dihapus : {$deletedCount} tiket");
})->purpose('Auto-hapus riwayat laporan masalah pelanggan berstatus Selesai yang berusia lebih dari 3 hari')->daily();

// Penjadwalan otomatis berjalan setiap hari
Schedule::command('tickets:prune')->daily();

Artisan::command('billing:hapus-semua-rekap {--delete : Hapus permanen record tagihan lunas}', function () {
    $lunasBills = \App\Models\Bill::where('status', 'Lunas')->get();
    $count = $lunasBills->count();

    if ($count === 0) {
        $this->info("Tidak ada data rekap tagihan (Lunas) yang ditemukan.");
        return 0;
    }

    $isDeletePermanent = (bool) $this->option('delete');
    $orderIds = $lunasBills->pluck('order_id')->filter()->unique()->all();

    if ($isDeletePermanent) {
        \App\Models\Bill::where('status', 'Lunas')->delete();
        $this->info("Berhasil menghapus secara permanen {$count} data tagihan rekap (Lunas).");
    } else {
        \App\Models\Bill::where('status', 'Lunas')->whereNotNull('order_id')->update([
            'status' => 'Belum Bayar',
            'paid_at' => null,
            'receipt_number' => null,
            'collected_by' => null,
            'collector_notes' => null,
            'payment_method' => null,
        ]);

        \App\Models\Bill::where('status', 'Lunas')->whereNull('order_id')->delete();

        if (!empty($orderIds)) {
            \App\Models\Order::whereIn('id', $orderIds)->update([
                'payment_status' => 'Menunggu Pembayaran',
            ]);
        }

        $this->info("Berhasil membersihkan {$count} data rekap pembayaran dan mengembalikannya ke status Belum Bayar.");
    }

    return 0;
})->purpose('Hapus semua data rekap pembayaran dan reset status tagihan lunas menjadi belum bayar');

Artisan::command('tagihan:hapus-rekap', function () {
    $this->call('billing:hapus-semua-rekap');
})->purpose('Alias untuk command billing:hapus-semua-rekap');
