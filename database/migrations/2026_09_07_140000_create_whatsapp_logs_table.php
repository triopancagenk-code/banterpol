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
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->string('message_type')->default('broadcast'); // broadcast, billing, maintenance, promo, custom, single
            $table->text('message');
            $table->string('status')->default('sent'); // sent, queued, failed
            $table->string('batch_id')->nullable();
            $table->string('target_filter')->nullable(); // all, unpaid, area, package, manual
            $table->string('sent_by')->default('Admin NOC');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};
