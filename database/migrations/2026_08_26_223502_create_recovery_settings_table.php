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
        Schema::create('recovery_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('free_attempts')->default(1);
            $table->unsignedTinyInteger('max_paid_attempts')->default(2);
            $table->decimal('recovery_price', 10, 2)->default(50000);
            $table->unsignedSmallInteger('recovery_period_days')->default(15);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recovery_settings');
    }
};
