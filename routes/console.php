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
