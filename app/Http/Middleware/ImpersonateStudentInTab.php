<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vista del estudiante en una sola pestaña: si la petición trae la llave
 * (cabecera X-Impersonation o ?_as=) y la sesión es del administrador que la
 * generó, durante esa petición el usuario es el estudiante. La sesión no
 * cambia, así que las demás pestañas siguen siendo del administrador.
 */
class ImpersonateStudentInTab
{
    public const HEADER = 'X-Impersonation';

    public const QUERY_KEY = '_as';

    public const ATTRIBUTE = 'impersonated_by';

    private const LIFETIME_HOURS = 8;

    public static function tokenFor(User $admin, User $student): string
    {
        return Crypt::encryptString(json_encode([
            'admin' => $admin->id,
            'user' => $student->id,
            'expires' => now()->addHours(self::LIFETIME_HOURS)->timestamp,
        ]));
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header(self::HEADER) ?: $request->query(self::QUERY_KEY);
        $admin = $request->user();

        if (! $token || ! $admin?->hasRole('admin')) {
            return $next($request);
        }

        $student = $this->studentFor((string) $token, $admin);

        if (! $student) {
            return $next($request);
        }

        // Cerrar sesión desde la pestaña del estudiante cerraría la del administrador.
        if ($request->routeIs('logout')) {
            return to_route('dashboard', [self::QUERY_KEY => $token])
                ->with('error', 'Estás en la vista del estudiante: cierra esta pestaña para salir.');
        }

        Auth::setUser($student);
        $request->attributes->set(self::ATTRIBUTE, $admin);

        try {
            return $next($request);
        } finally {
            // Solo durante esta petición: después, el usuario vuelve a ser el administrador.
            Auth::setUser($admin);
        }
    }

    private function studentFor(string $token, User $admin): ?User
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true);
        } catch (DecryptException) {
            return null;
        }

        if (! is_array($payload) || ($payload['admin'] ?? null) !== $admin->id || ($payload['expires'] ?? 0) < now()->timestamp) {
            return null;
        }

        $student = User::find($payload['user'] ?? null);

        return $student?->is_active && $student->hasRole('estudiante') ? $student : null;
    }
}
