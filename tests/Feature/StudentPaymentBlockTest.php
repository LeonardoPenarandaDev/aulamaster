<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\PaymentAgreement;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Parte 8 del plan de mejoras: el estudiante en mora queda bloqueado hasta
 * pagar o tener un acuerdo de pago, y el docente no le toma asistencia.
 */
class StudentPaymentBlockTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    /**
     * @return array{0: User, 1: Student, 2: Payment}
     */
    private function studentInDebt(): array
    {
        $user = $this->studentUser();
        $student = Student::factory()->create(['user_id' => $user->id]);
        $enrollment = Enrollment::factory()->create(['student_id' => $student->id, 'status' => 'activa']);
        $payment = Payment::factory()->create([
            'student_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'type' => 'mensualidad',
            'period' => now()->format('Y-m'),
            'due_date' => now()->subDays(3)->toDateString(),
            'status' => 'vencido',
        ]);

        return [$user, $student, $payment];
    }

    public function test_student_in_debt_is_sent_to_the_pending_payments_screen(): void
    {
        [$user, , $payment] = $this->studentInDebt();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('student-account.blocked'));
        $this->actingAs($user)->get(route('student-schedule.index'))->assertRedirect(route('student-account.blocked'));

        $this->actingAs($user)
            ->get(route('student-account.blocked'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('payments.0.id', $payment->id));
    }

    public function test_blocked_student_can_still_use_profile_contracts_and_log_out(): void
    {
        [$user] = $this->studentInDebt();

        $this->actingAs($user)->get(route('profile.edit'))->assertOk();
        $this->actingAs($user)->get(route('student-contracts.index'))->assertOk();
        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));
    }

    public function test_paying_unblocks_immediately(): void
    {
        [$user, , $payment] = $this->studentInDebt();

        $payment->update(['status' => 'pagado']);

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->actingAs($user)->get(route('student-account.blocked'))->assertRedirect(route('dashboard'));
    }

    public function test_active_payment_agreement_unblocks_until_its_date(): void
    {
        [$user, $student] = $this->studentInDebt();
        $agreement = PaymentAgreement::factory()->create(['student_id' => $student->id, 'agreed_until' => now()->addDays(5)->toDateString()]);

        $this->actingAs($user)->get('/dashboard')->assertOk();

        $agreement->update(['agreed_until' => now()->subDay()->toDateString()]);
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('student-account.blocked'));
    }

    public function test_old_payments_of_type_other_do_not_block(): void
    {
        $user = $this->studentUser();
        $student = Student::factory()->create(['user_id' => $user->id]);
        Payment::factory()->create(['student_id' => $student->id, 'status' => 'vencido']);

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_teacher_cannot_take_attendance_of_a_student_in_debt_but_admin_can(): void
    {
        [, $student] = $this->studentInDebt();
        $enrollment = $student->enrollments()->firstOrFail();
        $teacherUser = $this->teacherUser();
        $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);
        $session = ClassSession::factory()->create([
            'teacher_id' => $teacher->id,
            'level_id' => $enrollment->level_id,
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($teacherUser)
            ->get(route('attendance.create', $session))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('roster.0.is_blocked', true));

        $records = ['records' => [['enrollment_id' => $enrollment->id, 'status' => 'presente']]];

        $this->actingAs($teacherUser)->post(route('attendance.store', $session), $records)->assertSessionHasErrors('records');
        $this->assertSame(0, $enrollment->attendances()->count());

        $this->actingAs($this->adminUser())->post(route('attendance.store', $session), $records)->assertSessionHasNoErrors();
        $this->assertSame(1, $enrollment->attendances()->count());
    }

    public function test_overdue_payment_can_be_paid_online(): void
    {
        [$user, , $payment] = $this->studentInDebt();
        config(['services.wompi.public_key' => null]);

        // Sin llaves de Wompi responde 503, pero ya no 422 por estar vencido.
        $this->actingAs($user)->get(route('payments.pay-online', $payment))->assertStatus(503);
    }
}
