<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Temas de cada nivel global (grupo de clases): "Elementary 1" tiene sus
 * temas en orden, compartidos por sus intensidades (Standard,
 * Semi-Intensivo e Intensivo). Cada clase que se programa puede llevar uno.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('level_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('class_group', 120);
            $table->string('name');
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();

            $table->index(['course_id', 'class_group', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('level_topics');
    }
};
