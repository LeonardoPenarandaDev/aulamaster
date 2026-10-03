<?php

namespace App\Console\Commands;

use App\Actions\Payments\ProcessMonthlyFees;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Corre a diario (routes/console.php): genera las mensualidades, envía los
 * recordatorios, marca la mora y avisa al personal (parte 8 del plan de
 * mejoras).
 */
#[Signature('app:generate-monthly-fees')]
#[Description('Genera mensualidades, envía recordatorios y marca los pagos vencidos (parte 8 del plan de mejoras).')]
class GenerateMonthlyFees extends Command
{
    public function handle(ProcessMonthlyFees $processMonthlyFees): int
    {
        $result = $processMonthlyFees->handle();

        $this->info("Mensualidades generadas: {$result['generated']}");
        $this->info("Recordatorios enviados: {$result['reminded']}");
        $this->info("Pagos vencidos hoy: {$result['overdue']}");
        $this->info("Alertas de mora larga: {$result['alerted']}");

        return self::SUCCESS;
    }
}
