<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Quien entra con una contraseña temporal solo puede cambiarla o cerrar
 * sesión; cualquier otra página lo devuelve a "Cambiar contraseña".
 */
class EnsurePasswordIsChanged
{
    /**
     * @var list<string>
     */
    private const ALLOWED_ROUTES = ['password.change', 'password.change.update', 'logout'];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password && ! $request->routeIs(...self::ALLOWED_ROUTES)) {
            return to_route('password.change');
        }

        return $next($request);
    }
}
