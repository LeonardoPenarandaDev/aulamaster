<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estilo del menú lateral del personal (Sistema → Apariencia): claro,
 * oscuro o del color principal de la institución.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('institution_settings', function (Blueprint $table) {
            $table->string('sidebar_style', 10)->default('color')->after('accent_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institution_settings', function (Blueprint $table) {
            $table->dropColumn('sidebar_style');
        });
    }
};
