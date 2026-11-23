<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Level;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Cada docente tiene los niveles globales que dicta; los docentes se
 * filtran por nivel y al programar una clase se proponen los del nivel.
 */
class TeacherLevelTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private Level $standard;

    private Level $intensive;

    private Level $elementary2;

    protected function setUp(): void
    {
        parent::setUp();

        $course = Course::factory()->create(['name' => 'INGLES']);
        $this->standard = Level::factory()->create(['course_id' => $course->id, 'name' => 'Elementary 1 Standard']);
        $this->intensive = Level::factory()->create(['course_id' => $course->id, 'name' => 'Elementary 1 Intensivo']);
        $this->elementary2 = Level::factory()->create(['course_id' => $course->id, 'name' => 'Elementary 2 Standard']);
    }

    private function key(Level $level): string
    {
        return $level->globalLevelKey();
    }

    public function test_checking_a_global_level_assigns_every_intensity(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('teachers.store'), [
                'code' => 'DOC-1',
                'name' => 'Laura Pérez',
                'status' => 'activo',
                'global_levels' => [$this->key($this->standard)],
            ])
            ->assertSessionHasNoErrors();

        $teacher = Teacher::query()->where('code', 'DOC-1')->sole();
        $this->assertEqualsCanonicalizing([$this->standard->id, $this->intensive->id], $teacher->levels()->pluck('levels.id')->all());
    }

    public function test_levels_can_be_changed_when_editing_and_invalid_levels_are_rejected(): void
    {
        $admin = $this->adminUser();
        $teacher = Teacher::factory()->create();
        $teacher->levels()->sync([$this->standard->id, $this->intensive->id]);
        $data = $teacher->only(['code', 'name', 'status']);

        $this->actingAs($admin)
            ->get(route('teachers.edit', $teacher))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('teacher.global_levels', [$this->key($this->standard)])
                ->has('globalLevels', 2)
            );

        $this->actingAs($admin)
            ->put(route('teachers.update', $teacher), [...$data, 'global_levels' => [$this->key($this->elementary2)]])
            ->assertSessionHasNoErrors();
        $this->assertSame([$this->elementary2->id], $teacher->levels()->pluck('levels.id')->all());

        $this->actingAs($admin)
            ->put(route('teachers.update', $teacher), [...$data, 'global_levels' => ['999|Inventado']])
            ->assertSessionHasErrors('global_levels.0');
    }

    public function test_teachers_can_be_filtered_by_level(): void
    {
        $elementary1Teacher = Teacher::factory()->create(['name' => 'Ana']);
        $elementary1Teacher->levels()->sync([$this->standard->id, $this->intensive->id]);
        $elementary2Teacher = Teacher::factory()->create(['name' => 'Beto']);
        $elementary2Teacher->levels()->sync([$this->elementary2->id]);
        Teacher::factory()->create(['name' => 'Carla']);

        $this->actingAs($this->adminUser())
            ->get(route('teachers.index', ['level' => $this->key($this->intensive)]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('teachers.data', 1)
                ->where('teachers.data.0.id', $elementary1Teacher->id)
                ->where('teachers.data.0.global_levels', ['Elementary 1'])
            );

        $this->actingAs($this->adminUser())
            ->get(route('teachers.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('teachers.data', 3));
    }

    public function test_class_form_receives_the_levels_of_each_teacher(): void
    {
        $teacher = Teacher::factory()->create();
        $teacher->levels()->sync([$this->standard->id]);

        $this->actingAs($this->coordinadorUser())
            ->get(route('class-sessions.create'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('teachers.0.id', $teacher->id)
                ->where('teachers.0.level_ids', [$this->standard->id])
            );
    }
}
