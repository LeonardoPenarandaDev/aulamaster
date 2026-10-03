<?php

namespace App\Http\Middleware;

use App\Models\InstitutionSetting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'roles' => fn () => $request->user()?->getRoleNames() ?? [],
            ],
            'institution' => fn () => InstitutionSetting::branding(),
            'studentTheme' => fn () => $this->studentTheme($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'contractLink' => fn () => $request->session()->get('contractLink'),
            ],
            'notifications' => [
                'unreadCount' => fn () => $request->user()?->unreadNotifications()->count() ?? 0,
                'recent' => fn () => $request->user()
                    ?->notifications()
                    ->latest()
                    ->limit(10)
                    ->get(['id', 'data', 'read_at', 'created_at'])
                    ?? [],
            ],
        ];
    }

    /**
     * Color del nivel actual del estudiante, que se usa como fondo de su
     * portal (parte 4 del plan de mejoras). Null para los demás roles o si
     * todavía no tiene matrículas: en ese caso se mantiene el fondo gris.
     *
     * @return array{color: string}|null
     */
    protected function studentTheme(Request $request): ?array
    {
        $student = $request->user()?->hasRole('estudiante') ? $request->user()->student : null;
        $color = $student?->currentEnrollment()?->level?->color;

        return $color ? ['color' => $color] : null;
    }
}
