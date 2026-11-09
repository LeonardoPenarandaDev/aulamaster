<?php

namespace Tests\Feature;

use App\Models\Classroom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * La tabla de aulas se ordena por la columna que se elija.
 */
class ClassroomListTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_classrooms_can_be_sorted_by_any_column(): void
    {
        $small = Classroom::factory()->create(['name' => 'Aula B', 'code' => 'A-02', 'capacity' => 10]);
        $large = Classroom::factory()->create(['name' => 'Aula C', 'code' => 'A-03', 'capacity' => 30]);
        $medium = Classroom::factory()->create(['name' => 'Aula A', 'code' => 'A-01', 'capacity' => 20]);
        $coordinador = $this->coordinadorUser();

        $this->actingAs($coordinador)
            ->get(route('classrooms.index', ['sort' => 'capacity', 'direction' => 'desc']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('classrooms.data.0.id', $large->id)
                ->where('classrooms.data.1.id', $medium->id)
                ->where('classrooms.data.2.id', $small->id)
                ->where('filters.sort', 'capacity')
                ->where('filters.direction', 'desc')
                ->missing('classrooms.data.0.location')
                ->missing('classrooms.data.0.floor')
            );

        $this->actingAs($coordinador)
            ->get(route('classrooms.index', ['sort' => 'location', 'direction' => 'sideways']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('classrooms.data.0.id', $medium->id)
                ->where('filters.sort', 'name')
                ->where('filters.direction', 'asc')
            );
    }
}
