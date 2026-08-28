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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway')->nullable()->after('payment_method');
            $table->string('gateway_reference')->nullable()->unique()->after('gateway');
            $table->string('gateway_transaction_id')->nullable()->after('gateway_reference');
            $table->string('gateway_status')->nullable()->after('gateway_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['gateway', 'gateway_reference', 'gateway_transaction_id', 'gateway_status']);
        });
    }
};
