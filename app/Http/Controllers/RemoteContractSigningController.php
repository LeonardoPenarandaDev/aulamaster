<?php

namespace App\Http\Controllers;

use App\Actions\Contracts\ResolveContractSigner;
use App\Actions\Contracts\SignContract;
use App\Models\ContractSignature;
use App\Models\Enrollment;
use App\Notifications\ContractVerificationCodeNotification;
use App\Services\ContractRenderer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Firma a distancia sin cuenta (parte 6.8 del plan de mejoras). El enlace
 * es una URL firmada que vence; además lleva un token que cambia en cada
 * envío, así los enlaces anteriores dejan de servir. Antes de firmar, el
 * firmante confirma un código que le llega por correo.
 */
class RemoteContractSigningController extends Controller
{
    /**
     * Show the contracts to sign. Al abrir el enlace queda marcado como
     * "Abierto" y el token se guarda en la sesión para los pasos siguientes.
     */
    public function show(Request $request, Enrollment $enrollment, ResolveContractSigner $resolveSigner, ContractRenderer $renderer): Response
    {
        $token = (string) $request->query('token');
        $contracts = $this->openContracts($enrollment, $token);

        $request->session()->put($this->sessionKey($enrollment), $token);

        $contracts->where('status', 'enviado')->each(fn (ContractSignature $signature) => $signature->update([
            'status' => 'abierto',
            'opened_at' => now(),
        ]));

        $enrollment->loadMissing(['student', 'level.course']);
        $signer = $resolveSigner->handle($enrollment->student);

        return Inertia::render('Contracts/SignRemote', [
            'enrollmentId' => $enrollment->id,
            'student' => $enrollment->student->name,
            'level' => trim(($enrollment->level?->course?->name ?? '').' '.($enrollment->level?->name ?? '')),
            'signer' => [
                'role' => $signer['role'],
                'name' => $signer['name'],
                'document' => $signer['document'],
                'email_hint' => $this->maskEmail($signer['email']),
            ],
            'contracts' => $contracts->map(fn (ContractSignature $signature) => [
                'id' => $signature->id,
                'name' => $signature->template->name,
                'is_optional' => $signature->template->acceptance_mode === 'opcional',
                'body_html' => $renderer->toHtml($signature->rendered_body)->toHtml(),
            ])->values(),
            'signedCount' => $enrollment->contractSignatures()->where('status', 'firmado')->count(),
            'otpVerified' => $this->otpVerifiedAt($request, $enrollment) !== null,
            'idPhotoRequired' => config('contracts.remote_id_photo_required'),
            'maxPhotoKb' => config('contracts.id_photo_max_kb'),
        ]);
    }

    /**
     * Send (or resend) the verification code to the signer's email, or check
     * the code typed by the signer.
     */
    public function code(Request $request, Enrollment $enrollment, ResolveContractSigner $resolveSigner): RedirectResponse
    {
        $token = $this->sessionToken($request, $enrollment);
        $this->openContracts($enrollment, $token);
        $cacheKey = "contract-otp:{$enrollment->id}:{$token}";

        if ($request->filled('code')) {
            $stored = Cache::get($cacheKey);

            if (! $stored || $stored['attempts'] >= config('contracts.otp_max_attempts')) {
                throw ValidationException::withMessages(['code' => 'El código venció. Pide uno nuevo.']);
            }

            if (! Hash::check((string) $request->input('code'), $stored['hash'])) {
                Cache::put($cacheKey, [...$stored, 'attempts' => $stored['attempts'] + 1], now()->addMinutes(config('contracts.otp_minutes')));

                throw ValidationException::withMessages(['code' => 'El código no es correcto.']);
            }

            Cache::forget($cacheKey);
            $request->session()->put($this->otpSessionKey($enrollment), ['token' => $token, 'at' => now()->toIso8601String()]);

            return back()->with('success', 'Correo verificado. Ya puedes firmar.');
        }

        $signer = $resolveSigner->handle($enrollment->student);
        $code = (string) random_int(100000, 999999);

        Cache::put($cacheKey, ['hash' => Hash::make($code), 'attempts' => 0], now()->addMinutes(config('contracts.otp_minutes')));
        Notification::route('mail', $signer['email'])->notify(new ContractVerificationCodeNotification($code, config('contracts.otp_minutes')));

        return back()->with('success', "Te enviamos un código a {$this->maskEmail($signer['email'])}.");
    }

    /**
     * Sign one contract.
     */
    public function sign(Request $request, Enrollment $enrollment, ContractSignature $contractSignature, ResolveContractSigner $resolveSigner, SignContract $sign): RedirectResponse
    {
        $token = $this->sessionToken($request, $enrollment);
        $contracts = $this->openContracts($enrollment, $token);
        abort_unless($contracts->contains($contractSignature), 404);

        $otpVerifiedAt = $this->otpVerifiedAt($request, $enrollment);
        if (! $otpVerifiedAt) {
            throw ValidationException::withMessages(['code' => 'Primero confirma el código que te enviamos por correo.']);
        }

        $photo = ['file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:'.config('contracts.id_photo_max_kb')];
        $validated = $request->validate([
            'signature_png' => ['required', 'string', 'max:2000000'],
            'accepted_terms' => ['accepted'],
            'decision' => [$contractSignature->template->acceptance_mode === 'opcional' ? 'required' : 'nullable', Rule::in(['acepta', 'no_acepta'])],
            'timezone' => ['nullable', 'string', 'max:64'],
            'id_front' => [config('contracts.remote_id_photo_required') ? 'required' : 'nullable', ...$photo],
            'id_back' => ['nullable', ...$photo],
        ], [
            'accepted_terms.accepted' => 'Marca "He leído y acepto" para firmar.',
            'decision.required' => 'Indica si aceptas o no.',
            'signature_png.required' => 'Falta tu firma.',
        ]);

        $signer = $resolveSigner->handle($enrollment->student);

        $sign->handle($contractSignature, [
            'method' => 'distancia',
            'signature_png' => $validated['signature_png'],
            'decision' => $validated['decision'] ?? 'acepta',
            'signer_name' => $signer['name'] ?? '',
            'signer_document' => $signer['document'],
            'signer_role' => $signer['role'],
            'signer_email' => $signer['email'],
            'timezone' => $validated['timezone'] ?? null,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'otp_verified_at' => $otpVerifiedAt,
            'id_front' => $request->file('id_front'),
            'id_back' => $request->file('id_back'),
        ]);

        return back()->with('success', "Firmaste «{$contractSignature->template->name}». Te enviamos una copia en PDF por correo.");
    }

    /**
     * Contratos todavía abiertos del enlace. El token sigue en los contratos
     * ya firmados, así el enlace muestra "todo firmado" en vez de un error.
     *
     * @return Collection<int, ContractSignature>
     */
    private function openContracts(Enrollment $enrollment, string $token): Collection
    {
        abort_if($token === '', 403, 'Enlace no válido.');

        $withToken = $enrollment->contractSignatures()
            ->where('link_token', $token)
            ->with('template')
            ->orderBy('id')
            ->get();

        abort_if($withToken->isEmpty(), 403, 'Este enlace ya no es válido. Pide a la institución que te envíe uno nuevo.');
        abort_if($withToken->every(fn (ContractSignature $signature) => $signature->link_expires_at?->isPast()), 403, 'Este enlace venció. Pide a la institución que te envíe uno nuevo.');

        return $withToken->filter(fn (ContractSignature $signature) => $signature->isOpen())->values();
    }

    private function sessionKey(Enrollment $enrollment): string
    {
        return "remote-contracts.{$enrollment->id}.token";
    }

    private function otpSessionKey(Enrollment $enrollment): string
    {
        return "remote-contracts.{$enrollment->id}.otp";
    }

    private function sessionToken(Request $request, Enrollment $enrollment): string
    {
        return (string) $request->session()->get($this->sessionKey($enrollment), '');
    }

    /**
     * Cuándo confirmó el código, solo si fue con el enlace vigente.
     */
    private function otpVerifiedAt(Request $request, Enrollment $enrollment): ?Carbon
    {
        $otp = $request->session()->get($this->otpSessionKey($enrollment));

        if (! is_array($otp) || ($otp['token'] ?? null) !== $this->sessionToken($request, $enrollment)) {
            return null;
        }

        return Carbon::parse($otp['at']);
    }

    private function maskEmail(?string $email): string
    {
        if (! $email || ! str_contains($email, '@')) {
            return '';
        }

        [$user, $domain] = explode('@', $email, 2);

        return mb_substr($user, 0, 2).str_repeat('•', max(1, mb_strlen($user) - 2)).'@'.$domain;
    }
}
