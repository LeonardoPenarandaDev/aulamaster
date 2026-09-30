<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportStudentsRequest;
use App\Imports\StudentsImport;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentImportController extends Controller
{
    /**
     * Show the bulk import form.
     */
    public function create(): Response
    {
        Gate::authorize('create', Student::class);

        return Inertia::render('Students/Import', [
            'columns' => StudentsImport::COLUMNS,
            'maxRows' => StudentsImport::MAX_ROWS,
        ]);
    }

    /**
     * Download a CSV template with the expected headers and an example row.
     * Se incluye el BOM UTF-8 para que Excel muestre bien las tildes.
     */
    public function template(): StreamedResponse
    {
        Gate::authorize('create', Student::class);

        return response()->streamDownload(function () {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, StudentsImport::COLUMNS);
            fputcsv($output, ['EST-001', 'Ana Gómez', '1234567890', 'ana@correo.com', '3001234567', 'Calle 1 # 2-3', 'activo', '']);
            fclose($output);
        }, 'plantilla_estudiantes.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Import students from an uploaded CSV/Excel file.
     */
    public function store(ImportStudentsRequest $request): RedirectResponse
    {
        $import = new StudentsImport;

        DB::transaction(fn () => Excel::import($import, $request->file('file')));

        $message = "Se importaron {$import->createdCount} estudiantes";

        if ($import->portalAccessCount > 0) {
            $message .= " ({$import->portalAccessCount} con acceso al portal)";
        }

        return to_route('students.index')->with('success', "{$message}.");
    }
}
