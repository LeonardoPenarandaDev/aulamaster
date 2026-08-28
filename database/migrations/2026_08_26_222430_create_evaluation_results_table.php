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
        Schema::create('evaluation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained()->restrictOnDelete();
            $table->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('attempt_number')->default(1);
            $table->boolean('is_recovery')->default(false);
            $table->date('evaluated_at');
            $table->decimal('grade', 5, 2);
            $table->enum('result', ['aprobado', 'reprobado']);
            $table->text('notes')->nullable();
            $table->foreignId('registered_by_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['evaluation_id', 'enrollment_id', 'attempt_number'], 'eval_results_unique_attempt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_results');
    }
};
