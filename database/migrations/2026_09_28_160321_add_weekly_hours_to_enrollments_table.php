<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Intensidad horaria semanal contratada por el estudiante en esta
     * matrícula. Las matrículas existentes heredan la de su nivel.
     */
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->decimal('weekly_hours', 5, 2)->default(0)->after('required_hours');
        });

        DB::table('enrollments')->update([
            'weekly_hours' => DB::raw('(select levels.weekly_hours from levels where levels.id = enrollments.level_id)'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn('weekly_hours');
        });
    }
};
