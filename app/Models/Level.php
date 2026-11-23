<?php

namespace App\Models;

use Database\Factories\LevelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

#[Fillable([
    'course_id',
    'next_level_id',
    'name',
    'class_group',
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

    /**
     * Intensidades que se quitan del final del nombre para obtener el grupo
     * de clases ("Elementary 1 Semi-Intensivo" → "Elementary 1").
     */
    private const INTENSITY_SUFFIX = '/[\s\-–]*(semi[\s\-]*intensivo|intensivo|standard|est[aá]ndar)\s*$/iu';

    /**
     * Sin grupo escrito, el nivel toma el de su nombre.
     */
    protected static function booted(): void
    {
        static::saving(function (Level $level) {
            $level->class_group = trim((string) $level->class_group) ?: static::classGroupFromName((string) $level->name);
        });
    }

    public static function classGroupFromName(string $name): string
    {
        return trim((string) preg_replace(self::INTENSITY_SUFFIX, '', $name)) ?: trim($name);
    }

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

    /**
     * Niveles que comparten las clases de este (el mismo curso y grupo de
     * clases), incluido este mismo.
     *
     * @return list<int>
     */
    public function sharedClassLevelIds(): array
    {
        if (! $this->class_group) {
            return [$this->id];
        }

        return static::query()
            ->where('course_id', $this->course_id)
            ->where('class_group', $this->class_group)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Clave del nivel global: "curso|grupo de clases".
     */
    public function globalLevelKey(): string
    {
        return $this->course_id.'|'.$this->class_group;
    }

    /**
     * Niveles globales (un grupo de clases por curso) con las intensidades
     * que los comparten, para los selectores de Temas y Docentes.
     *
     * @return Collection<string, array{key: string, course: string|null, group: string, levels: list<string>, level_ids: list<int>}>
     */
    public static function globalLevelOptions(): Collection
    {
        return static::query()
            ->with('course:id,name')
            ->whereNotNull('class_group')
            ->orderBy('position')
            ->orderBy('name')
            ->get(['id', 'course_id', 'class_group', 'name', 'position'])
            ->groupBy(fn (Level $level) => $level->globalLevelKey())
            ->map(fn (Collection $levels, string $key) => [
                'key' => $key,
                'course' => $levels->first()->course?->name,
                'group' => $levels->first()->class_group,
                'levels' => $levels->pluck('name')->sort()->values()->all(),
                'level_ids' => $levels->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            ])
            ->sortBy([['course', 'asc'], ['group', 'asc']]);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class)->withTimestamps();
    }

    /**
     * @return list<int>
     */
    public static function sharedClassLevelIdsFor(int $levelId): array
    {
        return static::find($levelId)?->sharedClassLevelIds() ?? [$levelId];
    }

    /**
     * Temas del nivel global (los comparten todas sus intensidades).
     *
     * @return Collection<int, LevelTopic>
     */
    public function topics(): Collection
    {
        return LevelTopic::query()->forLevel($this)->get();
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
