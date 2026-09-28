<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clases presenciales o virtuales. Las virtuales (p. ej. las de la
     * noche) llevan el enlace de la reunión (Meet) que el profesor genera el
     * día de la clase.
     */
    public function up(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->enum('modality', ['presencial', 'virtual'])->default('presencial')->after('classroom_id');
        });

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->enum('modality', ['presencial', 'virtual'])->default('presencial')->after('classroom_id');
            $table->string('meeting_url', 2048)->nullable()->after('modality');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropColumn(['modality', 'meeting_url']);
        });

        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropColumn('modality');
        });
    }
};
