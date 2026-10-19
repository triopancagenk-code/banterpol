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
        Schema::table('materials', function (Blueprint $table) {
            if (!Schema::hasColumn('materials', 'initial_stock')) {
                $table->decimal('initial_stock', 12, 2)->default(0)->after('unit');
            }
            if (!Schema::hasColumn('materials', 'total_in')) {
                $table->decimal('total_in', 12, 2)->default(0)->after('initial_stock');
            }
            if (!Schema::hasColumn('materials', 'total_out')) {
                $table->decimal('total_out', 12, 2)->default(0)->after('total_in');
            }
            $table->decimal('stock', 12, 2)->default(0)->change();
        });

        Schema::table('material_mutations', function (Blueprint $table) {
            $table->decimal('amount', 12, 2)->default(0)->change();
            $table->decimal('stock_before', 12, 2)->default(0)->change();
            $table->decimal('stock_after', 12, 2)->default(0)->change();
            if (!Schema::hasColumn('material_mutations', 'transaction_date')) {
                $table->date('transaction_date')->nullable()->after('type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_mutations', function (Blueprint $table) {
            if (Schema::hasColumn('material_mutations', 'transaction_date')) {
                $table->dropColumn('transaction_date');
            }
            $table->integer('amount')->change();
            $table->integer('stock_before')->change();
            $table->integer('stock_after')->change();
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->integer('stock')->change();
            if (Schema::hasColumn('materials', 'total_out')) {
                $table->dropColumn('total_out');
            }
            if (Schema::hasColumn('materials', 'total_in')) {
                $table->dropColumn('total_in');
            }
            if (Schema::hasColumn('materials', 'initial_stock')) {
                $table->dropColumn('initial_stock');
            }
        });
    }
};
