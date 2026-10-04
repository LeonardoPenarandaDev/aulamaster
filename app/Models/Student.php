<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'code',
    'name',
    'document_type',
    'document',
    'birth_date',
    'email',
    'phone',
    'address',
    'status',
    'guardian_name',
    'guardian_document_type',
    'guardian_document',
    'guardian_relationship',
    'guardian_email',
    'guardian_phone',
])]
#[Hidden(['photo_path'])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    /**
     * Tipos de documento de identidad usados en Colombia.
     *
     * @var array<string, string>
     */
    public const DOCUMENT_TYPES = [
        'CC' => 'Cédula de ciudadanía',
        'TI' => 'Tarjeta de identidad',
        'RC' => 'Registro civil',
        'CE' => 'Cédula de extranjería',
        'PA' => 'Pasaporte',
        'PPT' => 'Permiso por protección temporal',
    ];

    public const ADULT_AGE = 18;

    /**
     * Tamaño máximo de la foto de perfil que se sube, en KB (luego se reduce).
     */
    public const PHOTO_MAX_KB = 8192;

    protected $appends = ['photo_url'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'image_consent' => 'boolean',
            'image_consent_updated_at' => 'datetime',
        ];
    }

    /**
     * Dirección de la foto de perfil (privada, la sirve StudentPhotoController).
     * Cambia cada vez que se reemplaza la foto, así el navegador no muestra
     * la anterior.
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->photo_path
            ? route('students.photo', ['student' => $this->id, 'v' => substr(md5($this->photo_path), 0, 8)], false)
            : null);
    }

    /**
     * @return list<string>
     */
    public static function photoRules(bool $required = true): array
    {
        return [$required ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::PHOTO_MAX_KB];
    }

    /**
     * @return array<string, string>
     */
    public static function photoMessages(string $field = 'photo'): array
    {
        return [
            "{$field}.image" => 'La foto debe ser una imagen.',
            "{$field}.mimes" => 'La foto debe ser JPG, PNG o WEBP.',
            "{$field}.max" => 'La foto no puede pesar más de 8 MB.',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * La matrícula más reciente que no esté cancelada. Define el nivel
     * "actual" del estudiante: cambia en cuanto se crea la del siguiente
     * nivel, aunque todavía esté pendiente (parte 4 del plan de mejoras).
     */
    public function currentEnrollment(): ?Enrollment
    {
        return $this->enrollments()
            ->where('status', '!=', 'cancelada')
            ->with('level')
            ->latest('enrolled_at')
            ->latest('id')
            ->first();
    }

    public function contractSignatures(): HasMany
    {
        return $this->hasMany(ContractSignature::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentAgreements(): HasMany
    {
        return $this->hasMany(PaymentAgreement::class);
    }

    public function paymentFollowUps(): HasMany
    {
        return $this->hasMany(PaymentFollowUp::class);
    }

    /**
     * Whether the student has overdue fees and no active payment agreement.
     * Un estudiante bloqueado solo puede pagar, ver su perfil, firmar
     * contratos y cerrar sesión (parte 8 del plan de mejoras).
     */
    public function isBlockedForDebt(): bool
    {
        return $this->payments()->overdue()->exists()
            && ! $this->paymentAgreements()->active()->exists();
    }

    /**
     * De los estudiantes indicados, los que están bloqueados por mora, en
     * dos consultas (para listas como la de asistencia).
     *
     * @param  array<int, int>  $studentIds
     * @return list<int>
     */
    public static function blockedForDebtIds(array $studentIds): array
    {
        if ($studentIds === []) {
            return [];
        }

        $withOverdue = Payment::query()->overdue()->whereIn('student_id', $studentIds)->distinct()->pluck('student_id');
        $withAgreement = PaymentAgreement::query()->active()->whereIn('student_id', $withOverdue)->pluck('student_id');

        return $withOverdue->diff($withAgreement)->values()->all();
    }

    public function referralsMade(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_student_id');
    }

    public function referralReceived(): HasOne
    {
        return $this->hasOne(Referral::class, 'referred_student_id');
    }

    /**
     * Whether the student is under 18 on the given date (by default, today).
     * Se evalúa con la fecha en que se firma: si el estudiante es menor, los
     * contratos los firma solo el acudiente (parte 6.2 del plan de mejoras).
     */
    public function isMinor(?CarbonInterface $on = null): bool
    {
        if (! $this->birth_date) {
            return false;
        }

        return $this->birth_date->diffInYears($on ?? now()) < self::ADULT_AGE;
    }

    /**
     * Datos que faltan para poder enviarle contratos. Si la lista no está
     * vacía, el estudiante aparece como "Datos incompletos".
     *
     * @return list<string>
     */
    public function missingContractData(): array
    {
        $missing = [];

        if (! $this->birth_date) {
            $missing[] = 'fecha de nacimiento';
        }

        if ($this->isMinor()) {
            if (! $this->guardian_name) {
                $missing[] = 'nombre del acudiente';
            }

            if (! $this->guardian_email) {
                $missing[] = 'correo del acudiente';
            }
        }

        return $missing;
    }
}
