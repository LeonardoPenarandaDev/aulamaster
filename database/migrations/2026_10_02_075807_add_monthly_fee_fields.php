<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mensualidades y mora (parte 8 del plan de mejoras). Los pagos que ya
 * existen quedan como "otro", sin fecha de vencimiento, y no activan la mora.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('type', ['matricula', 'mensualidad', 'otro'])->default('otro')->after('enrollment_id');
            $table->string('period', 7)->nullable()->after('type');
            $table->date('due_date')->nullable()->after('period');
            $table->timestamp('reminder_sent_at')->nullable()->after('gateway_status');
            $table->timestamp('overdue_alert_sent_at')->nullable()->after('reminder_sent_at');

            $table->unique(['enrollment_id', 'type', 'period']);
            $table->index(['status', 'due_date']);
        });

        Schema::table('levels', function (Blueprint $table) {
            $table->decimal('monthly_fee', 12, 2)->nullable()->after('price');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->decimal('monthly_fee', 12, 2)->nullable()->after('final_price');
        });

        Schema::table('institution_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('payment_due_day')->default(5)->after('signer_title');
            $table->unsignedTinyInteger('payment_reminder_days')->default(2)->after('payment_due_day');
            $table->unsignedTinyInteger('overdue_alert_days')->default(10)->after('payment_reminder_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['enrollment_id', 'type', 'period']);
            $table->dropIndex(['status', 'due_date']);
            $table->dropColumn(['type', 'period', 'due_date', 'reminder_sent_at', 'overdue_alert_sent_at']);
        });

        Schema::table('levels', function (Blueprint $table) {
            $table->dropColumn('monthly_fee');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn('monthly_fee');
        });

        Schema::table('institution_settings', function (Blueprint $table) {
            $table->dropColumn(['payment_due_day', 'payment_reminder_days', 'overdue_alert_days']);
        });
    }
};
