<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clases sin docente (parte 11 del plan de mejoras): se importan sin
 * docente, aparecen como "Sin docente" en el calendario y se avisa 48 horas
 * antes si todavía no tienen uno. No se puede tomar asistencia sin docente.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
        });

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->foreignId('teacher_id')->nullable()->change();
            $table->foreign('teacher_id')->references('id')->on('teachers')->nullOnDelete();
            $table->timestamp('unassigned_alert_sent_at')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->dropColumn('unassigned_alert_sent_at');
        });

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->foreignId('teacher_id')->nullable(false)->change();
            $table->foreign('teacher_id')->references('id')->on('teachers')->cascadeOnDelete();
        });
    }
};
