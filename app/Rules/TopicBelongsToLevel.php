<?php

namespace App\Rules;

use App\Models\Level;
use App\Models\LevelTopic;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * El tema de una clase debe ser del nivel global de la clase (mismo curso y
 * grupo de clases).
 */
class TopicBelongsToLevel implements ValidationRule
{
    public function __construct(
        protected mixed $levelId,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $topic = LevelTopic::find($value);
        $level = Level::find($this->levelId);

        if (! $topic || ! $level) {
            return;
        }

        if (! $topic->belongsToLevelGroup($level)) {
            $fail('El tema no pertenece a este nivel.');
        }
    }
}
