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
        Schema::create('contract_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80);
            $table->unsignedInteger('version')->default(1);
            $table->string('name');
            $table->string('type', 30);
            $table->longText('body');
            $table->enum('status', ['borrador', 'publicado', 'archivado'])->default('borrador');
            $table->enum('acceptance_mode', ['obligatorio', 'opcional'])->default('obligatorio');
            $table->enum('scope', ['matricula', 'alumno'])->default('matricula');
            $table->boolean('requires_guardian')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['code', 'version']);
            $table->index(['status', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_templates');
    }
};
