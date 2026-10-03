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
        Schema::table('students', function (Blueprint $table) {
            $table->string('document_type', 10)->nullable()->after('name');
            $table->date('birth_date')->nullable()->after('document');
            $table->string('guardian_name')->nullable()->after('address');
            $table->string('guardian_document_type', 10)->nullable()->after('guardian_name');
            $table->string('guardian_document')->nullable()->after('guardian_document_type');
            $table->string('guardian_relationship')->nullable()->after('guardian_document');
            $table->string('guardian_email')->nullable()->after('guardian_relationship');
            $table->string('guardian_phone')->nullable()->after('guardian_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'document_type',
                'birth_date',
                'guardian_name',
                'guardian_document_type',
                'guardian_document',
                'guardian_relationship',
                'guardian_email',
                'guardian_phone',
            ]);
        });
    }
};
