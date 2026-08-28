<?php

namespace App\Console\Commands;

use App\Actions\Notifications\NotifyStudent;
use App\Actions\Notifications\NotifyTeacher;
use App\Actions\Recovery\GetPendingRecoveries;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Student;
use App\Notifications\HoursBehindNotification;
use App\Notifications\RecoveryDeadlineApproachingNotification;
use App\Notifications\UpcomingClassNotification;
use App\Notifications\UpcomingEvaluationNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Cubre los avisos "periódicos" de la sección 47 del plan que no ocurren
 * como reacción inmediata a una acción del admin (esos ya se disparan desde
 * los controladores/actions correspondientes): próxima clase, evaluación
 * próxima, recuperación por vencer, y falta de horas. Pensado para correr
 * una vez al día vía el scheduler (routes/console.php).
 */
#[Signature('app:send-notification-reminders')]
#[Description('Envía los recordatorios periódicos de la sección 47 del plan (próxima clase, evaluación próxima, recuperación por vencer, horas atrasadas).')]
class SendNotificationReminders extends Command
{
    public function handle(
        NotifyStudent $notifyStudent,
        NotifyTeacher $notifyTeacher,
        GetPendingRecoveries $getPendingRecoveries,
    ): int {
        $this->remindUpcomingClasses($notifyStudent, $notifyTeacher);
        $this->remindUpcomingEvaluations($notifyStudent);
        $this->remindRecoveryDeadlines($notifyStudent, $getPendingRecoveries);
        $this->remindHoursBehind($notifyStudent);

        return self::SUCCESS;
    }

    protected function remindUpcomingClasses(NotifyStudent $notifyStudent, NotifyTeacher $notifyTeacher): void
    {
        $sessions = ClassSession::query()
            ->whereDate('date', today()->addDay())
            ->where('status', 'programada')
            ->with('teacher')
            ->get();

        foreach ($sessions as $session) {
            $studentIds = Enrollment::query()
                ->where('level_id', $session->level_id)
                ->whereIn('status', ['activa', 'en_recuperacion', 'extendida'])
                ->pluck('student_id');

            Student::query()->whereIn('id', $studentIds)->each(
                fn ($student) => $notifyStudent->handle($student, new UpcomingClassNotification($session))
            );

            $notifyTeacher->handle($session->teacher, new UpcomingClassNotification($session));
        }

        $this->info("Recordatorios de próxima clase: {$sessions->count()} clase(s) de mañana.");
    }

    protected function remindUpcomingEvaluations(NotifyStudent $notifyStudent): void
    {
        $count = 0;

        Enrollment::query()
            ->whereIn('status', ['activa', 'en_recuperacion', 'extendida'])
            ->whereColumn('accumulated_hours', '>=', 'required_hours')
            ->with(['level.evaluations' => fn ($q) => $q->where('status', 'activo'), 'student'])
            ->get()
            ->each(function (Enrollment $enrollment) use ($notifyStudent, &$count) {
                $presentedIds = $enrollment->evaluationResults()->pluck('evaluation_id')->unique();
                $pending = $enrollment->level->evaluations->whereNotIn('id', $presentedIds);

                foreach ($pending as $evaluation) {
                    $notifyStudent->handle($enrollment->student, new UpcomingEvaluationNotification($enrollment, $evaluation));
                    $count++;
                }
            });

        $this->info("Recordatorios de evaluación próxima: {$count}.");
    }

    protected function remindRecoveryDeadlines(NotifyStudent $notifyStudent, GetPendingRecoveries $getPendingRecoveries): void
    {
        $count = 0;

        $getPendingRecoveries->handle()
            ->filter(fn ($item) => ! $item['overdue'] && today()->diffInDays($item['deadline'], false) <= 3)
            ->each(function ($item) use ($notifyStudent, &$count) {
                $daysRemaining = (int) today()->diffInDays($item['deadline'], false);

                $notifyStudent->handle(
                    $item['enrollment']->student,
                    new RecoveryDeadlineApproachingNotification($item['evaluation'], $item['deadline'], $daysRemaining),
                );
                $count++;
            });

        $this->info("Recordatorios de fecha límite de recuperación: {$count}.");
    }

    protected function remindHoursBehind(NotifyStudent $notifyStudent): void
    {
        $enrollments = Enrollment::query()
            ->whereIn('status', ['activa', 'en_recuperacion', 'extendida'])
            ->whereColumn('accumulated_hours', '<', 'required_hours')
            ->whereNotNull('estimated_end_date')
            ->where('estimated_end_date', '<=', today()->addWeek())
            ->with('student')
            ->get();

        foreach ($enrollments as $enrollment) {
            $notifyStudent->handle($enrollment->student, new HoursBehindNotification($enrollment));
        }

        $this->info("Recordatorios de horas atrasadas: {$enrollments->count()}.");
    }
}
