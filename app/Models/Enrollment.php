<?php

namespace App\Models;

use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'student_id',
    'level_id',
    'previous_enrollment_id',
    'prerequisite_waived',
    'enrolled_at',
    'start_date',
    'estimated_end_date',
    'actual_end_date',
    'status',
    'required_hours',
    'weekly_hours',
    'accumulated_hours',
    'base_price',
    'promotion_id',
    'promotion_discount',
    'referral_id',
    'referral_discount',
    'final_price',
    'monthly_fee',
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
            'prerequisite_waived' => 'boolean',
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

    /**
     * Matrícula del nivel anterior que se aprobó para llegar a esta (parte 5
     * del plan de mejoras).
     */
    public function previousEnrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'previous_enrollment_id');
    }

    public function nextEnrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class, 'previous_enrollment_id');
    }

    public function contractSignatures(): HasMany
    {
        return $this->hasMany(ContractSignature::class);
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
     * Valor de la mensualidad: el ajustado para este estudiante o, si no
     * tiene, el del nivel (parte 8 del plan de mejoras).
     */
    public function effectiveMonthlyFee(): ?float
    {
        $fee = $this->monthly_fee ?? $this->level?->monthly_fee;

        return $fee !== null ? (float) $fee : null;
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
