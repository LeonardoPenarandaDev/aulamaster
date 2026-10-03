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
    'signer_name',
    'signer_title',
    'payment_due_day',
    'payment_reminder_days',
    'overdue_alert_days',
])]
class InstitutionSetting extends Model
{
    private const BRANDING_CACHE_KEY = 'institution.branding';

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
     * Nombre y logo que se muestran en toda la aplicación (pestaña del
     * navegador, barra de navegación y login). Se lee en cada página, así
     * que se guarda en caché hasta que cambie la configuración.
     *
     * @return array{name: string, logo_url: string|null}
     */
    public static function branding(): array
    {
        return Cache::rememberForever(self::BRANDING_CACHE_KEY, function (): array {
            $settings = static::current();

            return [
                'name' => $settings->name,
                'logo_url' => $settings->logo_path ? Storage::disk('public')->url($settings->logo_path) : null,
            ];
        });
    }

    public static function forgetBranding(): void
    {
        Cache::forget(self::BRANDING_CACHE_KEY);
    }
}
