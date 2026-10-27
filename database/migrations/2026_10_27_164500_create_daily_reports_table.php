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
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');
            $table->string('technician_name');
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('partner_company')->default('PT NetMedia Solusi Nusantara');
            $table->string('job_type')->default('Pasang Baru (PSB / Installation)');
            
            // Customer Info
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->text('customer_address')->nullable();
            $table->string('pppoe_username')->nullable();
            $table->string('odp_port')->nullable();
            
            // Technical Parameters
            $table->string('rx_power')->nullable();
            $table->string('ont_sn')->nullable();
            
            // Material Usage & Costing
            $table->json('materials_json')->nullable();
            $table->text('additional_materials')->nullable();
            $table->decimal('material_total_cost', 15, 2)->default(0);
            
            // Cash & Field Bookkeeping
            $table->string('transaction_type')->default('Biaya Pasang Baru (PSB)');
            $table->decimal('cash_amount', 15, 2)->default(0);
            $table->string('payment_method')->default('Tunai (Cash)');
            $table->string('cash_deposit_status')->default('Belum Disetor'); // Belum Disetor, Sudah Disetor, Non-Tunai
            
            // Link to Order if applicable
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            
            // Sync Status
            $table->boolean('synced_to_sheets')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('report_date');
            $table->index('job_type');
            $table->index('cash_deposit_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
