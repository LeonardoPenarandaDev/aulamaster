<?php

namespace App\Models;

use Database\Factories\PromotionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'description',
    'discount_type',
    'value',
    'start_date',
    'end_date',
    'course_id',
    'level_id',
    'max_uses',
    'status',
])]
class Promotion extends Model
{
    /** @use HasFactory<PromotionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function isCurrentlyValid(): bool
    {
        return $this->status === 'activo'
            && now()->between($this->start_date, $this->end_date)
            && ($this->max_uses === null || $this->enrollments()->count() < $this->max_uses);
    }

    /**
     * Calcula el valor de descuento (en pesos) que aplica sobre un precio base.
     */
    public function discountFor(float $basePrice): float
    {
        return $this->discount_type === 'porcentaje'
            ? round($basePrice * ($this->value / 100), 2)
            : (float) $this->value;
    }
}
