<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'pppoe')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('pppoe')->nullable()->after('customer_name')->index();
            });
        }

        // Generate and populate pppoe for existing orders
        $orders = DB::table('orders')->select('id', 'customer_name', 'order_number')->get();
        $usedUsernames = [];

        foreach ($orders as $order) {
            $name = trim((string) $order->customer_name);
            $clean = preg_replace('/\s*[\(\/\-].*$/', '', $name);
            $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $clean));

            if (empty($username)) {
                $username = !empty($order->order_number) 
                    ? strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $order->order_number)) 
                    : 'user' . $order->id;
            }

            $finalUsername = $username;
            $counter = 1;
            while (isset($usedUsernames[$finalUsername])) {
                $counter++;
                $finalUsername = $username . $counter;
            }

            $usedUsernames[$finalUsername] = true;

            DB::table('orders')->where('id', $order->id)->update([
                'pppoe' => $finalUsername
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('orders', 'pppoe')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('pppoe');
            });
        }
    }
};
