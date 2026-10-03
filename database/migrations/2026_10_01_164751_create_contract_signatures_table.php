<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada fila es un contrato generado para un estudiante y su evidencia de
 * firma (parte 6.4 del plan de mejoras).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contract_signatures', function (Blueprint $table) {
            $table->id();

            // Vínculos
            $table->foreignId('enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_template_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('template_version');

            // Estado: pendiente → enviado → abierto → firmado, o anulado.
            $table->enum('status', ['pendiente', 'enviado', 'abierto', 'firmado', 'anulado'])->default('pendiente');
            $table->enum('decision', ['acepta', 'no_acepta'])->nullable();
            $table->enum('signing_method', ['oficina', 'distancia'])->nullable();

            // Documento
            $table->text('special_clauses')->nullable();
            $table->longText('rendered_body');
            $table->string('content_hash', 64);
            $table->string('pdf_path')->nullable();

            // Firmante
            $table->string('signer_name')->nullable();
            $table->string('signer_document')->nullable();
            $table->enum('signer_role', ['alumno', 'acudiente'])->nullable();
            $table->string('signer_email')->nullable();

            // Evidencia
            $table->string('signature_path')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signer_timezone', 64)->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('otp_verified_at')->nullable();
            $table->string('id_front_path')->nullable();
            $table->string('id_back_path')->nullable();
            $table->foreignId('identity_verified_by_id')->nullable()->constrained('users')->nullOnDelete();

            // Envío a distancia
            $table->string('link_token', 64)->nullable()->index();
            $table->timestamp('link_expires_at')->nullable();
            $table->enum('sent_via', ['correo', 'whatsapp', 'enlace'])->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('sent_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at')->nullable();

            // Generación y anulación
            $table->foreignId('generated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable();

            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['enrollment_id', 'status']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->boolean('image_consent')->default(false)->after('guardian_phone');
            $table->timestamp('image_consent_updated_at')->nullable()->after('image_consent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['image_consent', 'image_consent_updated_at']);
        });

        Schema::dropIfExists('contract_signatures');
    }
};
