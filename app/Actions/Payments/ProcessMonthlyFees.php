<?php

namespace App\Actions\Payments;

use App\Actions\Notifications\NotifyStaff;
use App\Actions\Notifications\NotifyStudent;
use App\Models\Enrollment;
use App\Models\InstitutionSetting;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\LongOverdueAlertNotification;
use App\Notifications\MonthlyFeeGeneratedNotification;
use App\Notifications\MonthlyFeeReminderNotification;
use App\Notifications\PaymentOverdueNotification;
use App\Notifications\StudentOverdueStaffNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Calendario mensual de la parte 8 del plan de mejoras. Se ejecuta a diario
 * y cada paso es idempotente, así que si el servidor estuvo apagado un día
 * se pone al día en la siguiente ejecución:
 *
 * - Día 1: se genera la mensualidad (vence el día límite, 5 por defecto).
 * - Días antes del vencimiento: recordatorio.
 * - Día siguiente al vencimiento: pasa a "vencido" (el estudiante queda
 *   bloqueado) y se avisa al cajero, la secretaria y el admin.
 * - Tras los días de mora configurados (10): alerta al admin.
 */
class ProcessMonthlyFees
{
    /**
     * @var list<string>
     */
    public const BILLABLE_STATUSES = ['activa', 'en_recuperacion', 'extendida'];

    public function __construct(
        protected NotifyStudent $notifyStudent,
        protected NotifyStaff $notifyStaff,
    ) {}

    /**
     * @return array{generated: int, reminded: int, overdue: int, alerted: int}
     */
    public function handle(?CarbonInterface $today = null): array
    {
        $today = ($today ?? now())->copy()->startOfDay();
        $settings = InstitutionSetting::current();

        return [
            'generated' => $this->generate($today, $settings),
            'reminded' => $this->remind($today, $settings),
            'overdue' => $this->markOverdue($today),
            'alerted' => $this->alertLongOverdue($today, $settings),
        ];
    }

    /**
     * Genera la mensualidad del mes para cada matrícula vigente que todavía
     * no la tenga. Una matrícula que empieza después del día límite no paga
     * ese mes: su primera mensualidad es la del mes siguiente.
     */
    public function generate(CarbonInterface $today, InstitutionSetting $settings): int
    {
        $period = $today->format('Y-m');
        $dueDate = $this->dueDateFor($today, $settings);
        $systemUserId = $this->systemUserId();
        $generated = 0;

        Enrollment::query()
            ->whereIn('status', self::BILLABLE_STATUSES)
            ->whereDate('start_date', '<=', $dueDate)
            ->whereDoesntHave('payments', fn ($query) => $query->where('type', 'mensualidad')->where('period', $period))
            ->with(['level', 'student'])
            ->each(function (Enrollment $enrollment) use ($period, $dueDate, $systemUserId, &$generated) {
                $fee = $enrollment->effectiveMonthlyFee();

                if (! $fee || $fee <= 0 || ! $systemUserId) {
                    return;
                }

                $payment = Payment::create([
                    'student_id' => $enrollment->student_id,
                    'enrollment_id' => $enrollment->id,
                    'type' => 'mensualidad',
                    'period' => $period,
                    'due_date' => $dueDate->toDateString(),
                    'concept' => 'Mensualidad '.$dueDate->locale('es')->translatedFormat('F Y').' · '.$enrollment->level->name,
                    'base_amount' => $fee,
                    'discount_amount' => 0,
                    'final_amount' => $fee,
                    'status' => 'pendiente',
                    'registered_by_id' => $systemUserId,
                ]);

                $this->notifyStudent->handleWithGuardian($enrollment->student, new MonthlyFeeGeneratedNotification($payment));
                $generated++;
            });

        return $generated;
    }

    public function remind(CarbonInterface $today, InstitutionSetting $settings): int
    {
        $payments = Payment::query()
            ->where('type', 'mensualidad')
            ->where('status', 'pendiente')
            ->whereNull('reminder_sent_at')
            ->whereDate('due_date', '>=', $today)
            ->whereDate('due_date', '<=', $today->copy()->addDays($settings->payment_reminder_days))
            ->with('student')
            ->get();

        foreach ($payments as $payment) {
            $payment->update(['reminder_sent_at' => now()]);
            $this->notifyStudent->handleWithGuardian($payment->student, new MonthlyFeeReminderNotification($payment));
        }

        return $payments->count();
    }

    /**
     * Pasa a "vencido" lo que no se pagó hasta el día límite: el estudiante
     * queda bloqueado hasta pagar o firmar un acuerdo de pago.
     */
    public function markOverdue(CarbonInterface $today): int
    {
        $payments = Payment::query()
            ->whereIn('type', ['mensualidad', 'matricula'])
            ->where('status', 'pendiente')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->with('student')
            ->get();

        foreach ($payments as $payment) {
            DB::transaction(fn () => $payment->update(['status' => 'vencido']));

            $this->notifyStudent->handleWithGuardian($payment->student, new PaymentOverdueNotification($payment));
            $this->notifyStaff->handle(['cajero', 'secretaria', 'admin'], new StudentOverdueStaffNotification($payment));
        }

        return $payments->count();
    }

    public function alertLongOverdue(CarbonInterface $today, InstitutionSetting $settings): int
    {
        $payments = Payment::query()
            ->overdue()
            ->whereNull('overdue_alert_sent_at')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $today->copy()->subDays($settings->overdue_alert_days))
            ->with('student')
            ->get();

        foreach ($payments as $payment) {
            $payment->update(['overdue_alert_sent_at' => now()]);
            $days = (int) $payment->due_date->copy()->startOfDay()->diffInDays($today);
            $this->notifyStaff->handle(['admin'], new LongOverdueAlertNotification($payment, $days));
        }

        return $payments->count();
    }

    public function dueDateFor(CarbonInterface $date, InstitutionSetting $settings): CarbonInterface
    {
        $month = $date->copy()->startOfMonth();

        return $month->setDay(min($settings->payment_due_day, $month->daysInMonth));
    }

    /**
     * Los pagos generados por el sistema quedan registrados a nombre del
     * primer administrador activo (registered_by_id es obligatorio).
     */
    protected function systemUserId(): ?int
    {
        return User::role('admin')->where('is_active', true)->orderBy('id')->value('id');
    }
}
