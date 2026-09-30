<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStaffUserRequest;
use App\Http\Requests\UpdateStaffUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Cuentas del personal administrativo (admin, coordinador, cajero). No se
 * eliminan porque pagos, asistencias y demás registros guardan quién los
 * hizo; en su lugar se desactivan, lo que bloquea el inicio de sesión.
 */
class StaffUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', User::class);

        return Inertia::render('StaffUsers/Index', [
            'users' => User::role(User::STAFF_ROLES)
                ->with('roles:id,name')
                ->when(request('search'), fn ($query, $search) => $query->where(fn ($query) => $query
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
                    'role' => $this->staffRole($user),
                    'is_active' => $user->is_active,
                ]),
            'roleLabels' => User::STAFF_ROLE_LABELS,
            'filters' => request()->only('search'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('StaffUsers/Create', [
            'roleLabels' => User::STAFF_ROLE_LABELS,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStaffUserRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $user = User::create($request->safe()->only(['name', 'email', 'password']));
            $user->forceFill(['email_verified_at' => now()])->save();
            $user->assignRole($request->validated('role'));
        });

        return to_route('staff-users.index')->with('success', 'Usuario creado. Ya puede iniciar sesión con su correo y contraseña.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): Response
    {
        Gate::authorize('update', $user);

        return Inertia::render('StaffUsers/Edit', [
            'staffUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $this->staffRole($user),
                'is_active' => $user->is_active,
                'is_self' => $user->is(request()->user()),
            ],
            'roleLabels' => User::STAFF_ROLE_LABELS,
        ]);
    }

    /**
     * Update the specified resource in storage. Al desactivar una cuenta
     * se cierran también sus sesiones abiertas.
     */
    public function update(UpdateStaffUserRequest $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user) {
            $user->fill($request->safe()->only(['name', 'email', 'is_active']));

            if ($request->filled('password')) {
                $user->password = $request->validated('password');
            }

            $user->save();

            foreach (array_intersect($user->getRoleNames()->all(), User::STAFF_ROLES) as $role) {
                $user->removeRole($role);
            }
            $user->assignRole($request->validated('role'));

            if (! $user->is_active && config('session.driver') === 'database') {
                DB::table(config('session.table'))->where('user_id', $user->id)->delete();
            }
        });

        return to_route('staff-users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    private function staffRole(User $user): ?string
    {
        return $user->roles->pluck('name')->first(fn (string $role) => in_array($role, User::STAFF_ROLES, true));
    }
}
