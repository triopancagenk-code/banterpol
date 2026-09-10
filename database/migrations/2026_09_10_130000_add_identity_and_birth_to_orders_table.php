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
            $table->string('id_card_number', 30)->nullable()->after('customer_email');
            $table->string('birth_place', 100)->nullable()->after('id_card_number');
            $table->date('birth_date')->nullable()->after('birth_place');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['id_card_number', 'birth_place', 'birth_date']);
        });
    }
};
