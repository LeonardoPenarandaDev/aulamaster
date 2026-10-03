<?php

namespace App\Http\Controllers;

use App\Actions\Dashboard\GetAdminDashboardStats;
use App\Actions\Dashboard\GetStudentDashboardData;
use App\Actions\Dashboard\GetTeacherDashboardData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard for the current user's role (secciones 35-37
     * del plan). Cada rol ve solo su propio panel de consulta.
     */
    public function index(
        Request $request,
        GetAdminDashboardStats $getAdminStats,
        GetTeacherDashboardData $getTeacherData,
        GetStudentDashboardData $getStudentData,
    ): Response|RedirectResponse {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            return Inertia::render('Dashboard', [
                'stats' => $getAdminStats->handle(),
            ]);
        }

        // Los roles de alcance único (Fase 15) no tienen un panel de
        // indicadores propio: van directo a la única pantalla que pueden usar.
        if ($user->hasRole('coordinador')) {
            return to_route('class-sessions.index');
        }

        if ($user->hasRole('cajero')) {
            return to_route('enrollments.index');
        }

        if ($user->hasRole('secretaria')) {
            return to_route('students.index');
        }

        if ($user->hasRole('profesor') && $user->teacher) {
            return Inertia::render('Portal/TeacherDashboard', [
                'teacherData' => $getTeacherData->handle($user->teacher),
            ]);
        }

        if ($user->hasRole('estudiante') && $user->student) {
            return Inertia::render('Portal/StudentDashboard', [
                'studentData' => $getStudentData->handle($user->student),
            ]);
        }

        return Inertia::render('Dashboard');
    }
}
