<?php

namespace App\Models;

use Database\Factories\ReferralFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['referrer_student_id', 'referred_student_id', 'referrer_discount', 'referred_discount'])]
class Referral extends Model
{
    /** @use HasFactory<ReferralFactory> */
    use HasFactory;

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'referrer_student_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'referred_student_id');
    }
}
