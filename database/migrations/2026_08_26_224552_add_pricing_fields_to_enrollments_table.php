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
        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('promotion_id')->nullable()->after('level_id')->constrained()->nullOnDelete();
            $table->decimal('promotion_discount', 12, 2)->default(0)->after('promotion_id');
            $table->foreignId('referral_id')->nullable()->after('promotion_discount')->constrained()->nullOnDelete();
            $table->decimal('referral_discount', 12, 2)->default(0)->after('referral_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promotion_id');
            $table->dropConstrainedForeignId('referral_id');
            $table->dropColumn(['promotion_discount', 'referral_discount']);
        });
    }
};
