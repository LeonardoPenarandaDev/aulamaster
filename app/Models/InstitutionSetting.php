<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'tax_id', 'address', 'phone', 'email', 'logo_path', 'signer_name', 'signer_title'])]
class InstitutionSetting extends Model
{
    /**
     * This table holds a single configuration row, same singleton pattern
     * as RecoverySetting: `id` isn't fillable, so we never key on a
     * hardcoded id — just reuse the first (and only) row, or create it.
     */
    public static function current(): self
    {
        return static::query()->first() ?? static::query()->create(['name' => 'AulaMaster']);
    }
}
