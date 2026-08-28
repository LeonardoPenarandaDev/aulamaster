<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        // Wompi llama a este endpoint directamente, sin nuestra sesión/token
        // CSRF; la autenticidad del payload se valida con su propia firma
        // (WompiService::verifyEventSignature) en vez de con el token CSRF.
        $middleware->validateCsrfTokens(except: [
            'webhooks/wompi',
        ]);

        // En producción, el contenedor recibe el tráfico ya reenviado por el
        // Nginx del VPS (que sí termina TLS). Sin confiar en ese proxy,
        // Laravel vería cada request como HTTP plano y rompería redirects,
        // la cookie de sesión segura y la detección de HTTPS.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
