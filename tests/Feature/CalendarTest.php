<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Student;
use App\Models\Teacher;
use App\Notifications\ClassRescheduledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Parte 10 del plan de mejoras: calendario por rol, cargado por rango de
 * fechas, con el color de cada nivel.
 */
class CalendarTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_student_sees_the_classes_of_their_level_with_attendance_state(): void
    {
        $user = $this->studentUser();
        $student = Student::factory()->create(['user_id' => $user->id]);
        $level = Level::factory()->create(['color' => '#FCE7F3']);
        $enrollment = Enrollment::factory()->create(['student_id' => $student->id, 'level_id' => $level->id, 'status' => 'activa']);

        $past = ClassSession::factory()->create(['level_id' => $level->id, 'date' => now()->subDays(2)->toDateString()]);
        $upcoming = ClassSession::factory()->create(['level_id' => $level->id, 'date' => now()->addDays(2)->toDateString()]);
        ClassSession::factory()->create(['date' => now()->toDateString()]);
        Attendance::factory()->create(['class_session_id' => $past->id, 'enrollment_id' => $enrollment->id, 'status' => 'presente']);

        $this->actingAs($user)
            ->get(route('calendar.index', ['from' => now()->subWeek()->toDateString(), 'to' => now()->addWeek()->toDateString()]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Calendar/Index')
                ->has('sessions', 2)
                ->where('sessions.0.id', $past->id)
                ->where('sessions.0.color', '#FCE7F3')
                ->where('sessions.0.student_status', 'asistio')
                ->where('sessions.0.teacher', null)
                ->where('sessions.1.id', $upcoming->id)
                ->where('sessions.1.student_status', 'proxima')
                ->where('sessions.1.can.edit', false)
                ->where('filterOptions', null)
            );
    }

    public function test_teacher_sees_only_their_classes_with_attendance_actions(): void
    {
        $user = $this->teacherUser();
        $teacher = Teacher::factory()->create(['user_id' => $user->id]);
        $own = ClassSession::factory()->create(['teacher_id' => $teacher->id, 'date' => now()->toDateString()]);
        ClassSession::factory()->create(['date' => now()->toDateString()]);

        $this->actingAs($user)
            ->get(route('calendar.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('sessions', 1)
                ->where('sessions.0.id', $own->id)
                ->where('sessions.0.can.take_attendance', true)
                ->where('sessions.0.can.materials', true)
            );
    }

    public function test_coordinator_sees_all_classes_and_can_filter(): void
    {
        $level = Level::factory()->create();
        ClassSession::factory()->create(['level_id' => $level->id, 'date' => now()->toDateString()]);
        ClassSession::factory()->create(['date' => now()->toDateString()]);
        $coordinador = $this->coordinadorUser();

        $this->actingAs($coordinador)
            ->get(route('calendar.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('sessions', 2)->where('canCreate', true));

        $this->actingAs($coordinador)
            ->get(route('calendar.index', ['level_id' => $level->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('sessions', 1)->where('sessions.0.can.edit', true));
    }

    public function test_range_is_limited_and_other_roles_have_no_calendar(): void
    {
        $this->actingAs($this->adminUser())
            ->get(route('calendar.index', ['from' => '2026-01-01', 'to' => '2026-12-31']))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('range.to', '2026-03-04'));

        $this->actingAs($this->cajeroUser())->get(route('calendar.index'))->assertForbidden();
    }

    public function test_rescheduling_from_the_calendar_notifies_and_returns_to_it(): void
    {
        Notification::fake();
        $session = ClassSession::factory()->create(['date' => now()->addDays(3)->toDateString(), 'start_time' => '08:00', 'end_time' => '10:00']);
        $newDate = now()->addDays(4)->toDateString();

        $this->actingAs($this->adminUser())
            ->put(route('class-sessions.update', $session), [
                ...$session->only(['level_id', 'teacher_id', 'classroom_id', 'status']),
                'modality' => 'presencial',
                'date' => $newDate,
                'start_time' => '14:00',
                'end_time' => '16:00',
                'return_to' => 'calendar',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('calendar.index', ['date' => $newDate]));

        Notification::assertSentOnDemand(ClassRescheduledNotification::class);
    }
}
