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
            if (!Schema::hasColumn('daily_reports', 'service_code')) {
                $table->string('service_code', 50)->nullable()->after('job_type');
            }
            if (!Schema::hasColumn('daily_reports', 'service_unit')) {
                $table->string('service_unit', 50)->nullable()->after('service_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            if (Schema::hasColumn('daily_reports', 'service_code')) {
                $table->dropColumn('service_code');
            }
            if (Schema::hasColumn('daily_reports', 'service_unit')) {
                $table->dropColumn('service_unit');
            }
        });
    }
};

