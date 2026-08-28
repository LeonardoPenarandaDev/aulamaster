<?php

namespace App\Models;

use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'class_session_id',
    'enrollment_id',
    'teacher_id',
    'class_date',
    'status',
    'registered_by_id',
])]
class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory;

    protected $appends = ['effective_status'];

    protected function casts(): array
    {
        return [
            'class_date' => 'date',
        ];
    }

    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
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

    public function corrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class)->latest();
    }

    /**
     * The status actually used for hour accounting: the latest correction's
     * new status if one exists, otherwise the original registered status.
     * The original `status` column is never overwritten (sección 16 del plan).
     */
    protected function effectiveStatus(): Attribute
    {
        return Attribute::get(function () {
            $latestCorrection = $this->relationLoaded('corrections')
                ? $this->corrections->first()
                : $this->corrections()->first();

            return $latestCorrection?->new_status ?? $this->status;
        });
    }
}
