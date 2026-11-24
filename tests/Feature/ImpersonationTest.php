<?php

namespace Tests\Feature;

use App\Http\Middleware\ImpersonateStudentInTab;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * El administrador ve la plataforma como un estudiante en una pestaña
 * nueva, sin su contraseña y sin cerrar su propia sesión.
 */
class ImpersonationTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private function studentWithAccount(): Student
    {
        return Student::factory()->create(['user_id' => $this->studentUser()->id, 'name' => 'Juan Test']);
    }

    private function tokenFrom(string $location): string
    {
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        return $query[ImpersonateStudentInTab::QUERY_KEY];
    }

    public function test_admin_opens_the_student_view_and_keeps_their_own_session(): void
    {
        $admin = $this->adminUser();
        $student = $this->studentWithAccount();

        $response = $this->actingAs($admin)->get(route('students.impersonate', $student));
        $response->assertRedirectContains(route('dashboard').'?_as=');
        $token = $this->tokenFrom($response->headers->get('Location'));

        $this->assertDatabaseHas(AuditLog::class, [
            'user_id' => $admin->id,
            'action' => 'ingreso_como_estudiante',
            'auditable_type' => User::class,
            'auditable_id' => $student->user_id,
        ]);

        // La pestaña del estudiante (primera carga con ?_as= y luego con la cabecera).
        $this->get(route('dashboard', ['_as' => $token]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Portal/StudentDashboard')
                ->where('auth.user.id', $student->user_id)
                ->where('impersonating.student', $student->user->name)
                ->where('impersonating.admin', $admin->name)
            );

        $this->withHeader(ImpersonateStudentInTab::HEADER, $token)
            ->get(route('student-schedule.index'))
            ->assertOk();

        $this->withHeader(ImpersonateStudentInTab::HEADER, $token)
            ->get(route('students.index'))
            ->assertForbidden();

        // Las demás pestañas (sin la llave) siguen siendo del administrador.
        $this->flushHeaders();
        $this->get(route('students.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.user.id', $admin->id)
                ->where('impersonating', null)
            );
        $this->assertAuthenticatedAs($admin);
    }

    public function test_logging_out_from_the_student_tab_does_not_close_the_admin_session(): void
    {
        $admin = $this->adminUser();
        $student = $this->studentWithAccount();
        $token = $this->tokenFrom($this->actingAs($admin)->get(route('students.impersonate', $student))->headers->get('Location'));

        $this->withHeader(ImpersonateStudentInTab::HEADER, $token)
            ->post(route('logout'))
            ->assertRedirectContains(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_the_token_only_works_with_the_admin_session_that_created_it(): void
    {
        $student = $this->studentWithAccount();
        $token = $this->tokenFrom($this->actingAs($this->adminUser())->get(route('students.impersonate', $student))->headers->get('Location'));

        $otherAdmin = $this->adminUser();
        $this->actingAs($otherAdmin)
            ->withHeader(ImpersonateStudentInTab::HEADER, $token)
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('auth.user.id', $otherAdmin->id));

        $secretaria = $this->secretariaUser();
        $this->actingAs($secretaria)
            ->withHeader(ImpersonateStudentInTab::HEADER, $token)
            ->get(route('students.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('auth.user.id', $secretaria->id));

        $this->actingAs($otherAdmin)
            ->withHeader(ImpersonateStudentInTab::HEADER, 'llave-falsa')
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('auth.user.id', $otherAdmin->id));
    }

    public function test_the_token_expires(): void
    {
        $admin = $this->adminUser();
        $student = $this->studentWithAccount();
        $token = $this->tokenFrom($this->actingAs($admin)->get(route('students.impersonate', $student))->headers->get('Location'));

        $this->travel(9)->hours();

        $this->actingAs($admin)
            ->withHeader(ImpersonateStudentInTab::HEADER, $token)
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('auth.user.id', $admin->id));
    }

    public function test_student_in_debt_shows_the_pending_payments_screen_in_the_tab(): void
    {
        $admin = $this->adminUser();
        $student = $this->studentWithAccount();
        Payment::factory()->create(['student_id' => $student->id, 'status' => 'vencido', 'type' => 'mensualidad']);
        $token = $this->tokenFrom($this->actingAs($admin)->get(route('students.impersonate', $student))->headers->get('Location'));

        $this->withHeader(ImpersonateStudentInTab::HEADER, $token)
            ->get(route('student-schedule.index'))
            ->assertRedirect(route('student-account.blocked'));

        $this->flushHeaders();
        $this->get(route('students.index'))->assertOk();
    }

    public function test_only_admin_can_open_the_student_view(): void
    {
        $student = $this->studentWithAccount();

        foreach ([$this->secretariaUser(), $this->cajeroUser(), $this->teacherUser(), $this->studentUser()] as $user) {
            $this->actingAs($user)->get(route('students.impersonate', $student))->assertForbidden();
        }
    }

    public function test_student_without_portal_access_cannot_be_opened(): void
    {
        $student = Student::factory()->create();

        $this->actingAs($this->adminUser())
            ->get(route('students.impersonate', $student))
            ->assertSessionHas('error');
    }

    public function test_student_edit_page_offers_the_link_only_to_admin(): void
    {
        $student = $this->studentWithAccount();

        $this->actingAs($this->adminUser())
            ->get(route('students.edit', $student))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('canImpersonate', true));

        $this->actingAs($this->secretariaUser())
            ->get(route('students.edit', $student))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('canImpersonate', false));
    }
}
