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
        if (Schema::hasColumn('orders', 'virtual_account')) {
            // Update all virtual_account records that start with 7701 or are empty to 1699101
            $orders = DB::table('orders')->select('id', 'customer_phone', 'virtual_account')->get();
            foreach ($orders as $order) {
                $cleanPhone = preg_replace('/\D/', '', (string) $order->customer_phone);
                if (str_starts_with($cleanPhone, '62')) {
                    $cleanPhone = substr($cleanPhone, 2);
                } elseif (str_starts_with($cleanPhone, '0')) {
                    $cleanPhone = substr($cleanPhone, 1);
                }

                $currentVa = (string) $order->virtual_account;
                if (str_starts_with($currentVa, '7701')) {
                    $newVa = '1699101' . substr($currentVa, 4);
                } elseif (empty($currentVa) || $currentVa === '-') {
                    $newVa = Order::generateVirtualAccount($order->customer_phone, $order->id);
                } else {
                    $newVa = $currentVa;
                }

                DB::table('orders')->where('id', $order->id)->update([
                    'virtual_account' => $newVa,
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
            $orders = DB::table('orders')->select('id', 'virtual_account')->get();
            foreach ($orders as $order) {
                $currentVa = (string) $order->virtual_account;
                if (str_starts_with($currentVa, '1699101')) {
                    $oldVa = '7701' . substr($currentVa, 7);
                    DB::table('orders')->where('id', $order->id)->update([
                        'virtual_account' => $oldVa,
                    ]);
                }
            }
        }
    }
};

