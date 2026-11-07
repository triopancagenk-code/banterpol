<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Order;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'virtual_account')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('virtual_account', 50)->nullable()->after('customer_phone')->index();
            });
        }

        // Generate and populate virtual_account for existing orders that don't have one
        $orders = DB::table('orders')->select('id', 'customer_phone', 'virtual_account')->get();
        foreach ($orders as $order) {
            if (empty($order->virtual_account) || $order->virtual_account === '-') {
                $cleanPhone = preg_replace('/\D/', '', (string) $order->customer_phone);
                if (str_starts_with($cleanPhone, '62')) {
                    $cleanPhone = substr($cleanPhone, 2);
                } elseif (str_starts_with($cleanPhone, '0')) {
                    $cleanPhone = substr($cleanPhone, 1);
                }

                if (!empty($cleanPhone)) {
                    $va = '7701' . $cleanPhone;
                } else {
                    $va = '7701' . str_pad((string) $order->id, 10, '0', STR_PAD_LEFT);
                }

                DB::table('orders')->where('id', $order->id)->update([
                    'virtual_account' => $va
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('orders', 'virtual_account')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('virtual_account');
            });
        }
    }
};

