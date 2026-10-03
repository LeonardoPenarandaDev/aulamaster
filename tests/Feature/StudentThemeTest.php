<?php

namespace Tests\Feature;

use App\Actions\Evaluations\PromoteToNextLevel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Parte 4 del plan de mejoras: el fondo del portal del estudiante toma el
 * color de su nivel actual (la matrícula más reciente no cancelada).
 */
class StudentThemeTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    /**
     * @return array{0: User, 1: Student}
     */
    private function studentWithAccount(): array
    {
        $user = $this->studentUser();
        $student = Student::factory()->create(['user_id' => $user->id]);

        return [$user, $student];
    }

    public function test_student_without_enrollments_keeps_the_gray_background(): void
    {
        [$user] = $this->studentWithAccount();

        $this->actingAs($user)->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('studentTheme', null));
    }

    public function test_theme_uses_the_color_of_the_current_level(): void
    {
        [$user, $student] = $this->studentWithAccount();
        $level = Level::factory()->create(['color' => '#FEF9C3']);
        Enrollment::factory()->create(['student_id' => $student->id, 'level_id' => $level->id, 'status' => 'activa']);

        $this->actingAs($user)->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('studentTheme.color', '#FEF9C3'));
    }

    public function test_cancelled_enrollments_are_ignored(): void
    {
        [$user, $student] = $this->studentWithAccount();
        $active = Level::factory()->create(['color' => '#DCFCE7']);
        $cancelled = Level::factory()->create(['color' => '#FCE7F3']);
        Enrollment::factory()->create(['student_id' => $student->id, 'level_id' => $active->id, 'status' => 'activa', 'enrolled_at' => now()->subMonth()]);
        Enrollment::factory()->create(['student_id' => $student->id, 'level_id' => $cancelled->id, 'status' => 'cancelada', 'enrolled_at' => now()]);

        $this->actingAs($user)->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('studentTheme.color', '#DCFCE7'));
    }

    public function test_color_changes_as_soon_as_the_next_level_is_created_after_approval(): void
    {
        Notification::fake();
        [$user, $student] = $this->studentWithAccount();
        $course = Course::factory()->create();
        $second = Level::factory()->create(['course_id' => $course->id, 'color' => '#EDE9FE']);
        $first = Level::factory()->create(['course_id' => $course->id, 'color' => '#DBEAFE', 'next_level_id' => $second->id]);
        $enrollment = Enrollment::factory()->create(['student_id' => $student->id, 'level_id' => $first->id, 'status' => 'aprobada']);

        app(PromoteToNextLevel::class)->handle($enrollment);

        $this->actingAs($user)->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('studentTheme.color', '#EDE9FE')
                ->where('studentData.level_path.0.state', 'aprobado')
                ->where('studentData.level_path.1.state', 'actual')
                ->where('studentData.level_path.1.color', '#EDE9FE')
            );
    }

    public function test_staff_users_have_no_student_theme(): void
    {
        $this->actingAs($this->adminUser())->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('studentTheme', null));
    }
}
