<?php

namespace App\Rules;

use App\Models\Level;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * El nivel siguiente debe ser del mismo curso, distinto del propio nivel y
 * no puede formar un ciclo (parte 5 del plan de mejoras).
 */
class ValidNextLevel implements ValidationRule
{
    public function __construct(
        protected mixed $courseId,
        protected ?Level $level = null,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $nextLevel = Level::find($value);

        if (! $nextLevel) {
            return;
        }

        if ($this->level && $nextLevel->is($this->level)) {
            $fail('Un nivel no puede ser su propio nivel siguiente.');

            return;
        }

        if ((int) $nextLevel->course_id !== (int) $this->courseId) {
            $fail('El nivel siguiente debe pertenecer al mismo curso.');

            return;
        }

        if (! $this->level) {
            return;
        }

        $visited = [];
        $current = $nextLevel;
        while ($current && ! isset($visited[$current->id])) {
            if ($current->next_level_id === $this->level->id) {
                $fail('Ese nivel siguiente formaría un ciclo en la ruta de niveles.');

                return;
            }

            $visited[$current->id] = true;
            $current = $current->nextLevel;
        }
    }
}
