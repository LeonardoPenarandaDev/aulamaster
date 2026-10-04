<?php

namespace App\Http\Controllers;

use App\Actions\Contracts\GenerateEnrollmentContracts;
use App\Actions\Contracts\SendContractsForSigning;
use App\Actions\Pricing\CalculateEnrollmentPrice;
use App\Actions\Students\StoreStudentPhoto;
use App\Http\Requests\StoreEnrollmentWizardRequest;
use App\Models\ContractSignature;
use App\Models\ContractTemplate;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Promotion;
use App\Models\Referral;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Asistente de matrícula (parte 6.3 del plan de mejoras):
 * 1. Alumno (y acudiente si es menor) · 2. Nivel y precio · 3. Contratos
 * (se asignan solos) · 4. Resumen y forma de firmar.
 */
class EnrollmentWizardController extends Controller
{
    /**
     * Show the wizard.
     */
    public function create(): Response
    {
        Gate::authorize('create', Enrollment::class);
        Gate::authorize('manage', ContractSignature::class);

        $templates = ContractTemplate::query()->published()->orderBy('name')
            ->get(['id', 'code', 'name', 'acceptance_mode', 'scope', 'requires_guardian']);

        return Inertia::render('Enrollments/Wizard', [
            'students' => Student::query()->orderBy('name')->get([
                'id', 'code', 'name', 'photo_path', 'document_type', 'document', 'birth_date', 'email',
                'guardian_name', 'guardian_email',
            ])->map(fn (Student $student) => [
                ...$student->only(['id', 'code', 'name', 'email', 'guardian_name', 'guardian_email', 'photo_url']),
                'birth_date' => $student->birth_date?->toDateString(),
                'is_minor' => $student->isMinor(),
                'missing_contract_data' => $student->missingContractData(),
            ]),
            'signedPerStudentCodes' => ContractSignature::query()
                ->where('status', 'firmado')
                ->whereHas('template', fn ($query) => $query->where('scope', 'alumno'))
                ->with('template:id,code')
                ->get(['id', 'student_id', 'contract_template_id'])
                ->groupBy('student_id')
                ->map(fn ($signatures) => $signatures->pluck('template.code')->unique()->values()),
            'levels' => Level::query()->with(['course:id,name', 'previousLevel:id,name,next_level_id'])->orderBy('name')->get([
                'id', 'name', 'course_id', 'duration_months', 'required_hours', 'weekly_hours', 'price', 'monthly_fee',
            ]),
            'promotions' => Promotion::query()->where('status', 'activo')->orderBy('name')->get([
                'id', 'name', 'discount_type', 'value', 'course_id', 'level_id',
            ]),
            'referrals' => Referral::query()->with(['referrer:id,name', 'referred:id,name'])->get(),
            'templates' => $templates,
            'documentTypes' => Student::DOCUMENT_TYPES,
        ]);
    }

    /**
     * Create the student (if new), the pending enrollment and its contracts,
     * and continue with the chosen signing method.
     */
    public function store(
        StoreEnrollmentWizardRequest $request,
        CalculateEnrollmentPrice $calculatePrice,
        GenerateEnrollmentContracts $generateContracts,
        SendContractsForSigning $sendContracts,
        StoreStudentPhoto $storePhoto,
    ): RedirectResponse {
        $enrollment = DB::transaction(function () use ($request, $calculatePrice, $generateContracts, $storePhoto) {
            $student = $request->filled('student_id')
                ? Student::findOrFail($request->validated('student_id'))
                : Student::create([...$request->validated('new_student'), 'status' => 'activo']);

            if ($request->hasFile('photo')) {
                $storePhoto->handle($student, $request->file('photo'));
            }

            $level = Level::findOrFail($request->validated('level_id'));
            $promotion = $request->validated('promotion_id') ? Promotion::find($request->validated('promotion_id')) : null;
            $referral = $request->validated('referral_id') ? Referral::find($request->validated('referral_id')) : null;
            $basePrice = $request->validated('base_price');

            $enrollment = Enrollment::create([
                ...$request->safe()->only([
                    'level_id', 'enrolled_at', 'start_date', 'estimated_end_date', 'required_hours', 'weekly_hours', 'promotion_id', 'referral_id',
                ]),
                ...$calculatePrice->handle($level, $student, $promotion, $referral, $basePrice !== null ? (float) $basePrice : null),
                'student_id' => $student->id,
                'monthly_fee' => $request->validated('monthly_fee') ?? $level->monthly_fee,
                'status' => 'pendiente',
                'prerequisite_waived' => $request->isMissingPrerequisite() && $request->prerequisiteWaived(),
            ]);

            if ($student->missingContractData() === [] && $generateContracts->pendingTemplates($enrollment)->isNotEmpty()) {
                $generateContracts->handle($enrollment, $request->user(), $request->validated('special_clauses') ?? []);
            }

            return $enrollment;
        });

        $method = $request->validated('sign_method');
        $hasOpenContracts = $enrollment->contractSignatures()->open()->exists();

        if (! $hasOpenContracts || $method === 'despues') {
            return to_route('enrollments.edit', $enrollment)->with('success', $hasOpenContracts
                ? 'Matrícula creada como pendiente. Los contratos quedaron listos para firmar.'
                : 'Matrícula creada como pendiente.');
        }

        if ($method === 'oficina') {
            return to_route('enrollments.contracts.sign', $enrollment)->with('success', 'Matrícula creada. Firma los contratos.');
        }

        try {
            $result = $sendContracts->handle($enrollment, $method, $request->user());
        } catch (ValidationException $exception) {
            return to_route('enrollments.edit', $enrollment)
                ->with('error', 'Matrícula creada, pero no se pudieron enviar los contratos: '.collect($exception->errors())->flatten()->first());
        }

        return to_route('enrollments.edit', $enrollment)
            ->with('success', 'Matrícula creada y contratos enviados para firma.')
            ->with('contractLink', [
                'url' => $result['url'],
                'via' => $method,
                'expires_at' => $result['expires_at'],
                'phone' => $result['signer']['phone'],
                'signer_name' => $result['signer']['name'],
            ]);
    }
}
