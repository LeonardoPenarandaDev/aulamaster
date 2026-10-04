<?php

namespace Tests\Feature;

use App\Models\Level;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Foto de perfil del estudiante: se toma al matricularlo, se reduce y se
 * guarda en privado; la ven el personal, los docentes y el propio alumno.
 */
class StudentPhotoTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_photo_is_taken_in_the_enrollment_wizard_and_resized(): void
    {
        $level = Level::factory()->create();

        $this->actingAs($this->secretariaUser())
            ->post(route('enrollments.wizard.store'), [
                'new_student' => ['code' => 'EST-950', 'name' => 'Sara Gómez', 'birth_date' => now()->subYears(30)->toDateString()],
                'level_id' => $level->id,
                'enrolled_at' => now()->toDateString(),
                'start_date' => now()->toDateString(),
                'required_hours' => 100,
                'weekly_hours' => 6,
                'sign_method' => 'despues',
                'photo' => UploadedFile::fake()->image('sara.jpg', 2000, 1500),
            ])
            ->assertSessionHasNoErrors();

        $student = Student::query()->where('code', 'EST-950')->firstOrFail();
        Storage::disk('local')->assertExists($student->photo_path);

        [$width, $height] = getimagesizefromstring(Storage::disk('local')->get($student->photo_path));
        $this->assertSame([600, 450], [$width, $height]);
        $this->assertStringStartsWith('/students/'.$student->id.'/photo', $student->photo_url);
    }

    public function test_photo_can_be_replaced_and_removed_from_the_student_record(): void
    {
        $secretaria = $this->secretariaUser();
        $student = Student::factory()->create();

        $this->actingAs($secretaria)
            ->post(route('students.photo.update', $student), ['photo' => UploadedFile::fake()->image('a.png', 300, 300)])
            ->assertSessionHasNoErrors();
        $first = $student->fresh()->photo_path;

        $this->actingAs($secretaria)
            ->post(route('students.photo.update', $student), ['photo' => UploadedFile::fake()->image('b.png', 300, 300)]);
        Storage::disk('local')->assertMissing($first);

        $this->actingAs($secretaria)->delete(route('students.photo.destroy', $student));
        $this->assertNull($student->fresh()->photo_path);

        $this->actingAs($secretaria)
            ->post(route('students.photo.update', $student), ['photo' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('photo');
    }

    public function test_photo_is_private_but_visible_to_staff_teachers_and_the_student(): void
    {
        $studentUser = $this->studentUser();
        $student = Student::factory()->create(['user_id' => $studentUser->id]);
        $this->actingAs($this->adminUser())->post(route('students.photo.update', $student), ['photo' => UploadedFile::fake()->image('a.jpg')]);
        $student->refresh();

        foreach ([$studentUser, $this->teacherUser(), $this->secretariaUser(), $this->cajeroUser()] as $user) {
            $this->actingAs($user)->get($student->photo_url)->assertOk();
        }

        $otherStudent = $this->studentUser();
        Student::factory()->create(['user_id' => $otherStudent->id]);
        $this->actingAs($otherStudent)->get($student->photo_url)->assertForbidden();

        auth()->logout();
        $this->get($student->photo_url)->assertRedirect(route('login'));
    }

    public function test_student_sees_their_photo_as_avatar_in_the_portal(): void
    {
        $studentUser = $this->studentUser();
        $student = Student::factory()->create(['user_id' => $studentUser->id]);
        $this->actingAs($this->adminUser())->post(route('students.photo.update', $student), ['photo' => UploadedFile::fake()->image('a.jpg')]);

        $this->actingAs($studentUser)->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('auth.avatar_url', $student->fresh()->photo_url));

    }
}
