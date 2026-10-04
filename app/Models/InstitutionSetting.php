<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'name',
    'tax_id',
    'address',
    'phone',
    'email',
    'logo_path',
    'primary_color',
    'accent_color',
    'sidebar_style',
    'signer_name',
    'signer_title',
    'payment_due_day',
    'payment_reminder_days',
    'overdue_alert_days',
])]
class InstitutionSetting extends Model
{
    private const BRANDING_CACHE_KEY = 'institution.branding';

    public const DEFAULT_PRIMARY_COLOR = '#1D4ED8';

    public const DEFAULT_ACCENT_COLOR = '#F97316';

    /**
     * Estilos del menú lateral del personal (Sistema → Apariencia).
     *
     * @var array<string, string>
     */
    public const SIDEBAR_STYLES = [
        'color' => 'Color de la institución',
        'oscuro' => 'Oscuro',
        'claro' => 'Claro',
    ];

    /**
     * Proporción de blanco (+) o de negro (-) que se mezcla con el color
     * base para cada tono. El color elegido es el tono 600 (botones).
     *
     * @var array<int, float>
     */
    private const SHADES = [
        50 => 0.95, 100 => 0.88, 200 => 0.76, 300 => 0.58, 400 => 0.36, 500 => 0.16,
        600 => 0.0, 700 => -0.16, 800 => -0.3, 900 => -0.44, 950 => -0.6,
    ];

    /**
     * This table holds a single configuration row, same singleton pattern
     * as RecoverySetting: `id` isn't fillable, so we never key on a
     * hardcoded id — just reuse the first (and only) row, or create it.
     */
    public static function current(): self
    {
        // fresh(): así el registro recién creado trae los valores por defecto de
        // la base de datos (día límite de pago, días de alerta…).
        return static::query()->first() ?? static::query()->create(['name' => 'AulaMaster'])->fresh();
    }

    /**
     * Nombre, logo y colores que se muestran en toda la aplicación (pestaña
     * del navegador, barra lateral, login y portal). Se lee en cada página,
     * así que se guarda en caché hasta que cambie la configuración.
     *
     * @return array{name: string, logo_url: string|null, primary_color: string, accent_color: string, sidebar_style: string, palette_css: string}
     */
    public static function branding(): array
    {
        return Cache::rememberForever(self::BRANDING_CACHE_KEY, function (): array {
            $settings = static::current();
            $primary = $settings->primary_color ?: self::DEFAULT_PRIMARY_COLOR;
            $accent = $settings->accent_color ?: self::DEFAULT_ACCENT_COLOR;

            return [
                'name' => $settings->name,
                'logo_url' => $settings->logoUrl(),
                'primary_color' => $primary,
                'accent_color' => $accent,
                'sidebar_style' => array_key_exists((string) $settings->sidebar_style, self::SIDEBAR_STYLES) ? $settings->sidebar_style : 'color',
                'palette_css' => self::paletteCss($primary, $accent),
            ];
        });
    }

    public static function forgetBranding(): void
    {
        Cache::forget(self::BRANDING_CACHE_KEY);
    }

    /**
     * Dirección del logo relativa al sitio ("/storage/…"), así carga con
     * cualquier dominio aunque APP_URL apunte a otro (localhost, la IP del
     * servidor…).
     */
    public function logoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return parse_url(Storage::disk('public')->url($this->logo_path), PHP_URL_PATH);
    }

    /**
     * Variables CSS con la paleta completa de cada color (50 a 950), que
     * usa tailwind.config.js para los colores "indigo" (principal) y
     * "accent".
     */
    public static function paletteCss(string $primary, string $accent): string
    {
        $variables = [];

        foreach (['primary' => $primary, 'accent' => $accent] as $name => $hex) {
            foreach (self::palette($hex) as $shade => $rgb) {
                $variables[] = "--c-{$name}-{$shade}: {$rgb};";
            }
        }

        return ':root{'.implode('', $variables).'}';
    }

    /**
     * @return array<int, string> tono => "r g b"
     */
    public static function palette(string $hex): array
    {
        $hex = ltrim($hex, '#');
        [$red, $green, $blue] = array_map('hexdec', str_split(strlen($hex) === 3 ? preg_replace('/(.)/', '$1$1', $hex) : $hex, 2));

        $mix = fn (int $channel, float $amount) => (int) round($amount >= 0
            ? $channel + (255 - $channel) * $amount
            : $channel * (1 + $amount));

        return collect(self::SHADES)
            ->map(fn (float $amount) => implode(' ', [$mix($red, $amount), $mix($green, $amount), $mix($blue, $amount)]))
            ->all();
    }
}
