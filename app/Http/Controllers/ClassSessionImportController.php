<?php

namespace App\Http\Controllers;

use App\Actions\Notifications\NotifyTeacher;
use App\Actions\Scheduling\ParseClassSessionImport;
use App\Exports\ClassSessionsTemplateExport;
use App\Models\ClassSession;
use App\Models\Teacher;
use App\Notifications\ClassesAssignedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Programar la semana de clases desde CSV/Excel (parte 11 del plan de
 * mejoras): subir → vista previa (correctas, sin docente y con errores) →
 * confirmar. Admin y coordinador.
 */
class ClassSessionImportController extends Controller
{
    private const CACHE_MINUTES = 60;

    /**
     * Show the upload form.
     */
    public function create(): Response
    {
        Gate::authorize('create', ClassSession::class);

        return Inertia::render('ClassSessions/Import', [
            'columns' => ParseClassSessionImport::COLUMNS,
            'nextMonday' => now()->next(Carbon::MONDAY)->toDateString(),
        ]);
    }

    /**
     * Download the .xlsx template with examples and dropdown lists.
     */
    public function template(): BinaryFileResponse
    {
        Gate::authorize('create', ClassSession::class);

        return Excel::download(new ClassSessionsTemplateExport, 'plantilla_horarios.xlsx');
    }

    /**
     * Parse the file and show the preview. Nada se guarda hasta confirmar.
     */
    public function preview(Request $request, ParseClassSessionImport $parse): Response|RedirectResponse
    {
        Gate::authorize('create', ClassSession::class);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:2048'],
            'week' => ['required', 'date'],
            'repeat_weeks' => ['required', 'integer', 'min:1', 'max:12'],
        ], [], ['file' => 'archivo', 'week' => 'semana', 'repeat_weeks' => 'semanas']);

        $sheets = Excel::toCollection(new class implements Import, WithHeadingRow {}, $request->file('file'));
        $rows = $sheets->first() ?? collect();

        if ($rows->count() > ParseClassSessionImport::MAX_ROWS) {
            return back()->withErrors(['file' => 'El archivo supera el máximo de '.ParseClassSessionImport::MAX_ROWS.' filas.']);
        }

        $weekStart = Carbon::parse($validated['week'])->startOfWeek(Carbon::MONDAY);
        $parsed = $parse->handle($rows, $weekStart, (int) $validated['repeat_weeks']);

        if ($parsed === []) {
            return back()->withErrors(['file' => 'El archivo no tiene clases.']);
        }

        $token = (string) Str::uuid();
        Cache::put($this->cacheKey($token), [
            'user_id' => $request->user()->id,
            'week' => $weekStart->toDateString(),
            'rows' => $parsed,
        ], now()->addMinutes(self::CACHE_MINUTES));

        $valid = collect($parsed)->where('status', '!=', 'error');

        return Inertia::render('ClassSessions/ImportPreview', [
            'token' => $token,
            'week' => $weekStart->toDateString(),
            'repeatWeeks' => (int) $validated['repeat_weeks'],
            'rows' => $parsed,
            'summary' => [
                'ok' => collect($parsed)->where('status', 'ok')->count(),
                'sin_docente' => collect($parsed)->where('status', 'sin_docente')->count(),
                'error' => collect($parsed)->where('status', 'error')->count(),
                'sessions' => $valid->sum(fn (array $row) => count($row['dates'])),
            ],
        ]);
    }

    /**
     * Create the sessions of the valid rows and notify the assigned teachers.
     */
    public function store(Request $request, NotifyTeacher $notifyTeacher): RedirectResponse
    {
        Gate::authorize('create', ClassSession::class);

        $import = $this->cachedImport($request, (string) $request->input('token'));
        $created = collect();

        DB::transaction(function () use ($import, &$created) {
            foreach ($import['rows'] as $row) {
                if ($row['status'] === 'error') {
                    continue;
                }

                foreach ($row['dates'] as $date) {
                    $created->push(ClassSession::create([
                        ...collect($row['data'])->only(['level_id', 'classroom_id', 'teacher_id', 'modality', 'meeting_url', 'notes', 'start_time', 'end_time'])->all(),
                        'date' => $date,
                        'status' => 'programada',
                    ]));
                }
            }
        });

        Cache::forget($this->cacheKey((string) $request->input('token')));

        $created->whereNotNull('teacher_id')->groupBy('teacher_id')->each(
            fn ($sessions, $teacherId) => $notifyTeacher->handle(Teacher::find($teacherId), new ClassesAssignedNotification($sessions->count(), $sessions->min('date')->toDateString()))
        );

        $withoutTeacher = $created->whereNull('teacher_id')->count();

        return to_route('calendar.index', ['date' => $import['week']])->with('success', "Se programaron {$created->count()} clases".($withoutTeacher ? " ({$withoutTeacher} sin docente: asígnalo desde el calendario)." : '.'));
    }

    /**
     * Download the rows with errors to fix them and upload them again.
     */
    public function errors(Request $request, string $token): StreamedResponse
    {
        Gate::authorize('create', ClassSession::class);

        $import = $this->cachedImport($request, $token);

        return response()->streamDownload(function () use ($import) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [...ParseClassSessionImport::COLUMNS, 'errores']);

            foreach ($import['rows'] as $row) {
                if ($row['status'] === 'error') {
                    fputcsv($output, [...array_values($row['raw']), implode(' ', $row['errors'])]);
                }
            }

            fclose($output);
        }, 'horarios_con_errores.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{user_id: int, week: string, rows: list<array<string, mixed>>}
     */
    private function cachedImport(Request $request, string $token): array
    {
        $import = $token !== '' ? Cache::get($this->cacheKey($token)) : null;

        abort_unless($import && $import['user_id'] === $request->user()->id, 410, 'La vista previa venció. Sube el archivo de nuevo.');

        return $import;
    }

    private function cacheKey(string $token): string
    {
        return "class-session-import:{$token}";
    }
}
