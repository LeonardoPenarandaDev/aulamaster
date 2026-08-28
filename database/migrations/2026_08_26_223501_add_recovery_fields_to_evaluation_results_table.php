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
        Schema::table('evaluation_results', function (Blueprint $table) {
            $table->decimal('cost', 10, 2)->default(0)->after('is_recovery');
            $table->enum('payment_status', ['no_requerido', 'pagado'])->default('no_requerido')->after('cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluation_results', function (Blueprint $table) {
            $table->dropColumn(['cost', 'payment_status']);
        });
    }
};
