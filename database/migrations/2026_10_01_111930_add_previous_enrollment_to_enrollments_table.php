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
            $table->foreignId('previous_enrollment_id')->nullable()->after('level_id')->constrained('enrollments')->nullOnDelete();
            $table->boolean('prerequisite_waived')->default(false)->after('previous_enrollment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('previous_enrollment_id');
            $table->dropColumn('prerequisite_waived');
        });
    }
};
