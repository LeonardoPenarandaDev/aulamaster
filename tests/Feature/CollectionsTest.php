<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\PaymentFollowUp;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Parte 8 del plan de mejoras: "Cartera en mora" con semáforo, contacto
 * (al acudiente si es menor), registro de contactos y acuerdos de pago.
 */
class CollectionsTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private function overdue(Student $student, int $daysAgo, float $amount = 250000): Payment
    {
        return Payment::factory()->create([
            'student_id' => $student->id,
            'type' => 'mensualidad',
            'due_date' => now()->subDays($daysAgo)->toDateString(),
            'final_amount' => $amount,
            'status' => 'vencido',
        ]);
    }

    public function test_lists_students_in_debt_with_traffic_light_and_guardian_contact(): void
    {
        $adult = Student::factory()->create(['birth_date' => now()->subYears(30), 'phone' => '3001112233']);
        $minor = Student::factory()->create([
            'birth_date' => now()->subYears(12),
            'guardian_name' => 'Marta Pérez',
            'guardian_phone' => '+57 310 555 1234',
        ]);
        $this->overdue($adult, 3);
        $this->overdue($minor, 12, 100000);
        $this->overdue($minor, 4, 100000);

        $this->actingAs($this->secretariaUser())
            ->get(route('collections.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('totals.students', 2)
                ->where('totals.amount', 450000)
                ->where('students.0.student.id', $minor->id)
                ->where('students.0.days_overdue', 12)
                ->where('students.0.severity', 'rojo')
                ->where('students.0.contact.role', 'acudiente')
                ->where('students.0.contact.name', 'Marta Pérez')
                ->where('students.1.severity', 'amarillo')
                ->where('students.1.contact.role', 'alumno')
            );

        $this->actingAs($this->cajeroUser())
            ->get(route('collections.index', ['days' => 'rojo']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('students', 1));
    }

    public function test_registering_a_contact_removes_the_student_from_uncontacted(): void
    {
        $student = Student::factory()->create();
        $this->overdue($student, 5);
        $secretaria = $this->secretariaUser();

        $this->actingAs($secretaria)
            ->get(route('collections.index', ['uncontacted' => 1]))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('students', 1));

        $this->actingAs($secretaria)
            ->post(route('collections.follow-ups.store', $student), ['channel' => 'llamada', 'result' => 'promesa_pago', 'note' => 'Paga el viernes'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(PaymentFollowUp::class, ['student_id' => $student->id, 'contacted_by_id' => $secretaria->id, 'result' => 'promesa_pago']);

        $this->actingAs($secretaria)
            ->get(route('collections.index', ['uncontacted' => 1]))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('students', 0));
    }

    public function test_only_admin_and_cashier_create_payment_agreements(): void
    {
        $student = Student::factory()->create();
        $this->overdue($student, 5);
        $data = ['agreed_until' => now()->addDays(10)->toDateString(), 'notes' => 'Dos cuotas'];

        $this->actingAs($this->secretariaUser())->post(route('collections.agreements.store', $student), $data)->assertForbidden();
        $this->actingAs($this->cajeroUser())->post(route('collections.agreements.store', $student), $data)->assertSessionHasNoErrors();

        $this->assertTrue($student->paymentAgreements()->active()->exists());
        $this->assertFalse($student->isBlockedForDebt());

        $this->actingAs($this->cajeroUser())
            ->post(route('collections.agreements.store', $student), ['agreed_until' => now()->addMonths(6)->toDateString()])
            ->assertSessionHasErrors('agreed_until');
    }

    public function test_teachers_and_students_cannot_see_collections(): void
    {
        $this->actingAs($this->teacherUser())->get(route('collections.index'))->assertForbidden();
        $this->actingAs($this->coordinadorUser())->get(route('collections.index'))->assertForbidden();
    }
}
