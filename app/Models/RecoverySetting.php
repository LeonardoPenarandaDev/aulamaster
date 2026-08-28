<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['free_attempts', 'max_paid_attempts', 'recovery_price', 'recovery_period_days'])]
class RecoverySetting extends Model
{
    /**
     * This table holds a single configuration row. Deliberately not keyed
     * on a hardcoded id=1: `id` isn't fillable, so `firstOrCreate(['id' =>
     * 1])` could never actually persist that id and would insert a new row
     * on every call once the auto-increment counter moved past 1.
     */
    public static function current(): self
    {
        return static::query()->first() ?? static::query()->create([]);
    }
}
