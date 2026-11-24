<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cómo se enseña cada tema (Listening, Writing, Speaking, Reading) y las
 * observaciones con instrucciones para el docente que dicte la clase.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('level_topics', function (Blueprint $table) {
            $table->json('skills')->nullable()->after('name');
            $table->text('teacher_notes')->nullable()->after('skills');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('level_topics', function (Blueprint $table) {
            $table->dropColumn(['skills', 'teacher_notes']);
        });
    }
};
