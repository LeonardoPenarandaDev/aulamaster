<?php

namespace Tests\Feature;

use App\Models\ClassBooking;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * El estudiante se programa en las clases de su nivel; en las presenciales
 * no puede haber más inscritos que la capacidad del aula.
 */
class ClassBookingTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-09-30 12:00:00');
    }

    /**
     * @return array{0: User, 1: Enrollment}
     */
    private function studentWithEnrollment(?int $levelId = null): array
    {
        $user = $this->studentUser();
        $student = Student::factory()->create(['user_id' => $user->id]);
        $enrollment = Enrollment::factory()->create([
            'student_id' => $student->id,
            'start_date' => '2026-09-28',
            ...($levelId ? ['level_id' => $levelId] : []),
        ]);

        return [$user, $enrollment];
    }

    private function sessionFor(Enrollment $enrollment, array $attributes = []): ClassSession
    {
        return ClassSession::factory()->create([
            'level_id' => $enrollment->level_id,
            'date' => '2026-10-01',
            'start_time' => '08:00',
            'end_time' => '10:00',
            ...$attributes,
        ]);
    }

    public function test_student_can_book_a_class_of_their_level(): void
    {
        [$user, $enrollment] = $this->studentWithEnrollment();
        $session = $this->sessionFor($enrollment);

        $this->actingAs($user)
            ->post(route('class-bookings.store', $session))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('class_bookings', ['class_session_id' => $session->id, 'enrollment_id' => $enrollment->id]);
    }

    public function test_booking_twice_keeps_a_single_seat(): void
    {
        [$user, $enrollment] = $this->studentWithEnrollment();
        $session = $this->sessionFor($enrollment);

        $this->actingAs($user)->post(route('class-bookings.store', $session));
        $this->actingAs($user)->post(route('class-bookings.store', $session))->assertSessionHasNoErrors();

        $this->assertSame(1, ClassBooking::query()->count());
    }

    public function test_student_cannot_book_when_the_classroom_is_full(): void
    {
        [$user, $enrollment] = $this->studentWithEnrollment();
        $session = $this->sessionFor($enrollment, ['classroom_id' => Classroom::factory()->create(['capacity' => 2])->id]);
        ClassBooking::factory()->count(2)->create(['class_session_id' => $session->id]);

        $this->actingAs($user)
            ->post(route('class-bookings.store', $session))
            ->assertSessionHasErrors('booking');

        $this->assertSame(2, $session->bookings()->count());
    }

    public function test_virtual_classes_have_no_seat_limit(): void
    {
        [$user, $enrollment] = $this->studentWithEnrollment();
        $session = $this->sessionFor($enrollment, [
            'modality' => 'virtual',
            'classroom_id' => Classroom::factory()->create(['capacity' => 1])->id,
        ]);
        ClassBooking::factory()->create(['class_session_id' => $session->id]);

        $this->actingAs($user)
            ->post(route('class-bookings.store', $session))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $session->bookings()->count());
    }

    public function test_student_cannot_book_classes_of_another_level_cancelled_or_already_started(): void
    {
        [$user, $enrollment] = $this->studentWithEnrollment();

        $otherLevel = ClassSession::factory()->create(['date' => '2026-10-01']);
        $cancelled = $this->sessionFor($enrollment, ['status' => 'cancelada']);
        $started = $this->sessionFor($enrollment, ['date' => '2026-09-30', 'start_time' => '11:00', 'end_time' => '13:00']);

        foreach ([$otherLevel, $cancelled, $started] as $session) {
            $this->actingAs($user)
                ->post(route('class-bookings.store', $session))
                ->assertSessionHasErrors('booking');
        }

        $this->assertSame(0, ClassBooking::query()->count());
    }

    public function test_student_cannot_book_two_classes_at_the_same_time(): void
    {
        [$user, $enrollment] = $this->studentWithEnrollment();
        $first = $this->sessionFor($enrollment);
        $overlapping = $this->sessionFor($enrollment, ['start_time' => '09:00', 'end_time' => '11:00']);
        $later = $this->sessionFor($enrollment, ['start_time' => '10:00', 'end_time' => '12:00']);

        $this->actingAs($user)->post(route('class-bookings.store', $first))->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('class-bookings.store', $overlapping))->assertSessionHasErrors('booking');
        $this->actingAs($user)->post(route('class-bookings.store', $later))->assertSessionHasNoErrors();
    }

    public function test_student_can_cancel_their_seat_before_the_class_starts(): void
    {
        [$user, $enrollment] = $this->studentWithEnrollment();
        $session = $this->sessionFor($enrollment);
        ClassBooking::factory()->create(['class_session_id' => $session->id, 'enrollment_id' => $enrollment->id]);

        $this->actingAs($user)
            ->delete(route('class-bookings.destroy', $session))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, ClassBooking::query()->count());
    }

    public function test_student_cannot_cancel_once_the_class_started(): void
    {
        [$user, $enrollment] = $this->studentWithEnrollment();
        $session = $this->sessionFor($enrollment, ['date' => '2026-09-30', 'start_time' => '11:00', 'end_time' => '13:00']);
        ClassBooking::factory()->create(['class_session_id' => $session->id, 'enrollment_id' => $enrollment->id]);

        $this->actingAs($user)
            ->delete(route('class-bookings.destroy', $session))
            ->assertSessionHasErrors('booking');

        $this->assertSame(1, ClassBooking::query()->count());
    }

    public function test_schedule_page_shows_seats_and_whether_the_student_is_booked(): void
    {
        [$user, $enrollment] = $this->studentWithEnrollment();
        $full = $this->sessionFor($enrollment, ['classroom_id' => Classroom::factory()->create(['capacity' => 1])->id]);
        ClassBooking::factory()->create(['class_session_id' => $full->id]);
        $booked = $this->sessionFor($enrollment, [
            'start_time' => '14:00',
            'end_time' => '16:00',
            'classroom_id' => Classroom::factory()->create(['capacity' => 15])->id,
        ]);
        ClassBooking::factory()->create(['class_session_id' => $booked->id, 'enrollment_id' => $enrollment->id]);

        $this->actingAs($user)
            ->get(route('student-schedule.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sessions.0.id', $full->id)
                ->where('sessions.0.available_seats', 0)
                ->where('sessions.0.can_book', false)
                ->where('sessions.0.is_booked', false)
                ->where('sessions.1.id', $booked->id)
                ->where('sessions.1.capacity', 15)
                ->where('sessions.1.available_seats', 14)
                ->where('sessions.1.is_booked', true)
            );
    }

    public function test_only_students_can_book(): void
    {
        $session = ClassSession::factory()->create(['date' => '2026-10-01']);

        $this->actingAs($this->adminUser())->post(route('class-bookings.store', $session))->assertForbidden();
        $this->actingAs($this->teacherUser())->post(route('class-bookings.store', $session))->assertForbidden();
    }

    public function test_attendance_roster_lists_booked_students_first(): void
    {
        [, $enrollment] = $this->studentWithEnrollment();
        [, $bookedEnrollment] = $this->studentWithEnrollment($enrollment->level_id);
        $session = $this->sessionFor($enrollment);
        ClassBooking::factory()->create(['class_session_id' => $session->id, 'enrollment_id' => $bookedEnrollment->id]);

        $this->actingAs($this->adminUser())
            ->get(route('attendance.create', $session))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('roster.0.enrollment_id', $bookedEnrollment->id)
                ->where('roster.0.is_booked', true)
                ->where('roster.1.is_booked', false)
            );
    }
}
