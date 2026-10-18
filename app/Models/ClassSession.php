<?php

namespace App\Models;

use Database\Factories\ClassSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'class_schedule_id',
    'level_id',
    'teacher_id',
    'classroom_id',
    'modality',
    'meeting_url',
    'date',
    'start_time',
    'end_time',
    'status',
    'notes',
])]
class ClassSession extends Model
{
    /** @use HasFactory<ClassSessionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function classSchedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ClassMaterial::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(ClassBooking::class);
    }

    /**
     * Cupos de la clase: la capacidad del aula en las presenciales. Las
     * virtuales no tienen límite (null).
     */
    public function capacity(): ?int
    {
        return $this->modality === 'virtual' ? null : $this->classroom?->capacity;
    }

    /**
     * Momento en que empieza la clase (fecha + hora de inicio).
     */
    public function startsAt(): Carbon
    {
        return $this->date->copy()->setTimeFromTimeString($this->start_time);
    }

    /**
     * Duration of the session in hours, used for attendance-based hour accounting.
     */
    public function durationHours(): float
    {
        return Carbon::parse($this->start_time)->diffInMinutes(Carbon::parse($this->end_time)) / 60;
    }
}
