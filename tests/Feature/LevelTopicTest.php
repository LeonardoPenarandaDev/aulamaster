<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\LevelTopic;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Cada nivel global (p. ej. "Elementary 1") tiene sus temas, compartidos
 * por sus intensidades, y cada clase programada puede llevar uno.
 */
class LevelTopicTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private Course $course;

    private Level $standard;

    private Level $intensive;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::factory()->create();
        $this->standard = Level::factory()->create(['course_id' => $this->course->id, 'name' => 'Elementary 1 Standard', 'code' => 'E1-STD']);
        $this->intensive = Level::factory()->create(['course_id' => $this->course->id, 'name' => 'Elementary 1 Intensivo', 'code' => 'E1-INT']);
    }

    private function topic(string $name, int $position = 1, ?Level $level = null): LevelTopic
    {
        $level ??= $this->standard;

        return LevelTopic::factory()->create([
            'course_id' => $level->course_id,
            'class_group' => $level->class_group,
            'name' => $name,
            'position' => $position,
        ]);
    }

    public function test_topics_added_from_one_intensity_are_shared_with_the_others(): void
    {
        $coordinador = $this->coordinadorUser();

        $this->actingAs($coordinador)->post(route('level-topics.store', $this->standard), ['name' => 'Verb to be'])->assertSessionHasNoErrors();
        $this->actingAs($coordinador)->post(route('level-topics.store', $this->standard), ['name' => 'Present simple']);

        $this->actingAs($coordinador)
            ->get(route('levels.edit', $this->intensive))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('topics', 2)
                ->where('topics.0.name', 'Verb to be')
                ->where('topics.1.name', 'Present simple')
            );
    }

    public function test_topics_can_be_reordered_renamed_and_deleted(): void
    {
        $coordinador = $this->coordinadorUser();
        $first = $this->topic('Verb to be', 1);
        $second = $this->topic('Present simple', 2);
        $session = ClassSession::factory()->create(['level_id' => $this->standard->id, 'level_topic_id' => $first->id]);

        $this->actingAs($coordinador)->post(route('level-topics.move', [$this->standard, $second]), ['direction' => 'up']);
        $this->assertSame(1, $second->fresh()->position);
        $this->assertSame(2, $first->fresh()->position);

        $this->actingAs($coordinador)->put(route('level-topics.update', [$this->standard, $first]), ['name' => 'To be']);
        $this->assertSame('To be', $first->fresh()->name);

        $this->actingAs($coordinador)->delete(route('level-topics.destroy', [$this->standard, $first]));
        $this->assertModelMissing($first);
        $this->assertNull($session->fresh()->level_topic_id);
    }

    public function test_topic_of_another_level_cannot_be_managed_through_this_level(): void
    {
        $otherLevel = Level::factory()->create(['course_id' => $this->course->id, 'name' => 'Elementary 2 Standard']);
        $topic = $this->topic('Past simple', 1, $otherLevel);

        $this->actingAs($this->coordinadorUser())
            ->delete(route('level-topics.destroy', [$this->standard, $topic]))
            ->assertNotFound();
    }

    public function test_class_can_be_scheduled_with_a_topic_of_its_level_group_only(): void
    {
        $topic = $this->topic('Verb to be');
        $foreignTopic = $this->topic('Past simple', 1, Level::factory()->create(['course_id' => $this->course->id, 'name' => 'Elementary 2 Standard']));
        $data = [
            'level_id' => $this->intensive->id,
            'teacher_id' => Teacher::factory()->create()->id,
            'classroom_id' => Classroom::factory()->create()->id,
            'date' => '2026-12-01',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'programada',
        ];

        $this->actingAs($this->coordinadorUser())
            ->post(route('class-sessions.store'), [...$data, 'level_topic_id' => $foreignTopic->id])
            ->assertSessionHasErrors('level_topic_id');

        $this->actingAs($this->coordinadorUser())
            ->post(route('class-sessions.store'), [...$data, 'level_topic_id' => $topic->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($topic->id, ClassSession::query()->sole()->level_topic_id);
    }

    public function test_student_sees_the_topic_of_each_class_and_which_ones_they_already_saw(): void
    {
        $this->travelTo('2026-09-30 12:00:00');

        $user = $this->studentUser();
        $student = Student::factory()->create(['user_id' => $user->id]);
        $enrollment = Enrollment::factory()->create(['student_id' => $student->id, 'level_id' => $this->standard->id, 'start_date' => '2026-09-28']);
        $seen = $this->topic('Verb to be', 1);
        $pending = $this->topic('Present simple', 2);

        $past = ClassSession::factory()->create(['level_id' => $this->intensive->id, 'level_topic_id' => $seen->id, 'date' => '2026-09-29', 'status' => 'dictada']);
        Attendance::factory()->create(['class_session_id' => $past->id, 'enrollment_id' => $enrollment->id, 'class_date' => '2026-09-29', 'status' => 'presente']);
        ClassSession::factory()->create(['level_id' => $this->intensive->id, 'level_topic_id' => $seen->id, 'date' => '2026-10-01']);
        ClassSession::factory()->create(['level_id' => $this->standard->id, 'level_topic_id' => $pending->id, 'date' => '2026-10-02']);

        $this->actingAs($user)
            ->get(route('student-schedule.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('sessions', 2)
                ->where('sessions.0.topic', 'Verb to be')
                ->where('sessions.0.topic_seen', true)
                ->where('sessions.1.topic', 'Present simple')
                ->where('sessions.1.topic_seen', false)
            );
    }

    public function test_import_assigns_the_topic_by_name(): void
    {
        $topic = $this->topic('Verb to be');
        Classroom::factory()->create(['name' => 'Aula 1']);
        $file = UploadedFile::fake()->createWithContent('horarios.csv', "dia,hora_inicio,hora_fin,nivel,aula,modalidad,docente,enlace_virtual,notas,tema\n"
            ."lunes,08:00,10:00,E1-INT,Aula 1,presencial,,,,verb to be\n"
            ."martes,08:00,10:00,E1-INT,Aula 1,presencial,,,,Tema inventado\n");

        $this->actingAs($this->coordinadorUser())
            ->post(route('class-sessions.import.preview'), ['file' => $file, 'week' => '2026-10-12', 'repeat_weeks' => 1])
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('rows.0.data.level_topic_id', $topic->id)
                ->where('rows.0.data.topic', 'Verb to be')
                ->where('rows.1.status', 'error')
            );
    }

    public function test_topics_follow_the_level_when_its_group_is_renamed(): void
    {
        $level = Level::factory()->create(['course_id' => $this->course->id, 'name' => 'Kids 1', 'code' => 'K1']);
        $topic = $this->topic('Colors', 1, $level);

        $this->actingAs($this->adminUser())
            ->put(route('levels.update', $level), [
                ...$level->only(['course_id', 'name', 'code', 'required_hours', 'minimum_grade', 'price', 'status']),
                'class_group' => 'Kids Uno',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Kids Uno', $topic->fresh()->class_group);
    }
}
