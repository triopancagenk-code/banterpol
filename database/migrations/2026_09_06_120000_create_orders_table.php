<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email');
            $table->text('address');
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            
            // Paket & Biaya
            $table->string('package_name');
            $table->string('speed')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('installation_fee', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Jadwal Pemasangan
            $table->date('installation_date')->nullable();
            $table->string('installation_time')->nullable(); // pagi / siang

            // Pembayaran
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->default('Menunggu Pembayaran'); // Lunas, Menunggu Pembayaran, Gagal

            // Status Pesanan
            // Menunggu Konfirmasi, Jadwal Teknisi, Sedang Dipasang, Selesai, Dibatalkan
            $table->string('status')->default('Menunggu Konfirmasi');

            // Penugasan NOC
            $table->string('technician')->nullable();
            $table->string('assigned_odp')->nullable();
            $table->text('admin_notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
