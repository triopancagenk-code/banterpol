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
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->string('role', 30)->default('customer')->change();
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'ont_sn')) {
                $table->string('ont_sn')->nullable()->after('technician');
            }
            if (!Schema::hasColumn('orders', 'opm_dbm')) {
                $table->string('opm_dbm')->nullable()->after('ont_sn');
            }
            if (!Schema::hasColumn('orders', 'technician_notes')) {
                $table->text('technician_notes')->nullable()->after('opm_dbm');
            }
            if (!Schema::hasColumn('orders', 'installed_at')) {
                $table->timestamp('installed_at')->nullable()->after('technician_notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'installed_at')) {
                $table->dropColumn(['ont_sn', 'opm_dbm', 'technician_notes', 'installed_at']);
            }
        });
    }
};
