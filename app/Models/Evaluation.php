<?php

namespace App\Models;

use Database\Factories\EvaluationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['level_id', 'name', 'competency', 'minimum_grade', 'status'])]
class Evaluation extends Model
{
    /** @use HasFactory<EvaluationFactory> */
    use HasFactory;

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(EvaluationResult::class);
    }
}
