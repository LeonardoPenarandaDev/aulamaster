<?php

namespace Tests\Feature;

use App\Actions\Scheduling\CheckClassSessionConflicts;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\Level;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Reglas 11 y 12 de la sección 40: el aula y el profesor no pueden tener dos
 * clases simultáneas. También cubre la sección 10 (reutilización de aula sin
 * solape) para dejar constancia de que no es un falso positivo demasiado
 * estricto.
 */
class SchedulingRulesTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private Level $level;

    private Classroom $classroom;

    private Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $course = Course::factory()->create();
        $this->level = Level::factory()->create(['course_id' => $course->id]);
        $this->classroom = Classroom::factory()->create();
        $this->teacher = Teacher::factory()->create();
    }

    public function test_classroom_cannot_have_two_overlapping_classes(): void
    {
        ClassSession::factory()->create([
            'classroom_id' => $this->classroom->id,
            'date' => '2026-08-26',
            'start_time' => '14:00',
            'end_time' => '15:00',
        ]);

        $conflicts = app(CheckClassSessionConflicts::class)->handle(
            classroomId: $this->classroom->id,
            teacherId: Teacher::factory()->create()->id,
            date: '2026-08-26',
            startTime: '14:30',
            endTime: '15:30',
        );

        $this->assertArrayHasKey('classroom_id', $conflicts);
    }

    public function test_classroom_can_be_reused_for_non_overlapping_classes(): void
    {
        ClassSession::factory()->create([
            'classroom_id' => $this->classroom->id,
            'date' => '2026-08-26',
            'start_time' => '14:00',
            'end_time' => '15:00',
        ]);

        $conflicts = app(CheckClassSessionConflicts::class)->handle(
            classroomId: $this->classroom->id,
            teacherId: Teacher::factory()->create()->id,
            date: '2026-08-26',
            startTime: '15:00',
            endTime: '16:00',
        );

        $this->assertSame([], $conflicts);
    }

    public function test_teacher_cannot_be_in_two_overlapping_classes_in_different_classrooms(): void
    {
        ClassSession::factory()->create([
            'teacher_id' => $this->teacher->id,
            'date' => '2026-08-26',
            'start_time' => '14:00',
            'end_time' => '15:00',
        ]);

        $conflicts = app(CheckClassSessionConflicts::class)->handle(
            classroomId: Classroom::factory()->create()->id,
            teacherId: $this->teacher->id,
            date: '2026-08-26',
            startTime: '14:30',
            endTime: '15:30',
        );

        $this->assertArrayHasKey('teacher_id', $conflicts);
    }

    public function test_cancelled_classes_do_not_count_as_conflicts(): void
    {
        ClassSession::factory()->create([
            'classroom_id' => $this->classroom->id,
            'date' => '2026-08-26',
            'start_time' => '14:00',
            'end_time' => '15:00',
            'status' => 'cancelada',
        ]);

        $conflicts = app(CheckClassSessionConflicts::class)->handle(
            classroomId: $this->classroom->id,
            teacherId: Teacher::factory()->create()->id,
            date: '2026-08-26',
            startTime: '14:00',
            endTime: '15:00',
        );

        $this->assertSame([], $conflicts);
    }

    public function test_deleting_a_schedule_removes_its_future_sessions_but_keeps_history(): void
    {
        $this->travelTo('2026-09-30 12:00:00');

        $schedule = ClassSchedule::factory()->create([
            'level_id' => $this->level->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
        ]);
        $sessionFor = fn (string $date) => ClassSession::factory()->create([
            'class_schedule_id' => $schedule->id,
            'level_id' => $this->level->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'date' => $date,
        ]);

        $past = $sessionFor('2026-09-28');
        $futureWithAttendance = $sessionFor('2026-10-01');
        Attendance::factory()->create(['class_session_id' => $futureWithAttendance->id]);
        $future = $sessionFor('2026-10-05');
        $today = $sessionFor('2026-09-30');

        $this->actingAs($this->adminUser())
            ->delete(route('class-schedules.destroy', $schedule))
            ->assertRedirect(route('class-schedules.index'))
            ->assertSessionHas('success');

        $this->assertModelMissing($schedule);
        $this->assertModelMissing($future);
        $this->assertModelMissing($today);
        $this->assertModelExists($past);
        $this->assertModelExists($futureWithAttendance);
        $this->assertNull($past->fresh()->class_schedule_id);
    }

    public function test_only_admin_or_coordinator_can_delete_a_schedule(): void
    {
        $schedule = ClassSchedule::factory()->create();

        $this->actingAs($this->teacherUser())
            ->delete(route('class-schedules.destroy', $schedule))
            ->assertForbidden();

        $this->assertModelExists($schedule);
    }
}
