<?php

namespace App\Models;

use Database\Factories\LevelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

#[Fillable([
    'course_id',
    'next_level_id',
    'name',
    'color',
    'code',
    'duration_months',
    'weekly_hours',
    'monthly_hours',
    'required_hours',
    'minimum_grade',
    'price',
    'monthly_fee',
    'start_date',
    'end_date',
    'status',
])]
class Level extends Model
{
    /** @use HasFactory<LevelFactory> */
    use HasFactory;

    public const DEFAULT_COLOR = '#DBEAFE';

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function nextLevel(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'next_level_id');
    }

    public function previousLevel(): HasOne
    {
        return $this->hasOne(Level::class, 'next_level_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    /**
     * La ruta completa a la que pertenece este nivel, en orden: desde el
     * primer nivel (el que no tiene anterior) hasta el último.
     *
     * @return Collection<int, Level>
     */
    public function route(): Collection
    {
        $levels = $this->course->levels()->get()->keyBy('id');
        $previousOf = $levels->filter(fn (Level $level) => $level->next_level_id)->keyBy('next_level_id');

        $first = $levels->get($this->id) ?? $this;
        $visited = [$first->id => true];
        while (($previous = $previousOf->get($first->id)) && ! isset($visited[$previous->id])) {
            $visited[$previous->id] = true;
            $first = $previous;
        }

        $route = collect([$first]);
        $visited = [$first->id => true];
        while (($next = $levels->get($route->last()->next_level_id)) && ! isset($visited[$next->id])) {
            $visited[$next->id] = true;
            $route->push($next);
        }

        return $route;
    }

    /**
     * Numera los niveles de un curso según su lugar en la ruta (1 = primer
     * nivel), para ordenar los listados sin recorrer la cadena cada vez.
     */
    public static function recalculatePositions(int $courseId): void
    {
        $levels = static::query()->where('course_id', $courseId)->get();
        $firstLevels = $levels->reject(fn (Level $level) => $levels->contains('next_level_id', $level->id));

        foreach ($firstLevels as $first) {
            foreach ($first->route()->values() as $index => $level) {
                if ($level->position !== $index + 1) {
                    $level->forceFill(['position' => $index + 1])->saveQuietly();
                }
            }
        }
    }
}
