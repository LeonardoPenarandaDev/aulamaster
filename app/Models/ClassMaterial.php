<?php

namespace App\Models;

use Database\Factories\ClassMaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['class_session_id', 'title', 'url', 'description', 'created_by_id'])]
class ClassMaterial extends Model
{
    /** @use HasFactory<ClassMaterialFactory> */
    use HasFactory;

    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
