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
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->string('customer_nik', 30)->nullable()->after('customer_name');
            $table->string('service_plan', 100)->nullable()->after('pppoe_username');
            $table->decimal('service_price', 15, 2)->default(0)->after('service_plan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropColumn(['customer_nik', 'service_plan', 'service_price']);
        });
    }
};

