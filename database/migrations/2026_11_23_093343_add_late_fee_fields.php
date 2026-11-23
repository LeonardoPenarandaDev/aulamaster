<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mora por pago tardío: si la mensualidad no se paga hasta el día límite
 * (5 por defecto), al vencerse se le suma el porcentaje configurado. El
 * valor de la mora queda aparte en el pago para verlo en el detalle.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('institution_settings', function (Blueprint $table) {
            $table->decimal('late_fee_percentage', 5, 2)->default(0)->after('payment_due_day');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('late_fee_amount', 12, 2)->default(0)->after('discount_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('late_fee_amount');
        });

        Schema::table('institution_settings', function (Blueprint $table) {
            $table->dropColumn('late_fee_percentage');
        });
    }
};
