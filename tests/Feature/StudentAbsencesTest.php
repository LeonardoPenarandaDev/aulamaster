<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * El admin ve quiénes faltaron o no pudieron asistir a clase, con sus datos
 * de contacto, para que el instituto se comunique con ellos.
 */
class StudentAbsencesTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_admin_sees_students_with_absences_grouped_with_contact_data(): void
    {
        $student = Student::factory()->create(['name' => 'Ana Faltas', 'phone' => '3001234567', 'email' => 'ana@test.com']);
        $enrollment = Enrollment::factory()->create(['student_id' => $student->id]);

        $this->attendance($enrollment, 'ausente', today()->subDays(2));
        $this->attendance($enrollment, 'ausente', today()->subDays(4));
        $this->attendance($enrollment, 'excusado', today()->subDays(6));
        $this->attendance($enrollment, 'presente', today()->subDays(8));

        $alwaysPresent = Enrollment::factory()->create();
        $this->attendance($alwaysPresent, 'presente', today()->subDay());

        $this->actingAs($this->adminUser())
            ->get(route('attendance.absences'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Attendance/Absences')
                ->has('students', 1)
                ->where('students.0.student.name', 'Ana Faltas')
                ->where('students.0.student.phone', '3001234567')
                ->where('students.0.student.email', 'ana@test.com')
                ->where('students.0.absent_count', 2)
                ->where('students.0.excused_count', 1)
                ->where('students.0.last_missed_date', today()->subDays(2)->toDateString())
                ->where('students.0.last_present_date', today()->subDays(8)->toDateString())
                ->has('students.0.missed', 3)
            );
    }

    public function test_absence_corrected_to_present_is_not_listed(): void
    {
        $attendance = $this->attendance(Enrollment::factory()->create(), 'ausente', today()->subDay());
        $attendance->corrections()->create([
            'previous_status' => 'ausente',
            'new_status' => 'presente',
            'reason' => 'El profesor se equivocó',
            'corrected_by_id' => User::factory()->create()->id,
        ]);

        $this->actingAs($this->adminUser())
            ->get(route('attendance.absences'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('students', 0));
    }

    public function test_filters_by_date_range_and_status(): void
    {
        $enrollment = Enrollment::factory()->create();
        $this->attendance($enrollment, 'ausente', today()->subDays(60));
        $this->attendance($enrollment, 'excusado', today()->subDays(3));

        $this->actingAs($admin = $this->adminUser())
            ->get(route('attendance.absences'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('students.0.absent_count', 0)
                ->where('students.0.excused_count', 1)
            );

        $this->actingAs($admin)
            ->get(route('attendance.absences', ['status' => 'ausente']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('students', 0));

        $this->actingAs($admin)
            ->get(route('attendance.absences', ['from' => today()->subDays(90)->toDateString(), 'status' => 'ausente']))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('students.0.absent_count', 1));
    }

    public function test_only_admin_can_see_absences(): void
    {
        foreach (['teacherUser', 'studentUser', 'coordinadorUser', 'cajeroUser'] as $userFactory) {
            $this->actingAs($this->{$userFactory}())
                ->get(route('attendance.absences'))
                ->assertForbidden();
        }
    }

    private function attendance(Enrollment $enrollment, string $status, $date): Attendance
    {
        return Attendance::factory()->create([
            'enrollment_id' => $enrollment->id,
            'status' => $status,
            'class_date' => $date->toDateString(),
        ]);
    }
}
