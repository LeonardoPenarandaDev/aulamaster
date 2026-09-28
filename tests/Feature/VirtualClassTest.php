<?php

namespace Tests\Feature;

use App\Actions\Scheduling\CheckClassSessionConflicts;
use App\Actions\Scheduling\GenerateClassSessionsFromSchedule;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Las clases de la noche son virtuales: el profesor publica el día de la
 * clase el enlace de Meet y los estudiantes del nivel lo ven en sus horarios.
 */
class VirtualClassTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    /**
     * @return array{0: User, 1: ClassSession}
     */
    private function teacherWithVirtualSession(): array
    {
        $user = $this->teacherUser();
        $teacher = Teacher::factory()->create(['user_id' => $user->id]);

        return [$user, ClassSession::factory()->create(['teacher_id' => $teacher->id, 'modality' => 'virtual', 'start_time' => '18:00', 'end_time' => '20:00'])];
    }

    public function test_teacher_publishes_the_meeting_link_of_their_virtual_class(): void
    {
        [$user, $session] = $this->teacherWithVirtualSession();

        $this->actingAs($user)
            ->patch(route('class-sessions.meeting-url', $session), ['meeting_url' => 'https://meet.google.com/abc-defg-hij'])
            ->assertSessionHasNoErrors();

        $this->assertSame('https://meet.google.com/abc-defg-hij', $session->fresh()->meeting_url);
    }

    public function test_meeting_link_must_be_a_web_url_and_only_for_virtual_classes(): void
    {
        [$user, $session] = $this->teacherWithVirtualSession();

        $this->actingAs($user)
            ->patch(route('class-sessions.meeting-url', $session), ['meeting_url' => 'no es un enlace'])
            ->assertSessionHasErrors('meeting_url');

        $session->update(['modality' => 'presencial']);

        $this->actingAs($user)
            ->patch(route('class-sessions.meeting-url', $session), ['meeting_url' => 'https://meet.google.com/abc'])
            ->assertSessionHasErrors('meeting_url');

        $this->assertNull($session->fresh()->meeting_url);
    }

    public function test_teacher_cannot_set_the_link_of_another_teachers_class(): void
    {
        [$user] = $this->teacherWithVirtualSession();
        $other = ClassSession::factory()->create(['modality' => 'virtual']);

        $this->actingAs($user)
            ->patch(route('class-sessions.meeting-url', $other), ['meeting_url' => 'https://meet.google.com/abc'])
            ->assertForbidden();
    }

    public function test_students_of_the_level_see_the_virtual_class_and_its_link(): void
    {
        $this->travelTo('2026-09-30 10:00:00');

        [, $session] = $this->teacherWithVirtualSession();
        $session->update(['date' => '2026-09-30', 'meeting_url' => 'https://meet.google.com/abc-defg-hij']);

        $user = $this->studentUser();
        $student = Student::factory()->create(['user_id' => $user->id]);
        Enrollment::factory()->create(['student_id' => $student->id, 'level_id' => $session->level_id, 'start_date' => '2026-09-28']);

        $this->actingAs($user)
            ->get(route('student-schedule.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sessions.0.modality', 'virtual')
                ->where('sessions.0.meeting_url', 'https://meet.google.com/abc-defg-hij')
            );

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('studentData.next_session.modality', 'virtual')
                ->where('studentData.next_session.meeting_url', 'https://meet.google.com/abc-defg-hij')
            );
    }

    public function test_virtual_classes_do_not_take_up_a_classroom(): void
    {
        $presencial = ClassSession::factory()->create(['date' => '2026-10-01', 'start_time' => '18:00', 'end_time' => '20:00']);
        $otherTeacher = Teacher::factory()->create();

        $check = app(CheckClassSessionConflicts::class);

        $asPresencial = $check->handle($presencial->classroom_id, $otherTeacher->id, '2026-10-01', '18:00', '20:00');
        $asVirtual = $check->handle($presencial->classroom_id, $otherTeacher->id, '2026-10-01', '18:00', '20:00', modality: 'virtual');

        $this->assertArrayHasKey('classroom_id', $asPresencial);
        $this->assertSame([], $asVirtual);
    }

    public function test_virtual_class_still_blocks_the_same_teacher(): void
    {
        $session = ClassSession::factory()->create(['date' => '2026-10-01', 'start_time' => '18:00', 'end_time' => '20:00', 'modality' => 'virtual']);

        $conflicts = app(CheckClassSessionConflicts::class)
            ->handle($session->classroom_id, $session->teacher_id, '2026-10-01', '19:00', '21:00', modality: 'virtual');

        $this->assertArrayHasKey('teacher_id', $conflicts);
    }

    public function test_sessions_generated_from_a_virtual_schedule_are_virtual(): void
    {
        $schedule = ClassSchedule::factory()->create([
            'modality' => 'virtual',
            'days_of_week' => [1],
            'start_date' => '2026-09-28',
            'end_date' => '2026-10-11',
        ]);

        app(GenerateClassSessionsFromSchedule::class)->handle($schedule);

        $this->assertSame(2, $schedule->classSessions()->where('modality', 'virtual')->count());
    }
}
