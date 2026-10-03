<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * El estudiante con pagos vencidos y sin acuerdo de pago vigente solo puede
 * pagar en línea, ver su perfil, sus contratos y cerrar sesión; lo demás lo
 * lleva a "Tu cuenta tiene pagos pendientes" (parte 8 del plan de mejoras).
 */
class EnsureStudentIsUpToDate
{
    /**
     * @var list<string>
     */
    private const ALLOWED_ROUTES = [
        'student-account.blocked',
        'payments.pay-online',
        'payments.online-return',
        'profile.*',
        'password.*',
        'logout',
        'notifications.*',
        'student-contracts.*',
        'contract-signatures.pdf',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $request->routeIs(...self::ALLOWED_ROUTES) || ! $user->hasRole('estudiante')) {
            return $next($request);
        }

        if ($user->student?->isBlockedForDebt()) {
            return to_route('student-account.blocked');
        }

        return $next($request);
    }
}
