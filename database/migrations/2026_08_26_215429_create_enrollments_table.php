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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('level_id')->constrained()->restrictOnDelete();
            $table->date('enrolled_at');
            $table->date('start_date');
            $table->date('estimated_end_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->enum('status', [
                'pendiente',
                'activa',
                'en_recuperacion',
                'extendida',
                'finalizada',
                'cancelada',
                'aprobada',
                'reprobada',
            ])->default('pendiente');
            $table->decimal('required_hours', 8, 2);
            $table->decimal('accumulated_hours', 8, 2)->default(0);
            $table->decimal('base_price', 12, 2);
            $table->decimal('final_price', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
