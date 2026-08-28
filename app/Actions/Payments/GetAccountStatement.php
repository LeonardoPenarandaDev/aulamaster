<?php

namespace App\Actions\Payments;

use App\Models\Student;

class GetAccountStatement
{
    /**
     * Estado de cuenta de un estudiante (sección 34 del plan): total
     * facturado, total pagado, saldo pendiente y estado general.
     *
     * @return array{total_billed: float, total_paid: float, balance: float, status: string}
     */
    public function handle(Student $student): array
    {
        $payments = $student->payments()->where('status', '!=', 'anulado')->get();

        $totalBilled = (float) $payments->sum('final_amount');
        $totalPaid = (float) $payments->where('status', 'pagado')->sum('final_amount');
        $balance = round($totalBilled - $totalPaid, 2);

        $hasOverdue = $payments->where('status', 'vencido')->isNotEmpty();

        $status = match (true) {
            $balance <= 0 => 'al_dia',
            $hasOverdue => 'vencido',
            default => 'pendiente',
        };

        return [
            'total_billed' => $totalBilled,
            'total_paid' => $totalPaid,
            'balance' => $balance,
            'status' => $status,
        ];
    }
}
