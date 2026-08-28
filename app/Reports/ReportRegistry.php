<?php

namespace App\Reports;

use App\Reports\Definitions\AccumulatedHoursReport;
use App\Reports\Definitions\AttendanceByClassroomReport;
use App\Reports\Definitions\AttendanceByDateReport;
use App\Reports\Definitions\AttendanceByTeacherReport;
use App\Reports\Definitions\AuditReport;
use App\Reports\Definitions\ClassroomOccupancyReport;
use App\Reports\Definitions\EvaluationsApprovedReport;
use App\Reports\Definitions\EvaluationsFailedReport;
use App\Reports\Definitions\IncomeReport;
use App\Reports\Definitions\PromotionsUsedReport;
use App\Reports\Definitions\RecoveriesPaidReport;
use App\Reports\Definitions\RecoveriesReport;
use App\Reports\Definitions\ReferralsReport;
use App\Reports\Definitions\SchedulesReport;
use App\Reports\Definitions\StudentListReport;
use App\Reports\Definitions\StudentsByCourseReport;
use App\Reports\Definitions\StudentsByLevelReport;
use App\Reports\Definitions\StudentsNearEvaluationReport;
use App\Reports\Definitions\StudentsPendingPaymentsReport;
use Illuminate\Support\Collection;

/**
 * Catálogo de los reportes de la sección 45 del plan.
 */
class ReportRegistry
{
    /**
     * @return array<int, class-string<ReportDefinition>>
     */
    protected static array $reports = [
        StudentListReport::class,
        StudentsByCourseReport::class,
        StudentsByLevelReport::class,
        AttendanceByDateReport::class,
        AttendanceByTeacherReport::class,
        AttendanceByClassroomReport::class,
        AccumulatedHoursReport::class,
        StudentsNearEvaluationReport::class,
        EvaluationsApprovedReport::class,
        EvaluationsFailedReport::class,
        RecoveriesReport::class,
        RecoveriesPaidReport::class,
        StudentsPendingPaymentsReport::class,
        IncomeReport::class,
        PromotionsUsedReport::class,
        ReferralsReport::class,
        ClassroomOccupancyReport::class,
        SchedulesReport::class,
        AuditReport::class,
    ];

    /**
     * @return Collection<int, ReportDefinition>
     */
    public static function all(): Collection
    {
        return collect(static::$reports)->map(fn ($class) => app($class));
    }

    public static function find(string $key): ?ReportDefinition
    {
        return static::all()->first(fn (ReportDefinition $report) => $report->key() === $key);
    }
}
