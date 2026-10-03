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
        Schema::table('levels', function (Blueprint $table) {
            $table->foreignId('next_level_id')->nullable()->unique()->after('course_id')->constrained('levels')->nullOnDelete();
            $table->unsignedInteger('position')->nullable()->after('next_level_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('levels', function (Blueprint $table) {
            $table->dropConstrainedForeignId('next_level_id');
            $table->dropColumn('position');
        });
    }
};
