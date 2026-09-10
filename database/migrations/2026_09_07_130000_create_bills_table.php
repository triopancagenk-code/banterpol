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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_number')->unique(); // e.g. INV-202609-001
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->text('address');
            $table->string('package_name');
            $table->string('speed')->nullable(); // e.g. 20 Mbps
            $table->string('period'); // e.g. '01 Sep 2026 – 01 Okt 2026'
            $table->string('due_date'); // e.g. '05 Sep 2026'
            $table->string('bill_date')->nullable(); // e.g. '01 Sep 2026'
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('status')->default('Belum Bayar'); // Belum Bayar, Lunas, Jatuh Tempo, Menunggu Verifikasi
            $table->string('payment_method')->nullable(); // Tunai (Kolektor), Transfer Bank (BCA), QRIS, etc.
            $table->timestamp('paid_at')->nullable();
            $table->string('collected_by')->nullable(); // Nama kolektor penagih
            $table->text('collector_notes')->nullable();
            $table->string('receipt_number')->nullable(); // e.g. KWT-202609-001
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
