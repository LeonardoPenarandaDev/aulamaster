<?php

namespace App\Models;

use App\Actions\Enrollments\ActivateEnrollmentIfReady;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_id',
    'enrollment_id',
    'type',
    'period',
    'due_date',
    'concept',
    'base_amount',
    'discount_amount',
    'final_amount',
    'promotion_id',
    'referral_id',
    'paid_at',
    'payment_method',
    'receipt_reference',
    'status',
    'registered_by_id',
    'gateway',
    'gateway_reference',
    'gateway_transaction_id',
    'gateway_status',
    'reminder_sent_at',
    'overdue_alert_sent_at',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * Al quedar pagado un pago de una matrícula pendiente, se revisa si ya
     * puede activarse (parte 7 del plan de mejoras). Cubre el registro
     * manual del cajero y la confirmación de Wompi.
     */
    protected static function booted(): void
    {
        static::saved(function (Payment $payment) {
            $becamePaid = $payment->status === 'pagado' && ($payment->wasRecentlyCreated || $payment->wasChanged('status'));

            if ($payment->enrollment_id && $becamePaid) {
                app(ActivateEnrollmentIfReady::class)->handle($payment->enrollment);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'paid_at' => 'date',
            'due_date' => 'date',
            'reminder_sent_at' => 'datetime',
            'overdue_alert_sent_at' => 'datetime',
        ];
    }

    /**
     * @var array<string, string>
     */
    public const TYPES = [
        'matricula' => 'Matrícula',
        'mensualidad' => 'Mensualidad',
        'otro' => 'Otro',
    ];

    /**
     * Pagos en mora: vencidos y de matrícula o mensualidad. Los de tipo
     * "otro" (incluidos los anteriores a las mensualidades) no activan la
     * mora (parte 8 del plan de mejoras).
     *
     * @param  Builder<Payment>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->where('status', 'vencido')->where('type', '!=', 'otro');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_id');
    }
}
