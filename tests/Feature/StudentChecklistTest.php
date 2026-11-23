<?php

namespace Tests\Feature;

use App\Models\ChecklistItem;
use App\Models\ContractSignature;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentChecklistMark;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Checklist de la gestión académica: el administrador define los requisitos
 * y el personal los marca en cada estudiante (los automáticos los revisa la
 * plataforma).
 */
class StudentChecklistTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_admin_creates_edits_orders_and_deletes_requirements(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('checklist-items.store'), ['name' => 'Kit de inicio entregado'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('checklist-items.store'), ['name' => 'Contratos', 'automatic_check' => 'contratos_firmados'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('checklist-items.store'), ['name' => 'X', 'automatic_check' => 'inventado'])->assertSessionHasErrors('automatic_check');

        [$kit, $contracts] = ChecklistItem::query()->orderBy('position')->get()->all();
        $this->assertNull($kit->automatic_check);
        $this->assertSame('contratos_firmados', $contracts->automatic_check);

        $this->actingAs($admin)->post(route('checklist-items.move', $contracts), ['direction' => 'up']);
        $this->assertSame(1, $contracts->fresh()->position);

        $this->actingAs($admin)->put(route('checklist-items.update', $kit), ['name' => 'Kit entregado', 'is_active' => false]);
        $this->assertSame('Kit entregado', $kit->fresh()->name);
        $this->assertFalse($kit->fresh()->is_active);

        $this->actingAs($admin)->delete(route('checklist-items.destroy', $kit));
        $this->assertModelMissing($kit);

        $this->actingAs($admin)
            ->get(route('checklist-items.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('ChecklistItems/Index')->has('items', 1));
    }

    public function test_only_admin_manages_the_requirements(): void
    {
        foreach ([$this->secretariaUser(), $this->coordinadorUser(), $this->cajeroUser()] as $user) {
            $this->actingAs($user)->get(route('checklist-items.index'))->assertForbidden();
            $this->actingAs($user)->post(route('checklist-items.store'), ['name' => 'X'])->assertForbidden();
        }
    }

    public function test_staff_marks_manual_requirements_and_the_student_becomes_complete(): void
    {
        $secretaria = $this->secretariaUser();
        $student = Student::factory()->create();
        $kit = ChecklistItem::factory()->create(['name' => 'Kit de inicio entregado', 'position' => 1]);
        $card = ChecklistItem::factory()->create(['name' => 'Carné entregado', 'position' => 2]);

        $this->actingAs($secretaria)
            ->get(route('students.edit', $student))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('checklist.done', 0)
                ->where('checklist.total', 2)
                ->where('checklist.complete', false)
            );

        $this->actingAs($secretaria)->post(route('students.checklist.check', [$student, $kit]))->assertSessionHasNoErrors();
        $this->actingAs($secretaria)->post(route('students.checklist.check', [$student, $card]));
        $this->actingAs($secretaria)->post(route('students.checklist.check', [$student, $card]));

        $this->assertSame(2, StudentChecklistMark::query()->count());
        $this->assertSame($secretaria->id, StudentChecklistMark::query()->first()->checked_by_id);

        $this->actingAs($secretaria)
            ->get(route('students.edit', $student))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('checklist.complete', true)
                ->where('checklist.items.0.name', 'Kit de inicio entregado')
                ->where('checklist.items.0.checked_by', $secretaria->name)
            );

        $this->actingAs($secretaria)->delete(route('students.checklist.uncheck', [$student, $kit]));
        $this->assertSame(1, StudentChecklistMark::query()->count());
    }

    public function test_automatic_requirements_follow_the_platform_data(): void
    {
        $admin = $this->adminUser();
        $student = Student::factory()->create(['photo_path' => null, 'user_id' => null]);
        foreach (array_keys(ChecklistItem::AUTOMATIC_CHECKS) as $position => $check) {
            ChecklistItem::factory()->automatic($check)->create(['name' => $check, 'position' => $position + 1]);
        }

        $done = fn () => collect($this->actingAs($admin)->get(route('students.edit', $student))->viewData('page')['props']['checklist']['items'])
            ->pluck('done', 'name')
            ->all();

        $this->assertSame(['contratos_firmados' => false, 'foto_perfil' => false, 'acceso_plataforma' => false, 'matricula_pagada' => false], $done());

        $signature = ContractSignature::factory()->create(['student_id' => $student->id, 'status' => 'pendiente']);
        $this->assertFalse($done()['contratos_firmados']);
        $signature->update(['status' => 'firmado']);

        $student->forceFill(['photo_path' => 'students/photos/ana.jpg', 'user_id' => $this->studentUser()->id])->save();
        Payment::factory()->create(['student_id' => $student->id, 'type' => 'matricula', 'status' => 'pagado']);

        $this->assertSame(['contratos_firmados' => true, 'foto_perfil' => true, 'acceso_plataforma' => true, 'matricula_pagada' => true], $done());

        $this->actingAs($admin)
            ->post(route('students.checklist.check', [$student, ChecklistItem::query()->first()]))
            ->assertSessionHas('error');
        $this->assertSame(0, StudentChecklistMark::query()->count());
    }

    public function test_student_list_shows_progress_and_filters_incomplete_ones(): void
    {
        $admin = $this->adminUser();
        $item = ChecklistItem::factory()->create();
        $complete = Student::factory()->create(['name' => 'Ana']);
        $incomplete = Student::factory()->create(['name' => 'Beto']);
        StudentChecklistMark::query()->create(['student_id' => $complete->id, 'checklist_item_id' => $item->id]);

        $this->actingAs($admin)
            ->get(route('students.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('students.data.0.checklist.complete', true)
                ->where('students.data.1.checklist.done', 0)
                ->where('students.data.1.checklist.total', 1)
            );

        $this->actingAs($admin)
            ->get(route('students.index', ['checklist' => 'incompleto']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('students.data', 1)
                ->where('students.data.0.id', $incomplete->id)
            );
    }
}
