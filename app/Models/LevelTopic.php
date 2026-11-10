<?php

namespace App\Models;

use Database\Factories\LevelTopicFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tema de un nivel global (curso + grupo de clases), compartido por todas
 * sus intensidades.
 */
#[Fillable(['course_id', 'class_group', 'name', 'position'])]
class LevelTopic extends Model
{
    /** @use HasFactory<LevelTopicFactory> */
    use HasFactory;

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function classSessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    /**
     * Temas del grupo de clases del nivel, en orden.
     *
     * @param  Builder<LevelTopic>  $query
     */
    public function scopeForLevel(Builder $query, Level $level): void
    {
        $query->inGroup($level->course_id, $level->class_group)
            ->orderBy('position')
            ->orderBy('id');
    }

    /**
     * Temas de un nivel global (curso + grupo de clases), sin orden.
     *
     * @param  Builder<LevelTopic>  $query
     */
    public function scopeInGroup(Builder $query, int|string $courseId, ?string $classGroup): void
    {
        $query->where('course_id', $courseId)->where('class_group', $classGroup);
    }

    public function belongsToLevelGroup(Level $level): bool
    {
        return (int) $this->course_id === (int) $level->course_id && $this->class_group === $level->class_group;
    }
}
