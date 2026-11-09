<?php

use App\Models\Level;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Grupo de clases: los niveles del mismo curso con el mismo grupo (p. ej.
 * "Elementary 1 Standard", "Elementary 1 Semi-Intensivo" y "Elementary 1
 * Intensivo") comparten las clases del calendario. Solo cambia cuántas
 * horas por semana puede tomar cada estudiante.
 *
 * Solo agrega la columna: los niveles existentes se agrupan a partir de su
 * nombre, quitando la intensidad del final; no se modifica ningún otro dato.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('levels', function (Blueprint $table) {
            $table->string('class_group', 120)->nullable()->after('name');
            $table->index(['course_id', 'class_group']);
        });

        DB::table('levels')->select(['id', 'name'])->orderBy('id')->each(function (object $level) {
            DB::table('levels')->where('id', $level->id)->update(['class_group' => Level::classGroupFromName($level->name)]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('levels', function (Blueprint $table) {
            $table->dropIndex(['course_id', 'class_group']);
            $table->dropColumn('class_group');
        });
    }
};
