<?php

namespace Tests\Feature;

use App\Actions\Payments\ProcessMonthlyFees;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Notifications\LongOverdueAlertNotification;
use App\Notifications\MonthlyFeeGeneratedNotification;
use App\Notifications\MonthlyFeeReminderNotification;
use App\Notifications\PaymentOverdueNotification;
use App\Notifications\StudentOverdueStaffNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Parte 8 del plan de mejoras: el comando diario genera la mensualidad el
 * día 1, recuerda antes del día límite (5), la marca vencida el día 6 y
 * alerta al admin a los 10 días de mora.
 */
class MonthlyFeeTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->admin = $this->adminUser();
    }

    protected User $admin;

    private function activeEnrollment(array $levelAttributes = ['monthly_fee' => 250000], array $attributes = []): Enrollment
    {
        $level = Level::factory()->create($levelAttributes);

        return Enrollment::factory()->create([
            'level_id' => $level->id,
            'status' => 'activa',
            'start_date' => '2026-09-01',
            ...$attributes,
        ]);
    }

    private function runOn(string $date): array
    {
        Carbon::setTestNow($date.' 06:00');

        return app(ProcessMonthlyFees::class)->handle();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_day_1_generates_the_monthly_fee_once(): void
    {
        $enrollment = $this->activeEnrollment();

        $this->assertSame(1, $this->runOn('2026-10-01')['generated']);
        $this->assertSame(0, $this->runOn('2026-10-02')['generated']);

        $payment = Payment::query()->where('enrollment_id', $enrollment->id)->sole();
        $this->assertSame('mensualidad', $payment->type);
        $this->assertSame('2026-10', $payment->period);
        $this->assertSame('2026-10-05', $payment->due_date->toDateString());
        $this->assertEquals(250000, $payment->final_amount);
        $this->assertSame('pendiente', $payment->status);
        Notification::assertSentOnDemand(MonthlyFeeGeneratedNotification::class);
    }

    public function test_fee_adjusted_per_student_wins_over_the_level_value(): void
    {
        $enrollment = $this->activeEnrollment(['monthly_fee' => 250000], ['monthly_fee' => 200000]);

        $this->runOn('2026-10-01');

        $this->assertEquals(200000, Payment::query()->where('enrollment_id', $enrollment->id)->value('final_amount'));
    }

    public function test_no_fee_for_levels_without_monthly_fee_inactive_enrollments_or_late_starts(): void
    {
        $this->activeEnrollment(['monthly_fee' => null]);
        $this->activeEnrollment(['monthly_fee' => 250000], ['status' => 'pendiente']);
        $this->activeEnrollment(['monthly_fee' => 250000], ['start_date' => '2026-10-20']);

        $this->assertSame(0, $this->runOn('2026-10-01')['generated']);
    }

    public function test_minor_guardian_also_receives_the_notice(): void
    {
        $student = Student::factory()->create([
            'birth_date' => now()->subYears(12),
            'guardian_email' => 'acudiente@correo.test',
        ]);
        $this->activeEnrollment(['monthly_fee' => 250000], ['student_id' => $student->id]);

        $this->runOn('2026-10-01');

        Notification::assertSentOnDemand(MonthlyFeeGeneratedNotification::class, fn ($notification, $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'acudiente@correo.test');
    }

    public function test_reminder_overdue_and_long_overdue_alert_follow_the_calendar(): void
    {
        $cajero = $this->cajeroUser();
        $secretaria = $this->secretariaUser();
        $enrollment = $this->activeEnrollment();
        $this->runOn('2026-10-01');
        $payment = Payment::query()->where('enrollment_id', $enrollment->id)->sole();

        $this->assertSame(0, $this->runOn('2026-10-02')['reminded']);
        $this->assertSame(1, $this->runOn('2026-10-03')['reminded']);
        $this->assertSame(0, $this->runOn('2026-10-04')['reminded']);
        Notification::assertSentOnDemand(MonthlyFeeReminderNotification::class);

        $this->assertSame(0, $this->runOn('2026-10-05')['overdue']);
        $this->assertSame(1, $this->runOn('2026-10-06')['overdue']);
        $this->assertSame('vencido', $payment->fresh()->status);
        Notification::assertSentOnDemand(PaymentOverdueNotification::class);
        Notification::assertSentTo([$cajero, $secretaria, $this->admin], StudentOverdueStaffNotification::class);

        $this->assertSame(0, $this->runOn('2026-10-14')['alerted']);
        $this->assertSame(1, $this->runOn('2026-10-15')['alerted']);
        $this->assertSame(0, $this->runOn('2026-10-16')['alerted']);
        Notification::assertSentTo($this->admin, LongOverdueAlertNotification::class);
        Notification::assertNotSentTo($cajero, LongOverdueAlertNotification::class);
    }

    public function test_paid_fees_never_become_overdue_and_old_payments_without_due_date_are_ignored(): void
    {
        $enrollment = $this->activeEnrollment();
        $this->runOn('2026-10-01');
        Payment::query()->where('enrollment_id', $enrollment->id)->update(['status' => 'pagado']);
        $legacy = Payment::factory()->create(['student_id' => $enrollment->student_id, 'status' => 'pendiente']);

        $this->assertSame(0, $this->runOn('2026-10-06')['overdue']);
        $this->assertSame('otro', $legacy->fresh()->type);
        $this->assertSame('pendiente', $legacy->fresh()->status);
    }

    public function test_command_runs_from_the_console(): void
    {
        $this->activeEnrollment();
        Carbon::setTestNow('2026-10-01 06:00');

        $this->artisan('app:generate-monthly-fees')
            ->expectsOutputToContain('Mensualidades generadas: 1')
            ->assertSuccessful();
    }

    public function test_cashier_corrects_a_pending_fee_but_not_a_paid_one(): void
    {
        $enrollment = $this->activeEnrollment();
        $this->runOn('2026-10-01');
        $payment = Payment::query()->where('enrollment_id', $enrollment->id)->sole();
        $cajero = $this->cajeroUser();

        $this->actingAs($cajero)
            ->put(route('payments.update', $payment), ['status' => 'pendiente', 'final_amount' => 225000])
            ->assertSessionHasNoErrors();
        $this->assertEquals(225000, $payment->fresh()->final_amount);

        $payment->update(['status' => 'pagado']);

        $this->actingAs($cajero)
            ->put(route('payments.update', $payment), ['status' => 'pagado', 'final_amount' => 1000])
            ->assertSessionHasErrors('final_amount');
        $this->assertEquals(225000, $payment->fresh()->final_amount);
    }

    public function test_changing_the_level_fee_can_be_applied_to_active_enrollments(): void
    {
        $enrollment = $this->activeEnrollment(['monthly_fee' => 250000], ['monthly_fee' => 250000]);
        $custom = Enrollment::factory()->create(['level_id' => $enrollment->level_id, 'status' => 'activa', 'monthly_fee' => 180000]);
        $level = $enrollment->level;

        $this->actingAs($this->admin)->put(route('levels.update', $level), [
            ...$level->only(['course_id', 'name', 'code', 'required_hours', 'minimum_grade', 'price', 'status']),
            'monthly_fee' => 280000,
            'apply_monthly_fee_to_active' => true,
        ])->assertSessionHasNoErrors();

        $this->assertEquals(280000, $enrollment->fresh()->monthly_fee);
        $this->assertEquals(180000, $custom->fresh()->monthly_fee);
    }
}
