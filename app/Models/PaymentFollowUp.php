<?php

namespace App\Models;

use Database\Factories\PaymentFollowUpFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contacto registrado desde la cartera en mora (parte 8 del plan de mejoras).
 */
#[Fillable(['student_id', 'channel', 'result', 'note', 'contacted_by_id', 'contacted_at'])]
class PaymentFollowUp extends Model
{
    /** @use HasFactory<PaymentFollowUpFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    public const CHANNELS = [
        'whatsapp' => 'WhatsApp',
        'llamada' => 'Llamada',
        'correo' => 'Correo',
        'presencial' => 'En persona',
    ];

    /**
     * @var array<string, string>
     */
    public const RESULTS = [
        'contactado' => 'Contactado',
        'promesa_pago' => 'Prometió pagar',
        'no_contesta' => 'No contesta',
        'numero_errado' => 'Número equivocado',
        'otro' => 'Otro',
    ];

    protected function casts(): array
    {
        return [
            'contacted_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function contactedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contacted_by_id');
    }
}
