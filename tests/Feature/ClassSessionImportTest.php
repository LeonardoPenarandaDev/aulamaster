<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\Level;
use App\Models\Teacher;
use App\Notifications\ClassesAssignedNotification;
use App\Notifications\UnassignedClassAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Parte 11 del plan de mejoras: programar la semana de clases desde CSV o
 * Excel, con vista previa, choques y clases sin docente.
 */
class ClassSessionImportTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private Level $level;

    private Classroom $classroom;

    private Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->level = Level::factory()->create(['code' => 'ELE-1']);
        $this->classroom = Classroom::factory()->create(['name' => 'Aula 1']);
        $this->teacher = Teacher::factory()->create(['email' => 'profe@instituto.test']);
    }

    private function csv(string $rows): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('horarios.csv', "dia,hora_inicio,hora_fin,nivel,aula,modalidad,docente,enlace_virtual,notas\n".$rows);
    }

    private function preview(string $rows, int $repeatWeeks = 1, string $week = '2026-10-14')
    {
        return $this->actingAs($this->coordinadorUser())->post(route('class-sessions.import.preview'), [
            'file' => $this->csv($rows),
            'week' => $week,
            'repeat_weeks' => $repeatWeeks,
        ]);
    }

    public function test_preview_classifies_rows_without_saving_anything(): void
    {
        $this->preview(
            "lunes,08:00,10:00,ELE-1,Aula 1,presencial,profe@instituto.test,,\n".
            "miércoles,18:00,20:00,ele-1,aula 1,virtual,,https://meet.google.com/abc,\n".
            "domingo,10:00,09:00,NO-EXISTE,Aula 9,presencial,nadie@x.test,,\n"
        )->assertInertia(fn (AssertableInertia $page) => $page
            ->component('ClassSessions/ImportPreview')
            ->where('week', '2026-10-12')
            ->where('summary.ok', 1)
            ->where('summary.sin_docente', 1)
            ->where('summary.error', 1)
            ->where('summary.sessions', 2)
            ->where('rows.0.dates', ['2026-10-12'])
            ->where('rows.1.dates', ['2026-10-14'])
            ->has('rows.2.errors', 4)
        );

        $this->assertSame(0, ClassSession::query()->count());
    }

    public function test_confirming_creates_the_sessions_for_each_week_and_notifies_teachers(): void
    {
        Notification::fake();
        $coordinador = $this->coordinadorUser();

        $token = $this->actingAs($coordinador)->post(route('class-sessions.import.preview'), [
            'file' => $this->csv("lunes,08:00,10:00,ELE-1,Aula 1,presencial,profe@instituto.test,,\nmartes,08:00,10:00,ELE-1,Aula 1,presencial,,,\n"),
            'week' => '2026-10-12',
            'repeat_weeks' => 3,
        ])->viewData('page')['props']['token'];

        $this->actingAs($coordinador)
            ->post(route('class-sessions.import.store'), ['token' => $token])
            ->assertRedirect(route('calendar.index', ['date' => '2026-10-12']));

        $this->assertSame(6, ClassSession::query()->count());
        $this->assertSame(3, ClassSession::query()->whereNull('teacher_id')->count());
        $this->assertEqualsCanonicalizing(
            ['2026-10-12', '2026-10-19', '2026-10-26'],
            ClassSession::query()->where('teacher_id', $this->teacher->id)->get()->map(fn ($session) => $session->date->toDateString())->all(),
        );
        Notification::assertSentOnDemand(ClassesAssignedNotification::class);

        $this->actingAs($coordinador)->post(route('class-sessions.import.store'), ['token' => $token])->assertStatus(410);
    }

    public function test_conflicts_with_existing_classes_and_within_the_file_are_errors(): void
    {
        ClassSession::factory()->create([
            'classroom_id' => $this->classroom->id,
            'date' => '2026-10-12',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'modality' => 'presencial',
        ]);

        $this->preview(
            "lunes,08:00,10:00,ELE-1,Aula 1,presencial,,,\n".
            "martes,08:00,10:00,ELE-1,Aula 1,presencial,profe@instituto.test,,\n".
            "martes,09:00,11:00,ELE-1,Aula 1,virtual,profe@instituto.test,,\n"
        )->assertInertia(fn (AssertableInertia $page) => $page
            ->where('rows.0.status', 'error')
            ->where('rows.1.status', 'ok')
            ->where('rows.2.status', 'error')
            ->where('rows.2.errors.0', 'Choque de docente con la fila 3 del archivo.')
        );
    }

    public function test_template_and_error_rows_can_be_downloaded(): void
    {
        $coordinador = $this->coordinadorUser();

        $this->actingAs($coordinador)->get(route('class-sessions.import.template'))->assertOk()->assertDownload('plantilla_horarios.xlsx');

        $token = $this->actingAs($coordinador)->post(route('class-sessions.import.preview'), [
            'file' => $this->csv("lunes,25:00,10:00,ELE-1,Aula 1,,,,\n"),
            'week' => '2026-10-12',
            'repeat_weeks' => 1,
        ])->viewData('page')['props']['token'];

        $content = $this->actingAs($coordinador)->get(route('class-sessions.import.errors', $token))->assertOk()->streamedContent();
        $this->assertStringContainsString('Hora no válida', $content);
    }

    public function test_classes_without_teacher_block_attendance_and_alert_48_hours_before(): void
    {
        Notification::fake();
        $admin = $this->adminUser();
        $session = ClassSession::factory()->create(['teacher_id' => null, 'date' => now()->addDay()->toDateString()]);

        $this->actingAs($admin)->get(route('attendance.create', $session))->assertForbidden();

        $this->artisan('app:send-notification-reminders')->assertSuccessful();
        $this->artisan('app:send-notification-reminders')->assertSuccessful();

        Notification::assertSentToTimes($admin, UnassignedClassAlertNotification::class, 1);
    }

    public function test_cashier_cannot_import(): void
    {
        $this->actingAs($this->cajeroUser())->get(route('class-sessions.import.create'))->assertForbidden();
    }
}
