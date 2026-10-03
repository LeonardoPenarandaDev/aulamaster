<?php

namespace App\Models;

use Database\Factories\ContractSignatureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un contrato generado para un estudiante, con la copia exacta del texto,
 * su hash y la evidencia de la firma (parte 6.4 del plan de mejoras). Una
 * vez enviado o firmado no se modifica: se anula y se genera uno nuevo.
 */
#[Fillable([
    'enrollment_id',
    'student_id',
    'contract_template_id',
    'template_version',
    'status',
    'decision',
    'signing_method',
    'special_clauses',
    'rendered_body',
    'content_hash',
    'pdf_path',
    'signer_name',
    'signer_document',
    'signer_role',
    'signer_email',
    'signature_path',
    'signed_at',
    'signer_timezone',
    'ip',
    'user_agent',
    'otp_verified_at',
    'id_front_path',
    'id_back_path',
    'identity_verified_by_id',
    'link_token',
    'link_expires_at',
    'sent_via',
    'sent_at',
    'sent_by_id',
    'opened_at',
    'generated_by_id',
    'voided_at',
    'voided_by_id',
    'void_reason',
])]
#[Hidden(['link_token', 'signature_path', 'id_front_path', 'id_back_path', 'pdf_path'])]
class ContractSignature extends Model
{
    /** @use HasFactory<ContractSignatureFactory> */
    use HasFactory;

    /**
     * Estados en los que el contrato todavía espera firma.
     *
     * @var list<string>
     */
    public const OPEN_STATUSES = ['pendiente', 'enviado', 'abierto'];

    /**
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        'pendiente' => 'Generado',
        'enviado' => 'Enviado',
        'abierto' => 'Abierto',
        'firmado' => 'Firmado',
        'anulado' => 'Anulado',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
            'otp_verified_at' => 'datetime',
            'link_expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class, 'contract_template_id');
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_id');
    }

    public function identityVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'identity_verified_by_id');
    }

    /**
     * @param  Builder<ContractSignature>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', self::OPEN_STATUSES);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function isSigned(): bool
    {
        return $this->status === 'firmado';
    }

    public function hasIdPhotos(): bool
    {
        return $this->id_front_path !== null;
    }
}
