<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\WhatsappGatewayController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Collector\CollectorController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Technician\TechnicianController;
use Illuminate\Support\Facades\Route;

// Halaman Utama (Publik)
Route::get('/', [HomeController::class, 'index'])->name('home');

// Autentikasi Google (Publik / Guest)
Route::get('/auth-google-redirect', [AuthController::class, 'google_redirect'])->name('auth.google-redirect');
Route::get('/auth-google-callback', [AuthController::class, 'google_callback'])->name('auth.google-callback');

// Fitur Layanan & Pelanggan Banterpool (Wajib Login)
Route::middleware('auth')->group(function () {

    Route::get('/tentang-kami', [HomeController::class, 'tentangKami'])->name('tentang-kami');
    Route::get('/paket', [HomeController::class, 'paket'])->name('paket');
    Route::get('/tagihan', [HomeController::class, 'tagihan'])->name('tagihan');
    Route::get('/tagihan/pembayaran', [HomeController::class, 'paymentTagihan'])->name('tagihan.payment');
    Route::post('/tagihan/pembayaran/konfirmasi', [HomeController::class, 'confirmPaymentTagihan'])->name('tagihan.payment.confirm');
    Route::get('/checkout', [HomeController::class, 'checkout'])->name('checkout');
    Route::get('/payment', [HomeController::class, 'payment'])->name('payment');
    Route::get('/payment/status', [HomeController::class, 'paymentStatus'])->name('payment.status');
    Route::get('/order/detail', [HomeController::class, 'orderDetail'])->name('order.detail');

    // Halaman Pengaduan Pelanggan (Laporan Masalah)
    Route::get('/laporan-masalah', [HomeController::class, 'laporanMasalah'])->name('laporan.index');
    Route::post('/laporan-masalah', [HomeController::class, 'storeLaporanMasalah'])->name('laporan.store');
});

// ==========================================
// RUTE ADMIN PANEL (NOC & OPERATION BANTERPOOL)
// ==========================================
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/pesanan', [AdminController::class, 'pesanan'])->name('pesanan');
    Route::post('/pesanan', [AdminController::class, 'storePesanan'])->name('pesanan.store');
    Route::get('/pesanan/export', [AdminController::class, 'exportPesanan'])->name('pesanan.export');
    Route::get('/pesanan/{id}/formulir', [AdminController::class, 'formulirPesanan'])->name('pesanan.formulir');
    Route::post('/pesanan/{id}/status', [AdminController::class, 'updatePesananStatus'])->name('pesanan.status');
    Route::post('/pesanan/bulk-delete', [AdminController::class, 'bulkDeletePesanan'])->name('pesanan.bulk-delete');
    Route::delete('/pesanan/{id}', [AdminController::class, 'deletePesanan'])->name('pesanan.delete');

    // Data Pelanggan Terdaftar (KTP, Lahir, HP, Email, Layanan & Harga, Alamat)
    Route::get('/pelanggan', [AdminController::class, 'pelanggan'])->name('pelanggan');
    Route::get('/pelanggan/export', [AdminController::class, 'exportPelanggan'])->name('pelanggan.export');
    Route::post('/pelanggan/import', [AdminController::class, 'importPelanggan'])->name('pelanggan.import');
    Route::get('/pelanggan/template', [AdminController::class, 'downloadTemplatePelanggan'])->name('pelanggan.template');
    Route::get('/pelanggan/{id}/formulir', [AdminController::class, 'formulirPesanan'])->name('pelanggan.formulir');
    Route::post('/pelanggan', [AdminController::class, 'storePelanggan'])->name('pelanggan.store');
    Route::post('/pelanggan/bulk-delete', [AdminController::class, 'bulkDeletePelanggan'])->name('pelanggan.bulk-delete');
    Route::put('/pelanggan/{id}', [AdminController::class, 'updatePelanggan'])->name('pelanggan.update');
    Route::delete('/pelanggan/{id}', [AdminController::class, 'deletePelanggan'])->name('pelanggan.delete');

    Route::get('/tagihan', [AdminController::class, 'tagihan'])->name('tagihan');
    Route::post('/tagihan', [AdminController::class, 'storeTagihan'])->name('tagihan.store');
    Route::get('/tagihan/export', [AdminController::class, 'exportTagihan'])->name('tagihan.export');
    Route::post('/tagihan/{id}/status', [AdminController::class, 'updateTagihanStatus'])->name('tagihan.status');
    Route::get('/laporan-masalah', [AdminController::class, 'laporanMasalah'])->name('laporan');
    Route::post('/laporan-masalah/bulk-delete', [AdminController::class, 'bulkDeleteLaporan'])->name('laporan.bulk-delete');
    Route::post('/laporan-masalah/{id}/status', [AdminController::class, 'updateLaporanStatus'])->name('laporan.status');
    Route::delete('/laporan-masalah/{id}', [AdminController::class, 'deleteLaporan'])->name('laporan.delete');
    Route::get('/mrtg', [AdminController::class, 'mrtg'])->name('mrtg');
    Route::get('/odc-map', [AdminController::class, 'odcMap'])->name('odc-map');

    // WhatsApp Gateway
    Route::get('/whatsapp-gateway', [WhatsappGatewayController::class, 'index'])->name('whatsapp');
    Route::post('/whatsapp-gateway/broadcast', [WhatsappGatewayController::class, 'broadcast'])->name('whatsapp.broadcast');
    Route::post('/whatsapp-gateway/send-single', [WhatsappGatewayController::class, 'sendSingle'])->name('whatsapp.send-single');
    Route::post('/whatsapp-gateway/settings', [WhatsappGatewayController::class, 'saveSettings'])->name('whatsapp.settings');
    Route::delete('/whatsapp-gateway/logs/{id}', [WhatsappGatewayController::class, 'deleteLog'])->name('whatsapp.logs.delete');
    Route::post('/whatsapp-gateway/logs/clear', [WhatsappGatewayController::class, 'clearLogs'])->name('whatsapp.logs.clear');
    Route::get('/whatsapp-gateway/logs/export', [WhatsappGatewayController::class, 'exportLogs'])->name('whatsapp.logs.export');
});

// ==========================================
// RUTE PORTAL TEKNISI LAPANGAN
// ==========================================
Route::prefix('teknisi')->name('teknisi.')->middleware(['auth', 'technician'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('teknisi.dashboard');
    })->name('index');
    Route::get('/dashboard', [TechnicianController::class, 'dashboard'])->name('dashboard');
    Route::get('/pemasangan', [TechnicianController::class, 'pemasangan'])->name('pemasangan');
    Route::post('/pemasangan/{id}/status', [TechnicianController::class, 'updatePemasanganStatus'])->name('pemasangan.status');
    Route::get('/gangguan', [TechnicianController::class, 'gangguan'])->name('gangguan');
    Route::post('/gangguan/{id}/status', [TechnicianController::class, 'updateGangguanStatus'])->name('gangguan.status');
});

// ==========================================
// RUTE PORTAL KOLEKTOR LAPANGAN
// ==========================================
Route::prefix('kolektor')->name('kolektor.')->middleware(['auth', 'collector'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('kolektor.dashboard');
    })->name('index');
    Route::get('/dashboard', [CollectorController::class, 'dashboard'])->name('dashboard');
    Route::get('/tagihan', [CollectorController::class, 'tagihan'])->name('tagihan');
    Route::post('/tagihan/bayar-tunai', [CollectorController::class, 'bayarTunai'])->name('tagihan.bayar-tunai');
    Route::post('/tagihan/input-manual', [CollectorController::class, 'inputManualTagihan'])->name('tagihan.input-manual');
    Route::get('/tagihan/{id}/kuitansi', [CollectorController::class, 'kuitansi'])->name('tagihan.kuitansi');
    Route::get('/pemasangan', [CollectorController::class, 'pemasangan'])->name('pemasangan');
    Route::get('/gangguan', [CollectorController::class, 'gangguan'])->name('gangguan');
});

// Rute Dashboard (Redirect setelah login)
Route::get('/dashboard', function () {
    if (auth()->user()->role === 'technician' || auth()->user()->role === 'teknisi') {
        return redirect()->route('teknisi.dashboard');
    }
    if (auth()->user()->role === 'collector' || auth()->user()->role === 'kolektor') {
        return redirect()->route('kolektor.dashboard');
    }
    if (auth()->user()->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('home');
})->middleware(['auth', 'verified'])->name('dashboard');

// Rute Pengaturan Profil (Laravel Breeze)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
