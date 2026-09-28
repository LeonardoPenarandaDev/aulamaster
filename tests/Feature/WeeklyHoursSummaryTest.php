<?php

namespace Tests\Feature;

use App\Actions\Attendance\GetWeeklyHoursSummary;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Cada matrícula tiene una intensidad horaria semanal contratada; el
 * estudiante ve cuántas horas lleva en la semana y cuántas le faltan.
 */
class WeeklyHoursSummaryTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private function attend(Enrollment $enrollment, string $date, string $status, string $start = '14:00', string $end = '16:00'): Attendance
    {
        $session = ClassSession::factory()->create([
            'level_id' => $enrollment->level_id,
            'date' => $date,
            'start_time' => $start,
            'end_time' => $end,
        ]);

        return Attendance::factory()->create([
            'class_session_id' => $session->id,
            'enrollment_id' => $enrollment->id,
            'class_date' => $date,
            'status' => $status,
        ]);
    }

    public function test_it_sums_present_hours_per_week_against_the_weekly_intensity(): void
    {
        $this->travelTo('2026-09-30 12:00:00'); // Miércoles.

        $enrollment = Enrollment::factory()->create(['weekly_hours' => 8, 'start_date' => '2026-09-14']);

        $this->attend($enrollment, '2026-09-28', 'presente');
        $this->attend($enrollment, '2026-09-29', 'ausente');
        $this->attend($enrollment, '2026-09-30', 'presente', '07:00', '08:30');

        $this->attend($enrollment, '2026-09-22', 'presente', '08:00', '12:00');
        $this->attend($enrollment, '2026-09-24', 'presente', '08:00', '12:00');

        $corrected = $this->attend($enrollment, '2026-09-15', 'ausente');
        $corrected->corrections()->create([
            'previous_status' => 'ausente',
            'new_status' => 'presente',
            'reason' => 'Error de registro',
            'corrected_by_id' => $corrected->registered_by_id,
        ]);

        $summary = app(GetWeeklyHoursSummary::class)->handle($enrollment);

        $this->assertCount(3, $summary, 'No debe incluir semanas anteriores al inicio de la matrícula.');

        $this->assertSame('2026-09-28', $summary[0]['week_start']);
        $this->assertSame('2026-10-04', $summary[0]['week_end']);
        $this->assertTrue($summary[0]['is_current']);
        $this->assertEquals(3.5, $summary[0]['attended_hours']);
        $this->assertEquals(4.5, $summary[0]['pending_hours']);

        $this->assertEquals(8, $summary[1]['attended_hours']);
        $this->assertEquals(0, $summary[1]['pending_hours']);

        $this->assertEquals(2, $summary[2]['attended_hours']);
        $this->assertEquals(6, $summary[2]['pending_hours']);
    }

    public function test_pending_hours_never_go_below_zero(): void
    {
        $this->travelTo('2026-09-30 12:00:00');

        $enrollment = Enrollment::factory()->create(['weekly_hours' => 2, 'start_date' => '2026-09-28']);
        $this->attend($enrollment, '2026-09-28', 'presente', '08:00', '12:00');

        $summary = app(GetWeeklyHoursSummary::class)->handle($enrollment);

        $this->assertEquals(4, $summary[0]['attended_hours']);
        $this->assertEquals(0, $summary[0]['pending_hours']);
    }

    public function test_student_dashboard_shows_the_weekly_summary(): void
    {
        $this->travelTo('2026-09-30 12:00:00');

        $user = $this->studentUser();
        $student = Student::factory()->create(['user_id' => $user->id]);
        $enrollment = Enrollment::factory()->create([
            'student_id' => $student->id,
            'weekly_hours' => 12,
            'start_date' => '2026-09-28',
        ]);
        $this->attend($enrollment, '2026-09-28', 'presente');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('studentData.enrollment.weekly_hours', '12.00')
                ->where('studentData.weekly_summary.0.attended_hours', 2)
                ->where('studentData.weekly_summary.0.pending_hours', 10)
            );
    }

    public function test_enrollment_stores_the_weekly_intensity(): void
    {
        $student = Student::factory()->create();
        $level = Level::factory()->create();

        $this->actingAs($this->adminUser())
            ->post(route('enrollments.store'), [
                'student_id' => $student->id,
                'level_id' => $level->id,
                'enrolled_at' => '2026-09-28',
                'start_date' => '2026-09-28',
                'status' => 'activa',
                'required_hours' => 128,
                'weekly_hours' => 12,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('enrollments', ['student_id' => $student->id, 'level_id' => $level->id, 'weekly_hours' => 12]);
    }
}
