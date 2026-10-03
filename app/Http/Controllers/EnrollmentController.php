<?php

namespace App\Http\Controllers;

use App\Actions\Contracts\GenerateEnrollmentContracts;
use App\Actions\Contracts\ResolveContractSigner;
use App\Actions\Evaluations\PromoteToNextLevel;
use App\Actions\Pricing\CalculateEnrollmentPrice;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Requests\UpdateEnrollmentRequest;
use App\Models\AuditLog;
use App\Models\ContractSignature;
use App\Models\Enrollment;
use App\Models\InstitutionSetting;
use App\Models\Level;
use App\Models\Promotion;
use App\Models\Referral;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Enrollment::class);

        return Inertia::render('Enrollments/Index', [
            'enrollments' => Enrollment::query()
                ->with(['student:id,name,code', 'level.course:id,name'])
                ->when(request('student_id'), fn ($query, $id) => $query->where('student_id', $id))
                ->when(request('level_id'), fn ($query, $id) => $query->where('level_id', $id))
                ->when(request('status'), fn ($query, $status) => $query->where('status', $status))
                ->when(request('contracts') === 'pendientes', fn ($query) => $query->whereHas('contractSignatures', fn ($query) => $query->open()))
                ->withCount(['contractSignatures as open_contracts_count' => fn ($query) => $query->open()])
                ->orderByDesc('enrolled_at')
                ->paginate(15)
                ->withQueryString(),
            'filters' => request()->only('student_id', 'level_id', 'status', 'contracts'),
            'students' => Student::query()->orderBy('name')->get(['id', 'name', 'code']),
            'levels' => Level::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', Enrollment::class);

        return Inertia::render('Enrollments/Create', [
            'students' => Student::query()->orderBy('name')->get(['id', 'name', 'code']),
            'levels' => Level::query()->with(['course:id,name', 'previousLevel:id,name,next_level_id'])->orderBy('name')->get([
                'id', 'name', 'course_id', 'duration_months', 'required_hours', 'weekly_hours', 'price', 'monthly_fee',
            ]),
            'promotions' => Promotion::query()->where('status', 'activo')->orderBy('name')->get([
                'id', 'name', 'discount_type', 'value', 'course_id', 'level_id',
            ]),
            'referrals' => Referral::query()->with(['referrer:id,name', 'referred:id,name'])->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEnrollmentRequest $request, CalculateEnrollmentPrice $calculatePrice): RedirectResponse
    {
        $student = Student::findOrFail($request->validated('student_id'));
        $level = Level::findOrFail($request->validated('level_id'));
        $promotion = $request->validated('promotion_id') ? Promotion::find($request->validated('promotion_id')) : null;
        $referral = $request->validated('referral_id') ? Referral::find($request->validated('referral_id')) : null;

        $basePrice = $request->validated('base_price');
        $pricing = $calculatePrice->handle($level, $student, $promotion, $referral, $basePrice !== null ? (float) $basePrice : null);

        Enrollment::create([
            ...$request->safe()->except('skip_prerequisite'),
            ...$pricing,
            'monthly_fee' => $request->validated('monthly_fee') ?? $level->monthly_fee,
            'prerequisite_waived' => $request->isMissingPrerequisite() && $request->prerequisiteWaived(),
        ]);

        return to_route('enrollments.index')->with('success', 'Matrícula registrada correctamente.');
    }

    /**
     * Botón manual "Pasar al siguiente nivel" (parte 5 del plan de mejoras),
     * por si la matrícula se aprobó antes de configurar la ruta de niveles.
     */
    public function promote(Enrollment $enrollment, PromoteToNextLevel $promoteToNextLevel): RedirectResponse
    {
        Gate::authorize('update', $enrollment);

        if ($enrollment->status !== 'aprobada') {
            return back()->with('error', 'Solo se puede pasar al siguiente nivel una matrícula aprobada.');
        }

        if (! $enrollment->level->next_level_id) {
            return back()->with('error', 'Este nivel no tiene un nivel siguiente configurado.');
        }

        $nextEnrollment = $promoteToNextLevel->handle($enrollment);

        if (! $nextEnrollment) {
            return back()->with('error', 'El estudiante ya tiene una matrícula en el nivel siguiente.');
        }

        return to_route('enrollments.edit', $nextEnrollment)->with('success', 'Matrícula del siguiente nivel creada como pendiente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Enrollment $enrollment, GenerateEnrollmentContracts $generateContracts, ResolveContractSigner $resolveSigner): Response
    {
        Gate::authorize('update', $enrollment);

        $enrollment->load([
            'extensions.extendedBy:id,name',
            'promotion:id,name',
            'referral.referrer:id,name',
            'level.nextLevel:id,name',
            'nextEnrollment:id,previous_enrollment_id,level_id,status',
            'previousEnrollment.level:id,name',
        ]);

        return Inertia::render('Enrollments/Edit', [
            'enrollment' => [
                ...$enrollment->toArray(),
                'enrolled_at' => $enrollment->enrolled_at->toDateString(),
                'start_date' => $enrollment->start_date->toDateString(),
                'estimated_end_date' => $enrollment->estimated_end_date?->toDateString(),
                'actual_end_date' => $enrollment->actual_end_date?->toDateString(),
            ],
            'students' => Student::query()->orderBy('name')->get(['id', 'name', 'code']),
            'levels' => Level::query()->with('course:id,name')->orderBy('name')->get([
                'id', 'name', 'course_id', 'duration_months', 'required_hours', 'weekly_hours', 'price', 'monthly_fee',
            ]),
            'contracts' => Gate::allows('manage', ContractSignature::class)
                ? $this->contractsSummary($enrollment, $generateContracts, $resolveSigner)
                : null,
        ]);
    }

    /**
     * Pestaña "Contratos" de la matrícula (parte 6.5 del plan de mejoras).
     *
     * @return array<string, mixed>
     */
    private function contractsSummary(Enrollment $enrollment, GenerateEnrollmentContracts $generateContracts, ResolveContractSigner $resolveSigner): array
    {
        $enrollment->loadMissing('student');

        return [
            'items' => $enrollment->contractSignatures()
                ->with(['template:id,name,acceptance_mode', 'sentBy:id,name'])
                ->orderBy('id')
                ->get()
                ->map(fn (ContractSignature $signature) => [
                    'id' => $signature->id,
                    'name' => $signature->template->name,
                    'version' => $signature->template_version,
                    'is_optional' => $signature->template->acceptance_mode === 'opcional',
                    'status' => $signature->status,
                    'status_label' => ContractSignature::STATUS_LABELS[$signature->status],
                    'decision' => $signature->decision,
                    'signing_method' => $signature->signing_method,
                    'signer_name' => $signature->signer_name,
                    'signer_role' => $signature->signer_role,
                    'signed_at' => $signature->signed_at?->format('Y-m-d H:i'),
                    'sent_via' => $signature->sent_via,
                    'sent_at' => $signature->sent_at?->format('Y-m-d H:i'),
                    'opened_at' => $signature->opened_at?->format('Y-m-d H:i'),
                    'link_expires_at' => $signature->link_expires_at?->toDateString(),
                    'has_id_photos' => $signature->hasIdPhotos(),
                    'has_id_back' => $signature->id_back_path !== null,
                    'special_clauses' => $signature->special_clauses,
                    'void_reason' => $signature->void_reason,
                ]),
            'pendingTemplates' => $generateContracts->pendingTemplates($enrollment)
                ->map->only(['id', 'name', 'acceptance_mode', 'scope'])
                ->values(),
            'signer' => $resolveSigner->handle($enrollment->student),
            'missingData' => $enrollment->student->missingContractData(),
        ];
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEnrollmentRequest $request, Enrollment $enrollment): RedirectResponse
    {
        $enrollment->update($request->validated());

        return to_route('enrollments.index')->with('success', 'Matrícula actualizada correctamente.');
    }

    /**
     * Emitir el certificado de nivel aprobado en PDF (Fase 16 del checklist).
     * Siempre bajo pedido del admin, nunca automático; cada emisión queda
     * registrada en Auditoría aunque no exista un cambio de modelo que el
     * AuditObserver pueda capturar por su cuenta.
     */
    public function certificate(Enrollment $enrollment): HttpResponse
    {
        Gate::authorize('view', $enrollment);

        abort_unless($enrollment->status === 'aprobada', 422, 'El certificado solo puede emitirse para matrículas aprobadas.');

        $enrollment->load(['student', 'level.course']);
        $institution = InstitutionSetting::current();

        $pdf = Pdf::loadView('certificates.enrollment', [
            'enrollment' => $enrollment,
            'institution' => $institution,
            'logoPath' => $institution->logo_path ? Storage::disk('public')->path($institution->logo_path) : null,
        ]);

        $user = Auth::user();
        AuditLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'role' => $user?->getRoleNames()->first(),
            'action' => 'emitido',
            'module' => 'certificados',
            'auditable_type' => Enrollment::class,
            'auditable_id' => $enrollment->id,
            'description' => "Certificado de {$enrollment->student->name} ({$enrollment->level->name}) emitido",
            'old_values' => null,
            'new_values' => null,
            'ip_address' => request()->ip(),
        ]);

        $fileName = Str::slug($enrollment->student->name).'-'.Str::slug($enrollment->level->name).'.pdf';

        return $pdf->download($fileName);
    }
}
