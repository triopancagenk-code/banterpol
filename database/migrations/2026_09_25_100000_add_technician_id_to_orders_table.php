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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'technician_id')) {
                $table->foreignId('technician_id')->nullable()->after('technician')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable()->after('technician_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'technician_id')) {
                $table->dropForeign(['technician_id']);
                $table->dropColumn('technician_id');
            }
            if (Schema::hasColumn('orders', 'assigned_at')) {
                $table->dropColumn('assigned_at');
            }
        });
    }
};
