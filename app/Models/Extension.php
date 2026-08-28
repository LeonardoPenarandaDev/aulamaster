<?php

namespace App\Models;

use Database\Factories\ExtensionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['enrollment_id', 'previous_end_date', 'new_end_date', 'reason', 'notes', 'extended_by_id'])]
class Extension extends Model
{
    /** @use HasFactory<ExtensionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'previous_end_date' => 'date',
            'new_end_date' => 'date',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function extendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'extended_by_id');
    }
}
