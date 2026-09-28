<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ClassMaterial;
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
 * El profesor comparte material de repaso (enlaces) en su clase y solo lo
 * ven los estudiantes que quedaron presentes en esa clase.
 */
class ClassMaterialTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    /**
     * @return array{0: User, 1: ClassSession}
     */
    private function teacherWithSession(): array
    {
        $user = $this->teacherUser();
        $teacher = Teacher::factory()->create(['user_id' => $user->id]);

        return [$user, ClassSession::factory()->create(['teacher_id' => $teacher->id])];
    }

    /**
     * @return array{0: User, 1: Enrollment}
     */
    private function studentIn(ClassSession $session, ?string $attendanceStatus): array
    {
        $user = $this->studentUser();
        $student = Student::factory()->create(['user_id' => $user->id]);
        $enrollment = Enrollment::factory()->create(['student_id' => $student->id, 'level_id' => $session->level_id]);

        if ($attendanceStatus) {
            Attendance::factory()->create([
                'class_session_id' => $session->id,
                'enrollment_id' => $enrollment->id,
                'status' => $attendanceStatus,
            ]);
        }

        return [$user, $enrollment];
    }

    public function test_teacher_can_share_a_link_in_their_class(): void
    {
        [$user, $session] = $this->teacherWithSession();

        $this->actingAs($user)->get(route('class-materials.index', $session))->assertOk();

        $this->actingAs($user)
            ->post(route('class-materials.store', $session), [
                'title' => 'Video de repaso',
                'url' => 'https://www.youtube.com/watch?v=abc',
                'description' => 'Verbos irregulares',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('class_materials', [
            'class_session_id' => $session->id,
            'title' => 'Video de repaso',
            'url' => 'https://www.youtube.com/watch?v=abc',
            'created_by_id' => $user->id,
        ]);
    }

    public function test_url_must_be_a_valid_web_link(): void
    {
        [$user, $session] = $this->teacherWithSession();

        $this->actingAs($user)
            ->post(route('class-materials.store', $session), ['title' => 'Malo', 'url' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('url');

        $this->assertDatabaseCount('class_materials', 0);
    }

    public function test_teacher_cannot_manage_material_of_another_teachers_class(): void
    {
        [$user] = $this->teacherWithSession();
        $otherSession = ClassSession::factory()->create();
        $material = ClassMaterial::factory()->create(['class_session_id' => $otherSession->id]);

        $this->actingAs($user)->get(route('class-materials.index', $otherSession))->assertForbidden();
        $this->actingAs($user)
            ->post(route('class-materials.store', $otherSession), ['title' => 'X', 'url' => 'https://example.com'])
            ->assertForbidden();
        $this->actingAs($user)->delete(route('class-materials.destroy', $material))->assertForbidden();

        $this->assertModelExists($material);
    }

    public function test_teacher_can_delete_material_from_their_class(): void
    {
        [$user, $session] = $this->teacherWithSession();
        $material = ClassMaterial::factory()->create(['class_session_id' => $session->id]);

        $this->actingAs($user)->delete(route('class-materials.destroy', $material))->assertRedirect();

        $this->assertModelMissing($material);
    }

    public function test_students_cannot_manage_material(): void
    {
        [, $session] = $this->teacherWithSession();
        [$studentUser] = $this->studentIn($session, 'presente');

        $this->actingAs($studentUser)->get(route('class-materials.index', $session))->assertForbidden();
        $this->actingAs($studentUser)
            ->post(route('class-materials.store', $session), ['title' => 'X', 'url' => 'https://example.com'])
            ->assertForbidden();
    }

    public function test_only_students_present_in_the_class_see_its_material(): void
    {
        [, $session] = $this->teacherWithSession();
        ClassMaterial::factory()->create(['class_session_id' => $session->id, 'title' => 'Guía de repaso']);

        [$present] = $this->studentIn($session, 'presente');
        [$absent] = $this->studentIn($session, 'ausente');
        [$excused] = $this->studentIn($session, 'excusado');
        [$noAttendance] = $this->studentIn($session, null);

        $this->actingAs($present)
            ->get(route('student-materials.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('StudentMaterials/Index')
                ->has('sessions', 1)
                ->where('sessions.0.materials.0.title', 'Guía de repaso')
            );

        foreach ([$absent, $excused, $noAttendance] as $user) {
            $this->actingAs($user)
                ->get(route('student-materials.index'))
                ->assertInertia(fn (AssertableInertia $page) => $page->has('sessions', 0));
        }
    }

    public function test_attendance_corrected_to_present_unlocks_the_material(): void
    {
        [, $session] = $this->teacherWithSession();
        ClassMaterial::factory()->create(['class_session_id' => $session->id]);
        [$user, $enrollment] = $this->studentIn($session, 'ausente');

        $attendance = $enrollment->attendances()->first();
        $attendance->corrections()->create([
            'previous_status' => 'ausente',
            'new_status' => 'presente',
            'reason' => 'Llegó tarde',
            'corrected_by_id' => $attendance->registered_by_id,
        ]);

        $this->actingAs($user)
            ->get(route('student-materials.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('sessions', 1));
    }
}
