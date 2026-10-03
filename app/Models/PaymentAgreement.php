<?php

namespace App\Models;

use Database\Factories\PaymentAgreementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Acuerdo de pago: mientras esté vigente, el estudiante en mora no queda
 * bloqueado (parte 8 del plan de mejoras).
 */
#[Fillable(['student_id', 'agreed_until', 'notes', 'created_by_id', 'cancelled_at'])]
class PaymentAgreement extends Model
{
    /** @use HasFactory<PaymentAgreementFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'agreed_until' => 'date',
            'cancelled_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * @param  Builder<PaymentAgreement>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('cancelled_at')->whereDate('agreed_until', '>=', today());
    }
}
