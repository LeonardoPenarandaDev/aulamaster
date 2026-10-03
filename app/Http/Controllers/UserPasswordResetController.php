<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Reemplaza el "¿Olvidaste tu contraseña?" (parte 2 del plan de mejoras):
 * el personal genera una contraseña temporal, se la comparte a la persona y
 * esta debe cambiarla al iniciar sesión. Queda registrado en la auditoría.
 */
class UserPasswordResetController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const ROLE_LABELS = [
        ...User::STAFF_ROLE_LABELS,
        'profesor' => 'Profesor',
        'estudiante' => 'Estudiante',
    ];

    /**
     * List the accounts the current user is allowed to reset.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAnyPasswordResets', User::class);

        $actor = $request->user();
        $resettableRoles = $actor->hasRole('admin')
            ? array_keys(self::ROLE_LABELS)
            : ['estudiante', 'profesor'];

        return Inertia::render('PasswordResets/Index', [
            'users' => User::role($resettableRoles)
                ->with('roles:id,name')
                ->when(! $actor->hasRole('admin'), fn ($query) => $query->withoutRole(User::STAFF_ROLES))
                ->whereKeyNot($actor->id)
                ->when($request->input('search'), fn ($query, $search) => $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                ))
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString()
                ->through(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => self::ROLE_LABELS[$user->roles->pluck('name')->first()] ?? null,
                    'is_active' => $user->is_active,
                    'must_change_password' => $user->must_change_password,
                ]),
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * Assign a temporary password and force the user to change it on their
     * next login. Sus sesiones abiertas se cierran.
     */
    public function store(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('resetPassword', $user);

        $temporaryPassword = Str::password(10, symbols: false);
        $actor = $request->user();

        DB::transaction(function () use ($user, $temporaryPassword, $actor, $request) {
            $user->forceFill([
                'password' => $temporaryPassword,
                'must_change_password' => true,
            ])->save();

            if (config('session.driver') === 'database') {
                DB::table(config('session.table'))->where('user_id', $user->id)->delete();
            }

            AuditLog::create([
                'user_id' => $actor->id,
                'user_name' => $actor->name,
                'role' => $actor->getRoleNames()->first(),
                'action' => 'contraseña_restablecida',
                'module' => 'usuarios',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'description' => "Contraseña restablecida para {$user->name} ({$user->email})",
                'ip_address' => $request->ip(),
            ]);
        });

        return back()->with('success', "Contraseña restablecida. Usuario: {$user->email} · Contraseña temporal: {$temporaryPassword}. Deberá cambiarla al iniciar sesión.");
    }
}
