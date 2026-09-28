<?php

namespace Tests\Feature;

use App\Actions\Attendance\GetWeeklyHoursSummary;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * El estudiante ve las clases de su nivel de esta semana y la próxima para
 * asistir a otros grupos y recuperar las horas que le falten.
 */
class StudentScheduleTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private function studentWithEnrollment(array $enrollmentAttributes = []): array
    {
        $user = $this->studentUser();
        $student = Student::factory()->create(['user_id' => $user->id]);
        $enrollment = Enrollment::factory()->create(['student_id' => $student->id, ...$enrollmentAttributes]);

        return [$user, $enrollment];
    }

    private function attend(Enrollment $enrollment, string $date, string $start, string $end): void
    {
        $session = ClassSession::factory()->create([
            'level_id' => $enrollment->level_id,
            'date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'dictada',
        ]);

        Attendance::factory()->create([
            'class_session_id' => $session->id,
            'enrollment_id' => $enrollment->id,
            'class_date' => $date,
            'status' => 'presente',
        ]);
    }

    public function test_backlog_accumulates_missing_hours_and_extra_hours_compensate(): void
    {
        $this->travelTo('2026-09-30 12:00:00'); // Miércoles, semana del 28 sep.

        [, $enrollment] = $this->studentWithEnrollment(['weekly_hours' => 8, 'start_date' => '2026-09-14']);

        // Semana del 14: 4 h (faltan 4). Semana del 21: 10 h (sobran 2). Neto: faltan 2.
        $this->attend($enrollment, '2026-09-15', '08:00', '12:00');
        $this->attend($enrollment, '2026-09-22', '08:00', '12:00');
        $this->attend($enrollment, '2026-09-24', '08:00', '14:00');
        // Semana actual: 3 h.
        $this->attend($enrollment, '2026-09-28', '07:00', '10:00');

        $catchUp = app(GetWeeklyHoursSummary::class)->catchUp($enrollment);

        $this->assertEquals(2, $catchUp['backlog_hours']);
        $this->assertEquals(7, $catchUp['needed_this_week']);
    }

    public function test_there_is_no_backlog_in_the_first_week(): void
    {
        $this->travelTo('2026-09-30 12:00:00');

        [, $enrollment] = $this->studentWithEnrollment(['weekly_hours' => 8, 'start_date' => '2026-09-29']);

        $catchUp = app(GetWeeklyHoursSummary::class)->catchUp($enrollment);

        $this->assertEquals(0, $catchUp['backlog_hours']);
        $this->assertEquals(8, $catchUp['needed_this_week']);
    }

    public function test_student_sees_sessions_of_their_level_for_this_and_next_week_only(): void
    {
        $this->travelTo('2026-09-30 12:00:00');

        [$user, $enrollment] = $this->studentWithEnrollment(['weekly_hours' => 8, 'start_date' => '2026-09-28']);

        $thisWeek = ClassSession::factory()->create(['level_id' => $enrollment->level_id, 'date' => '2026-10-01', 'notes' => 'Repaso para examen']);
        $nextWeek = ClassSession::factory()->create(['level_id' => $enrollment->level_id, 'date' => '2026-10-10']);
        ClassSession::factory()->create(['level_id' => $enrollment->level_id, 'date' => '2026-10-12']);
        ClassSession::factory()->create(['level_id' => $enrollment->level_id, 'date' => '2026-09-29']);
        ClassSession::factory()->create(['level_id' => Level::factory(), 'date' => '2026-10-01']);

        $this->actingAs($user)
            ->get(route('student-schedule.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('StudentSchedule/Index')
                ->where('enrollment.weekly_hours', 8)
                ->has('sessions', 2)
                ->where('sessions.0.id', $thisWeek->id)
                ->where('sessions.0.week_start', '2026-09-28')
                ->where('sessions.0.notes', 'Repaso para examen')
                ->where('sessions.1.id', $nextWeek->id)
                ->where('sessions.1.week_start', '2026-10-05')
                ->where('catch_up.needed_this_week', 8)
            );
    }

    public function test_only_students_can_open_the_schedule_page(): void
    {
        $this->actingAs($this->teacherUser())->get(route('student-schedule.index'))->assertForbidden();
        $this->actingAs($this->adminUser())->get(route('student-schedule.index'))->assertForbidden();
    }

    public function test_student_without_enrollment_sees_an_empty_page(): void
    {
        $user = $this->studentUser();
        Student::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('student-schedule.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('enrollment', null));
    }

    public function test_student_user_without_a_student_record_sees_an_empty_page(): void
    {
        $user = $this->studentUser();

        $this->assertNull($user->student);
        $this->actingAs(User::find($user->id))
            ->get(route('student-schedule.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('enrollment', null));
    }

    public function test_students_are_never_told_who_teaches_the_class(): void
    {
        $this->travelTo('2026-09-30 12:00:00');

        [$user, $enrollment] = $this->studentWithEnrollment(['start_date' => '2026-09-28']);
        $teacher = Teacher::factory()->create(['name' => 'Profesor Secreto']);
        ClassSession::factory()->create(['level_id' => $enrollment->level_id, 'teacher_id' => $teacher->id, 'date' => '2026-10-01']);

        $this->actingAs($user)
            ->get(route('student-schedule.index'))
            ->assertDontSee('Profesor Secreto')
            ->assertInertia(fn (AssertableInertia $page) => $page->missing('sessions.0.teacher'));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertDontSee('Profesor Secreto')
            ->assertInertia(fn (AssertableInertia $page) => $page->missing('studentData.next_session.teacher'));
    }
}
