<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\ContractSignature;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Student::class);

        return Inertia::render('Students/Index', [
            'students' => Student::query()
                ->when(request('search'), fn ($query, $search) => $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                ))
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString()
                ->through(fn (Student $student) => [
                    ...$student->toArray(),
                    'is_minor' => $student->isMinor(),
                    'missing_contract_data' => $student->missingContractData(),
                ]),
            'filters' => request()->only('search'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', Student::class);

        return Inertia::render('Students/Create', [
            'documentTypes' => Student::DOCUMENT_TYPES,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudentRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $student = Student::create($request->safe()->except('password'));

            if ($request->filled('password')) {
                $user = User::create([
                    'name' => $student->name,
                    'email' => $student->email,
                    'password' => $request->validated('password'),
                ]);
                $user->assignRole('estudiante');
                $student->update(['user_id' => $user->id]);
            }
        });

        return to_route('students.index')->with('success', $request->filled('password')
            ? 'Estudiante registrado con acceso al portal.'
            : 'Estudiante registrado correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Student $student): Response
    {
        Gate::authorize('update', $student);

        return Inertia::render('Students/Edit', [
            'student' => [
                ...$student->load('user:id,email')->toArray(),
                'birth_date' => $student->birth_date?->toDateString(),
            ],
            'documentTypes' => Student::DOCUMENT_TYPES,
            'signedContracts' => $student->contractSignatures()
                ->where('status', 'firmado')
                ->with(['template:id,name,acceptance_mode', 'enrollment.level:id,name'])
                ->latest('signed_at')
                ->get()
                ->map(fn (ContractSignature $signature) => [
                    'id' => $signature->id,
                    'name' => $signature->template->name,
                    'version' => $signature->template_version,
                    'level' => $signature->enrollment?->level?->name,
                    'signer_name' => $signature->signer_name,
                    'signer_role' => $signature->signer_role,
                    'signed_at' => $signature->signed_at?->format('Y-m-d H:i'),
                    'decision' => $signature->template->acceptance_mode === 'opcional' ? $signature->decision : null,
                ]),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $student->update($request->validated());

        return to_route('students.index')->with('success', 'Estudiante actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student): RedirectResponse
    {
        Gate::authorize('delete', $student);

        if ($blocked = $this->deleteOrBlock($student, 'No se puede eliminar el estudiante: tiene matrículas, pagos o referidos registrados.')) {
            return $blocked;
        }

        return to_route('students.index')->with('success', 'Estudiante eliminado correctamente.');
    }
}
