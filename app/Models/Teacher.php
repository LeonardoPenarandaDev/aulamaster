<?php

namespace App\Models;

use Database\Factories\TeacherFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['user_id', 'code', 'name', 'document', 'email', 'phone', 'status'])]
class Teacher extends Model
{
    /** @use HasFactory<TeacherFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Niveles que dicta. Se asignan por nivel global: marcar "Elementary 1"
     * lo asigna a todas sus intensidades.
     */
    public function levels(): BelongsToMany
    {
        return $this->belongsToMany(Level::class)->withTimestamps();
    }

    /**
     * Claves de los niveles globales que dicta ("curso|grupo").
     *
     * @return list<string>
     */
    public function globalLevelKeys(): array
    {
        return $this->levels
            ->map(fn (Level $level) => $level->globalLevelKey())
            ->unique()
            ->values()
            ->all();
    }
}
