<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Checklist de la gestión académica: requisitos que el administrador define
 * (contratos firmados, foto de perfil, kit de inicio entregado…) y que cada
 * estudiante debe cumplir. Un requisito es manual (lo marca el personal) o
 * automático (la plataforma lo revisa sola).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('automatic_check', 40)->nullable();
            $table->unsignedInteger('position')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_items');
    }
};
