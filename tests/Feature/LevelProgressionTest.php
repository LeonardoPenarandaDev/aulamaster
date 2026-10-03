<?php

namespace Tests\Feature;

use App\Actions\Evaluations\EvaluateLevelCompletion;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\EvaluationResult;
use App\Models\Level;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\NextLevelEnrollmentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Parte 5 del plan de mejoras: los niveles de un curso forman una ruta.
 * Al aprobar uno, el estudiante queda matriculado en el siguiente como
 * pendiente, y no se puede saltar un nivel sin que el admin lo autorice.
 */
class LevelProgressionTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    /**
     * @return array{0: Level, 1: Level}
     */
    private function twoLevelRoute(): array
    {
        $course = Course::factory()->create();
        $second = Level::factory()->create(['course_id' => $course->id, 'name' => 'Elementary 2', 'price' => 400000, 'weekly_hours' => 6]);
        $first = Level::factory()->create(['course_id' => $course->id, 'name' => 'Elementary 1', 'next_level_id' => $second->id]);

        return [$first, $second];
    }

    private function approveAllEvaluations(Enrollment $enrollment): void
    {
        $evaluation = Evaluation::factory()->create(['level_id' => $enrollment->level_id, 'minimum_grade' => 70, 'status' => 'activo']);

        EvaluationResult::factory()->create([
            'evaluation_id' => $evaluation->id,
            'enrollment_id' => $enrollment->id,
            'teacher_id' => Teacher::factory()->create()->id,
            'grade' => 90,
            'result' => 'aprobado',
            'registered_by_id' => User::factory()->create()->id,
        ]);

        app(EvaluateLevelCompletion::class)->handle($enrollment->fresh());
    }

    public function test_approving_a_level_creates_the_next_enrollment_as_pending(): void
    {
        Notification::fake();
        [$first, $second] = $this->twoLevelRoute();
        $enrollment = Enrollment::factory()->create([
            'level_id' => $first->id,
            'status' => 'activa',
            'required_hours' => 10,
            'accumulated_hours' => 10,
        ]);

        $this->approveAllEvaluations($enrollment);

        $this->assertSame('aprobada', $enrollment->fresh()->status);

        $next = Enrollment::query()->where('previous_enrollment_id', $enrollment->id)->firstOrFail();
        $this->assertSame($second->id, $next->level_id);
        $this->assertSame($enrollment->student_id, $next->student_id);
        $this->assertSame('pendiente', $next->status);
        $this->assertEquals(400000, $next->final_price);
        $this->assertEquals(6, $next->weekly_hours);

        Notification::assertSentOnDemand(NextLevelEnrollmentNotification::class);
    }

    public function test_no_next_enrollment_is_created_for_the_last_level_or_twice(): void
    {
        Notification::fake();
        [$first, $second] = $this->twoLevelRoute();
        $student = Student::factory()->create();

        $last = Enrollment::factory()->create([
            'student_id' => $student->id, 'level_id' => $second->id, 'status' => 'activa', 'required_hours' => 10, 'accumulated_hours' => 10,
        ]);
        $this->approveAllEvaluations($last);
        $this->assertSame(1, Enrollment::query()->where('student_id', $student->id)->count());

        $enrollment = Enrollment::factory()->create(['level_id' => $first->id, 'status' => 'aprobada']);
        Enrollment::factory()->create(['student_id' => $enrollment->student_id, 'level_id' => $second->id, 'status' => 'pendiente']);

        $this->actingAs($this->adminUser())
            ->post(route('enrollments.promote', $enrollment))
            ->assertSessionHas('error');

        $this->assertSame(2, Enrollment::query()->where('student_id', $enrollment->student_id)->count());
    }

    public function test_admin_can_promote_manually(): void
    {
        Notification::fake();
        [$first, $second] = $this->twoLevelRoute();
        $enrollment = Enrollment::factory()->create(['level_id' => $first->id, 'status' => 'aprobada']);

        $response = $this->actingAs($this->adminUser())->post(route('enrollments.promote', $enrollment));

        $next = Enrollment::query()->where('previous_enrollment_id', $enrollment->id)->firstOrFail();
        $response->assertRedirect(route('enrollments.edit', $next));
        $this->assertSame($second->id, $next->level_id);
        $this->assertSame('pendiente', $next->status);
    }

    public function test_cashier_cannot_promote_manually(): void
    {
        [$first] = $this->twoLevelRoute();
        $enrollment = Enrollment::factory()->create(['level_id' => $first->id, 'status' => 'aprobada']);

        $this->actingAs($this->cajeroUser())->post(route('enrollments.promote', $enrollment))->assertForbidden();
    }

    public function test_cannot_enroll_in_a_level_without_approving_the_previous_one(): void
    {
        [$first, $second] = $this->twoLevelRoute();
        $student = Student::factory()->create();

        $this->actingAs($this->cajeroUser())
            ->post(route('enrollments.store'), $this->enrollmentData($student, $second))
            ->assertSessionHasErrors('level_id');

        Enrollment::factory()->create(['student_id' => $student->id, 'level_id' => $first->id, 'status' => 'aprobada']);

        $this->actingAs($this->cajeroUser())
            ->post(route('enrollments.store'), $this->enrollmentData($student, $second))
            ->assertSessionHasNoErrors();
    }

    public function test_only_the_admin_can_skip_the_prerequisite_and_it_is_audited(): void
    {
        [, $second] = $this->twoLevelRoute();
        $student = Student::factory()->create();

        $this->actingAs($this->cajeroUser())
            ->post(route('enrollments.store'), $this->enrollmentData($student, $second, ['skip_prerequisite' => true]))
            ->assertSessionHasErrors('level_id');

        $this->actingAs($this->adminUser())
            ->post(route('enrollments.store'), $this->enrollmentData($student, $second, ['skip_prerequisite' => true]))
            ->assertSessionHasNoErrors();

        $enrollment = Enrollment::query()->where('student_id', $student->id)->firstOrFail();
        $this->assertTrue($enrollment->prerequisite_waived);

        $audit = AuditLog::query()->where('auditable_type', Enrollment::class)->where('auditable_id', $enrollment->id)->firstOrFail();
        $this->assertTrue((bool) $audit->new_values['prerequisite_waived']);
    }

    public function test_next_level_must_be_in_the_same_course_and_cannot_form_a_cycle(): void
    {
        $admin = $this->adminUser();
        [$first, $second] = $this->twoLevelRoute();
        $otherCourseLevel = Level::factory()->create();

        $this->actingAs($admin)
            ->put(route('levels.update', $second), $this->levelData($second, ['next_level_id' => $otherCourseLevel->id]))
            ->assertSessionHasErrors('next_level_id');

        $this->actingAs($admin)
            ->put(route('levels.update', $second), $this->levelData($second, ['next_level_id' => $second->id]))
            ->assertSessionHasErrors('next_level_id');

        $this->actingAs($admin)
            ->put(route('levels.update', $second), $this->levelData($second, ['next_level_id' => $first->id]))
            ->assertSessionHasErrors('next_level_id');

        $this->assertNull($second->fresh()->next_level_id);
    }

    public function test_levels_listing_shows_the_route_in_order(): void
    {
        $course = Course::factory()->create(['name' => 'Inglés']);
        $third = Level::factory()->create(['course_id' => $course->id, 'name' => 'Elementary 3']);
        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('levels.store'), $this->levelData(new Level, [
            'course_id' => $course->id, 'name' => 'Elementary 2', 'code' => 'EL-2', 'next_level_id' => $third->id,
        ]))->assertSessionHasNoErrors();
        $second = Level::query()->where('code', 'EL-2')->firstOrFail();

        $this->actingAs($admin)->post(route('levels.store'), $this->levelData(new Level, [
            'course_id' => $course->id, 'name' => 'Elementary 1', 'code' => 'EL-1', 'next_level_id' => $second->id,
        ]))->assertSessionHasNoErrors();

        $this->assertSame([1, 2, 3], Level::query()->where('course_id', $course->id)->orderBy('position')->pluck('position')->all());

        $this->actingAs($admin)->get(route('levels.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('routes.0.course', 'Inglés')
                ->where('routes.0.paths.0.0.name', 'Elementary 1')
                ->where('routes.0.paths.0.1.name', 'Elementary 2')
                ->where('routes.0.paths.0.2.name', 'Elementary 3')
            );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function enrollmentData(Student $student, Level $level, array $overrides = []): array
    {
        return array_merge([
            'student_id' => $student->id,
            'level_id' => $level->id,
            'enrolled_at' => now()->toDateString(),
            'start_date' => now()->toDateString(),
            'status' => 'activa',
            'required_hours' => 100,
            'weekly_hours' => 6,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function levelData(Level $level, array $overrides = []): array
    {
        return array_merge([
            'course_id' => $level->course_id,
            'name' => $level->name,
            'code' => $level->code,
            'required_hours' => 100,
            'minimum_grade' => 70,
            'price' => 300000,
            'status' => 'activo',
        ], $overrides);
    }
}
