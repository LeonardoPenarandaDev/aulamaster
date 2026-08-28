<?php

namespace App\Models;

use Database\Factories\AttendanceCorrectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['attendance_id', 'previous_status', 'new_status', 'reason', 'corrected_by_id'])]
class AttendanceCorrection extends Model
{
    /** @use HasFactory<AttendanceCorrectionFactory> */
    use HasFactory;

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by_id');
    }
}
