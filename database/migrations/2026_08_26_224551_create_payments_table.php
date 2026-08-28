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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('concept');
            $table->decimal('base_amount', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('final_amount', 12, 2);
            $table->foreignId('promotion_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('referral_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('receipt_reference')->nullable();
            $table->enum('status', ['pendiente', 'pagado', 'vencido', 'anulado'])->default('pendiente');
            $table->foreignId('registered_by_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
