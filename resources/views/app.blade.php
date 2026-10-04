<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ $institution['name'] }}</title>
        <meta name="application-name" content="{{ $institution['name'] }}">
        <link rel="icon" href="{{ $institution['logo_url'] ?? '/favicon.ico' }}">
        <meta name="theme-color" content="{{ $institution['primary_color'] }}">
        {{-- Colores de la institución (Sistema → Configuración). Solo contiene números generados a partir de colores validados. --}}
        <style>{!! $institution['palette_css'] !!}</style>

        <!-- Tema claro/oscuro: se aplica antes de pintar para que no parpadee (resources/js/theme.js). -->
        <script>
            (function () {
                try {
                    var theme = localStorage.getItem('theme') || 'system';
                    var dark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                    document.documentElement.classList.toggle('dark', dark);
                } catch (e) {}
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
