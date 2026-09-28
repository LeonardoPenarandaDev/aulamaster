<?php

namespace App\Actions\Scheduling;

use App\Models\ClassSchedule;
use App\Models\ClassSession;
use Illuminate\Support\Carbon;

class GenerateClassSessionsFromSchedule
{
    public function __construct(
        private readonly CheckClassSessionConflicts $checkConflicts,
    ) {}

    /**
     * Generate one ClassSession per matching weekday within the schedule's
     * date range. Dates that conflict with an existing session (same
     * classroom or teacher) are skipped rather than overwritten.
     *
     * @return array{created: int, skipped: int}
     */
    public function handle(ClassSchedule $schedule): array
    {
        $created = 0;
        $skipped = 0;

        $period = Carbon::parse($schedule->start_date)->toPeriod($schedule->end_date);

        foreach ($period as $date) {
            if (! in_array($date->dayOfWeekIso, $schedule->days_of_week, strict: false)) {
                continue;
            }

            $conflicts = $this->checkConflicts->handle(
                classroomId: $schedule->classroom_id,
                teacherId: $schedule->teacher_id,
                date: $date->toDateString(),
                startTime: $schedule->start_time,
                endTime: $schedule->end_time,
                modality: $schedule->modality ?? 'presencial',
            );

            if ($conflicts !== []) {
                $skipped++;

                continue;
            }

            ClassSession::create([
                'class_schedule_id' => $schedule->id,
                'level_id' => $schedule->level_id,
                'teacher_id' => $schedule->teacher_id,
                'classroom_id' => $schedule->classroom_id,
                'modality' => $schedule->modality ?? 'presencial',
                'date' => $date->toDateString(),
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'status' => 'programada',
            ]);
            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }
}
