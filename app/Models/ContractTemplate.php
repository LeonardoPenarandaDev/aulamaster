<?php

namespace App\Models;

use Database\Factories\ContractTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Plantilla de contrato (parte 6.1 del plan de mejoras). Una plantilla
 * publicada no se edita: se crea una versión nueva con el mismo `code`, y
 * lo que ya se firmó conserva la versión con la que se firmó.
 */
#[Fillable([
    'code',
    'version',
    'name',
    'type',
    'body',
    'status',
    'acceptance_mode',
    'scope',
    'requires_guardian',
    'published_at',
    'created_by_id',
])]
class ContractTemplate extends Model
{
    /** @use HasFactory<ContractTemplateFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    public const TYPES = [
        'matricula' => 'Contrato de matrícula',
        'imagenes' => 'Autorización de uso de imágenes',
        'datos' => 'Tratamiento de datos personales',
        'reglamento' => 'Reglamento institucional',
        'otro' => 'Otro',
    ];

    protected function casts(): array
    {
        return [
            'requires_guardian' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(ContractSignature::class);
    }

    /**
     * @param  Builder<ContractTemplate>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', 'publicado');
    }

    public function isDraft(): bool
    {
        return $this->status === 'borrador';
    }

    public function isMandatory(): bool
    {
        return $this->acceptance_mode === 'obligatorio';
    }

    /**
     * Whether this template has to be signed for the given student (las
     * plantillas "solo menores" no aplican a estudiantes mayores de edad).
     */
    public function appliesTo(Student $student): bool
    {
        return ! $this->requires_guardian || $student->isMinor();
    }
}
