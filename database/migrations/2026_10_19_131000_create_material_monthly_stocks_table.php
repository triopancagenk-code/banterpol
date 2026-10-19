<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('material_monthly_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->unsignedTinyInteger('month'); // 1 - 12
            $table->string('month_name', 50);     // e.g. 'Januari', 'Februari', 'Oktober'
            $table->unsignedSmallInteger('year')->default(2026);
            $table->decimal('initial_stock', 12, 2)->default(0);
            $table->decimal('total_in', 12, 2)->default(0);
            $table->decimal('total_out', 12, 2)->default(0);
            $table->decimal('final_stock', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['material_id', 'month', 'year'], 'mat_monthly_unique');
            $table->index(['month', 'year']);
        });

        // Backfill existing materials if any
        $monthMap = [
            'januari' => 1, 'februari' => 2, 'pebruari' => 2, 'maret' => 3, 'april' => 4,
            'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8, 'september' => 9,
            'oktober' => 10, 'november' => 11, 'nopember' => 11, 'desember' => 12,
        ];

        try {
            $existingMaterials = DB::table('materials')->get();
            $now = now();
            foreach ($existingMaterials as $m) {
                $rawMonth = strtolower(trim((string)($m->month ?? 'oktober')));
                $monthNum = 10;
                $monthName = 'Oktober';
                foreach ($monthMap as $nameKey => $num) {
                    if (str_contains($rawMonth, $nameKey)) {
                        $monthNum = $num;
                        $monthName = ucfirst($nameKey);
                        break;
                    }
                }
                $year = !empty($m->year) ? (int)$m->year : 2026;

                DB::table('material_monthly_stocks')->insertOrIgnore([
                    'material_id' => $m->id,
                    'month' => $monthNum,
                    'month_name' => $monthName,
                    'year' => $year,
                    'initial_stock' => (float)($m->initial_stock ?? 0),
                    'total_in' => (float)($m->total_in ?? 0),
                    'total_out' => (float)($m->total_out ?? 0),
                    'final_stock' => (float)($m->stock ?? 0),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        } catch (\Throwable $e) {
            // Ignore during dry runs
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_monthly_stocks');
    }
};
