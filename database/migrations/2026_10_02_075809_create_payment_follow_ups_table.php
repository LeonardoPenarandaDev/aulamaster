<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro de contactos de la cartera en mora (parte 8 del plan de
     * mejoras).
     */
    public function up(): void
    {
        Schema::create('payment_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->enum('channel', ['whatsapp', 'llamada', 'correo', 'presencial']);
            $table->enum('result', ['contactado', 'promesa_pago', 'no_contesta', 'numero_errado', 'otro']);
            $table->text('note')->nullable();
            $table->foreignId('contacted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('contacted_at');
            $table->timestamps();

            $table->index(['student_id', 'contacted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_follow_ups');
    }
};
