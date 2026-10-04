<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colores de la institución: el principal (botones, menú, enlaces) y el de
 * acento (degradados y detalles). Por defecto, el azul y el naranja de
 * Active English.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('institution_settings', function (Blueprint $table) {
            $table->string('primary_color', 7)->default('#1D4ED8')->after('logo_path');
            $table->string('accent_color', 7)->default('#F97316')->after('primary_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institution_settings', function (Blueprint $table) {
            $table->dropColumn(['primary_color', 'accent_color']);
        });
    }
};
