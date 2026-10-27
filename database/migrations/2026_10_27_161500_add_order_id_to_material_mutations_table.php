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
        Schema::table('material_mutations', function (Blueprint $table) {
            if (!Schema::hasColumn('material_mutations', 'order_id')) {
                $table->foreignId('order_id')->nullable()->after('user_id')->constrained('orders')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_mutations', function (Blueprint $table) {
            if (Schema::hasColumn('material_mutations', 'order_id')) {
                $table->dropForeign(['order_id']);
                $table->dropColumn('order_id');
            }
        });
    }
};
