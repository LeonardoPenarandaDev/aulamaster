<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Material de clase por tipo (parte 12 del plan de mejoras): YouTube,
 * enlace, PDF o imagen. Los archivos se guardan en el disco privado.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('class_materials', function (Blueprint $table) {
            $table->enum('type', ['youtube', 'enlace', 'pdf', 'imagen'])->default('enlace')->after('class_session_id');
            $table->string('url', 2048)->nullable()->change();
            $table->string('file_path')->nullable()->after('url');
            $table->string('file_name')->nullable()->after('file_path');
            $table->unsignedInteger('file_size')->nullable()->after('file_name');
            $table->string('mime_type', 100)->nullable()->after('file_size');
        });

        DB::table('class_materials')
            ->where(fn ($query) => $query->where('url', 'like', '%youtube.com/%')->orWhere('url', 'like', '%youtu.be/%'))
            ->update(['type' => 'youtube']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('class_materials')->whereNull('url')->delete();

        Schema::table('class_materials', function (Blueprint $table) {
            $table->dropColumn(['type', 'file_path', 'file_name', 'file_size', 'mime_type']);
            $table->string('url', 2048)->nullable(false)->change();
        });
    }
};
