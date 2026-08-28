<?php

namespace App\Http\Controllers;

use App\Exports\GenericReportExport;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Level;
use App\Models\Teacher;
use App\Models\User;
use App\Reports\ReportDefinition;
use App\Reports\ReportRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    protected array $filterKeys = ['from', 'to', 'status', 'course_id', 'level_id', 'teacher_id', 'classroom_id'];

    /**
     * El cajero (Fase 15) solo ve los reportes financieros: son el
     * corazón de su trabajo (matrículas, pagos, promociones, referidos),
     * a diferencia de los académicos/de asistencia que no le corresponden.
     *
     * @var array<int, string>
     */
    protected array $cajeroReportKeys = ['income', 'students-pending-payments', 'promotions-used', 'referrals', 'recoveries-paid'];

    /**
     * @return Collection<int, ReportDefinition>
     */
    protected function reportsFor(User $user): Collection
    {
        $all = ReportRegistry::all();

        return $user->hasRole('admin')
            ? $all
            : $all->filter(fn (ReportDefinition $report) => in_array($report->key(), $this->cajeroReportKeys, true))->values();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): InertiaResponse
    {
        return Inertia::render('Reports/Index', [
            'reports' => $this->reportsFor($request->user())
                ->map(fn ($report) => ['key' => $report->key(), 'label' => $report->label(), 'module' => $report->module()])
                ->groupBy('module'),
        ]);
    }

    /**
     * Display the report on screen, or export it when a "format" query param is present.
     */
    public function show(Request $request, string $report): InertiaResponse|BinaryFileResponse|HttpResponse
    {
        $definition = ReportRegistry::find($report) ?? abort(404);
        abort_unless($this->reportsFor($request->user())->contains(fn (ReportDefinition $r) => $r->key() === $report), 403);
        $filters = $request->only($this->filterKeys);
        $format = $request->query('format');

        if ($format === 'pdf') {
            return Pdf::loadView('reports.generic-pdf', [
                'label' => $definition->label(),
                'headings' => $definition->headings(),
                'rows' => $definition->rows($filters)->map(fn ($row) => $definition->toRow($row)),
            ])->download("{$definition->key()}.pdf");
        }

        if (in_array($format, ['xlsx', 'csv'], true)) {
            return Excel::download(
                new GenericReportExport($definition, $filters),
                "{$definition->key()}.{$format}",
            );
        }

        $rows = $definition->rows($filters)->map(fn ($row) => $definition->toRow($row));

        return Inertia::render('Reports/Show', [
            'report' => ['key' => $definition->key(), 'label' => $definition->label(), 'module' => $definition->module()],
            'headings' => $definition->headings(),
            'rows' => $rows,
            'filters' => $filters,
            'courses' => Course::query()->orderBy('name')->get(['id', 'name']),
            'levels' => Level::query()->orderBy('name')->get(['id', 'name']),
            'teachers' => Teacher::query()->orderBy('name')->get(['id', 'name']),
            'classrooms' => Classroom::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
