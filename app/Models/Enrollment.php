<?php

namespace App\Models;

use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'student_id',
    'level_id',
    'enrolled_at',
    'start_date',
    'estimated_end_date',
    'actual_end_date',
    'status',
    'required_hours',
    'accumulated_hours',
    'base_price',
    'promotion_id',
    'promotion_discount',
    'referral_id',
    'referral_discount',
    'final_price',
])]
class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    protected $appends = ['pending_hours', 'progress_percentage'];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'date',
            'start_date' => 'date',
            'estimated_end_date' => 'date',
            'actual_end_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function evaluationResults(): HasMany
    {
        return $this->hasMany(EvaluationResult::class);
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(Extension::class)->latest();
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Horas pendientes = horas requeridas - horas acumuladas (sección 17 del plan).
     */
    protected function pendingHours(): Attribute
    {
        return Attribute::get(fn () => max(0, $this->required_hours - $this->accumulated_hours));
    }

    /**
     * Progreso = (horas acumuladas / horas requeridas) x 100 (sección 17 del plan).
     */
    protected function progressPercentage(): Attribute
    {
        return Attribute::get(fn () => $this->required_hours > 0
            ? round(min(100, ($this->accumulated_hours / $this->required_hours) * 100), 2)
            : 0.0
        );
    }
}
