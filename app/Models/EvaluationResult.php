<?php

namespace App\Models;

use Database\Factories\EvaluationResultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'evaluation_id',
    'enrollment_id',
    'teacher_id',
    'attempt_number',
    'is_recovery',
    'cost',
    'payment_status',
    'payment_id',
    'evaluated_at',
    'grade',
    'result',
    'notes',
    'registered_by_id',
])]
class EvaluationResult extends Model
{
    /** @use HasFactory<EvaluationResultFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'evaluated_at' => 'date',
            'is_recovery' => 'boolean',
        ];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
