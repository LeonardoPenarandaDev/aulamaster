<?php

namespace App\Models;

use Database\Factories\ChecklistItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Requisito del checklist de la gestión académica que define el
 * administrador. Sin `automatic_check` es manual: lo marca el personal.
 */
#[Fillable(['name', 'description', 'automatic_check', 'position', 'is_active'])]
class ChecklistItem extends Model
{
    /** @use HasFactory<ChecklistItemFactory> */
    use HasFactory;

    /**
     * Revisiones que la plataforma hace sola.
     *
     * @var array<string, string>
     */
    public const AUTOMATIC_CHECKS = [
        'contratos_firmados' => 'Contratos firmados (todos los generados)',
        'foto_perfil' => 'Foto de perfil cargada',
        'acceso_plataforma' => 'Acceso a la plataforma creado',
        'matricula_pagada' => 'Pago de la matrícula registrado',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function marks(): HasMany
    {
        return $this->hasMany(StudentChecklistMark::class);
    }

    /**
     * @param  Builder<ChecklistItem>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('position')->orderBy('id');
    }

    public function isManual(): bool
    {
        return $this->automatic_check === null;
    }
}
