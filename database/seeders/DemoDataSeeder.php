<?php

namespace Database\Seeders;

use App\Actions\Attendance\RecalculateEnrollmentHours;
use App\Actions\Scheduling\GenerateClassSessionsFromSchedule;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\ClassMaterial;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\EvaluationResult;
use App\Models\Extension;
use App\Models\Level;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\Referral;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Datos de demostración para probar toda la plataforma: cursos, niveles,
 * aulas, profesores, horarios con sesiones generadas, alumnos con acceso al
 * portal, asistencias (y por tanto horas vistas), evaluaciones, pagos,
 * promociones, referidos y extensiones. Es aditivo: no borra datos
 * existentes. Todos los usuarios creados usan la contraseña "password".
 *
 * Ejecutar con: php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    private const STUDENT_COUNT = 30;

    private const TEACHER_COUNT = 6;

    /**
     * Franjas horarias sin solapamiento entre sí, una por horario.
     *
     * @var array<int, array{days: array<int, int>, start: string, end: string}>
     */
    private const TIME_SLOTS = [
        ['days' => [1, 3], 'start' => '07:00', 'end' => '09:00'],
        ['days' => [2, 4], 'start' => '07:00', 'end' => '09:00'],
        ['days' => [1, 3], 'start' => '09:00', 'end' => '11:00'],
        ['days' => [2, 4], 'start' => '09:00', 'end' => '11:00'],
        ['days' => [1, 3], 'start' => '14:00', 'end' => '16:00'],
        ['days' => [2, 4], 'start' => '14:00', 'end' => '16:00'],
        ['days' => [1, 3], 'start' => '18:00', 'end' => '20:00'],
        ['days' => [2, 4], 'start' => '18:00', 'end' => '20:00'],
        ['days' => [6], 'start' => '08:00', 'end' => '12:00'],
    ];

    public function __construct(
        private readonly GenerateClassSessionsFromSchedule $generateClassSessions,
        private readonly RecalculateEnrollmentHours $recalculateHours,
    ) {}

    public function run(): void
    {
        if (User::query()->where('email', 'alumno1@instituto.test')->exists()) {
            $this->command?->warn('Los datos de demostración ya existen (alumno1@instituto.test). No se hizo nada.');

            return;
        }

        $this->call(RoleSeeder::class);

        DB::transaction(fn () => $this->seedDemoData());

        $this->command?->info('Datos de demostración creados. Alumnos: alumno1..'.self::STUDENT_COUNT.'@instituto.test, profesores: profesor1..'.self::TEACHER_COUNT.'@instituto.test (contraseña: password).');
    }

    private function seedDemoData(): void
    {
        $admin = User::query()->where('email', 'admin@instituto.test')->first() ?? User::query()->firstOrFail();
        $cycleStart = today()->subWeeks(10)->startOfWeek();
        $cycleEnd = today()->addWeeks(8)->endOfWeek();

        $levels = $this->createCatalog($cycleStart, $cycleEnd);
        $classrooms = $this->createClassrooms();
        $teachers = $this->createTeachers();
        $schedules = $this->createSchedules($levels, $teachers, $classrooms, $cycleStart, $cycleEnd);
        $promotions = $this->createPromotions();
        $students = $this->createStudents();

        $enrollments = $this->createEnrollments($students, $levels, $schedules, $teachers, $admin, $cycleStart, $promotions);
        $this->createReferrals($students, $enrollments);

        foreach ($enrollments as $index => $enrollment) {
            $attendanceRate = $index === 0 ? 0.95 : fake()->randomFloat(2, 0.7, 0.98);

            foreach ($this->schedulesAttendedBy($schedules, $index) as $schedule) {
                $this->createAttendances($enrollment, $schedule, $attendanceRate, $admin);
            }

            $this->recalculateHours->handle($enrollment);
        }

        $this->createEvaluationResults($enrollments, $schedules);
        $this->createSpecialStatuses($enrollments, $admin);
        $this->createPayments($enrollments, $admin);
        $this->createClassMaterials($schedules);
        $this->markExamReviewSessions($levels);
    }

    /**
     * La última clase de la semana siguiente de cada nivel queda marcada como
     * repaso para examen, como la publica la institución en sus horarios.
     *
     * @param  Collection<int, Level>  $levels
     */
    private function markExamReviewSessions(Collection $levels): void
    {
        $nextWeekStart = today()->startOfWeek()->addWeek();

        foreach ($levels as $level) {
            ClassSession::query()
                ->where('level_id', $level->id)
                ->whereBetween('date', [$nextWeekStart->toDateString(), $nextWeekStart->copy()->endOfWeek()->toDateString()])
                ->orderByDesc('date')
                ->orderByDesc('start_time')
                ->first()
                ?->update(['notes' => 'Repaso para examen']);
        }
    }

    /**
     * Material de repaso en las últimas clases dictadas de cada grupo.
     *
     * @param  array<int, ClassSchedule>  $schedules
     */
    private function createClassMaterials(array $schedules): void
    {
        $samples = [
            ['title' => 'Presentación de la clase', 'url' => 'https://docs.google.com/presentation/d/ejemplo', 'description' => 'Diapositivas vistas en clase.'],
            ['title' => 'Video de repaso: verbos irregulares', 'url' => 'https://www.youtube.com/watch?v=ejemplo', 'description' => null],
            ['title' => 'Ejercicios de práctica', 'url' => 'https://learnenglish.britishcouncil.org/', 'description' => 'Hacer los ejercicios 1 al 5 antes de la próxima clase.'],
        ];

        foreach ($schedules as $schedule) {
            $schedule->classSessions()
                ->where('status', 'dictada')
                ->orderByDesc('date')
                ->take(3)
                ->get()
                ->each(function (ClassSession $session, int $index) use ($samples, $schedule) {
                    foreach (array_slice($samples, 0, $index === 0 ? 2 : 1) as $sample) {
                        ClassMaterial::query()->create([
                            ...$sample,
                            'class_session_id' => $session->id,
                            'created_by_id' => $schedule->teacher->user_id,
                        ]);
                    }
                });
        }
    }

    /**
     * Un único curso de inglés con un nivel A1, A2 y B1. Reutiliza el curso
     * y los niveles si ya existen, para no duplicarlos.
     *
     * @return Collection<int, Level>
     */
    private function createCatalog(Carbon $cycleStart, Carbon $cycleEnd): Collection
    {
        $course = Course::query()->firstOrCreate(
            ['name' => 'INGLES'],
            ['description' => 'Programa de inglés comunicativo por niveles del MCER.', 'status' => 'activo'],
        );

        return collect(['A1', 'A2', 'B1'])->map(function (string $levelName) use ($course, $cycleStart, $cycleEnd) {
            $level = Level::query()->firstOrCreate(
                ['course_id' => $course->id, 'name' => $levelName],
                [
                    'code' => "ING-{$levelName}",
                    'duration_months' => 4,
                    'weekly_hours' => 8,
                    'monthly_hours' => 32,
                    'required_hours' => 128,
                    'minimum_grade' => 70,
                    'price' => 450000,
                    'start_date' => $cycleStart->toDateString(),
                    'end_date' => $cycleEnd->toDateString(),
                    'status' => 'activo',
                ],
            );

            foreach (['Listening', 'Speaking', 'Reading', 'Writing'] as $competency) {
                Evaluation::query()->firstOrCreate(
                    ['level_id' => $level->id, 'name' => $competency],
                    ['competency' => strtolower($competency), 'minimum_grade' => 70, 'status' => 'activo'],
                );
            }

            return $level;
        });
    }

    /**
     * @return Collection<int, Classroom>
     */
    private function createClassrooms(): Collection
    {
        return collect(['201', '202', '203', '301'])->map(fn (string $number) => Classroom::query()->firstOrCreate(
            ['code' => "DEMO-{$number}"],
            [
                'name' => "Aula {$number}",
                'capacity' => fake()->numberBetween(15, 30),
                'location' => $number[0] === '2' ? 'Segundo piso' : 'Tercer piso',
                'floor' => $number[0],
                'status' => 'disponible',
            ],
        ));
    }

    /**
     * @return Collection<int, Teacher>
     */
    private function createTeachers(): Collection
    {
        return collect(range(1, self::TEACHER_COUNT))->map(function (int $number) {
            $user = User::factory()->create([
                'email' => "profesor{$number}@instituto.test",
            ]);
            $user->assignRole('profesor');

            return Teacher::factory()->create([
                'user_id' => $user->id,
                'code' => sprintf('PROF-%02d', $number),
                'name' => $user->name,
                'email' => $user->email,
            ]);
        });
    }

    /**
     * Varios grupos (horarios) por nivel en distintas franjas, con sus
     * sesiones generadas; las pasadas quedan como dictadas (algunas
     * canceladas) y las futuras como programadas.
     *
     * @param  Collection<int, Level>  $levels
     * @param  Collection<int, Teacher>  $teachers
     * @param  Collection<int, Classroom>  $classrooms
     * @return array<int, ClassSchedule>
     */
    private function createSchedules(Collection $levels, Collection $teachers, Collection $classrooms, Carbon $cycleStart, Carbon $cycleEnd): array
    {
        $schedules = [];

        foreach (self::TIME_SLOTS as $index => $slot) {
            $level = $levels[$index % $levels->count()];

            $schedule = ClassSchedule::query()->create([
                'level_id' => $level->id,
                'teacher_id' => $teachers[$index % $teachers->count()]->id,
                'classroom_id' => $classrooms[$index % $classrooms->count()]->id,
                'modality' => $slot['start'] >= '18:00' ? 'virtual' : 'presencial',
                'days_of_week' => $slot['days'],
                'start_time' => $slot['start'],
                'end_time' => $slot['end'],
                'start_date' => $cycleStart->toDateString(),
                'end_date' => $cycleEnd->toDateString(),
                'status' => 'activo',
            ]);

            $this->generateClassSessions->handle($schedule);

            $schedule->classSessions()
                ->where(fn ($query) => $query
                    ->whereDate('date', '<', today())
                    ->orWhere(fn ($query) => $query->whereDate('date', today())->where('end_time', '<=', now()->format('H:i:s')))
                )
                ->get()
                ->each(fn ($session) => $session->update(['status' => fake()->boolean(95) ? 'dictada' : 'cancelada']));

            $schedules[] = $schedule;
        }

        return $schedules;
    }

    /**
     * Grupo (horario) al que asiste el alumno en la posición dada.
     *
     * @param  array<int, ClassSchedule>  $schedules
     */
    private function scheduleFor(array $schedules, int $studentIndex): ClassSchedule
    {
        return $schedules[$studentIndex % count($schedules)];
    }

    /**
     * Intensidad horaria semanal contratada por el alumno: 4, 8 o 12 horas.
     */
    private function weeklyHoursFor(int $studentIndex): int
    {
        return [8, 4, 12, 8, 4][$studentIndex % 5];
    }

    /**
     * Grupos de su nivel a los que asiste el alumno para cubrir su
     * intensidad semanal: cada grupo son 4 horas por semana.
     *
     * @param  array<int, ClassSchedule>  $schedules
     * @return array<int, ClassSchedule>
     */
    private function schedulesAttendedBy(array $schedules, int $studentIndex): array
    {
        $main = $this->scheduleFor($schedules, $studentIndex);
        $sameLevel = collect($schedules)
            ->filter(fn (ClassSchedule $schedule) => $schedule->level_id === $main->level_id && ! $schedule->is($main))
            ->values();

        $extraGroups = intdiv($this->weeklyHoursFor($studentIndex), 4) - 1;

        return [$main, ...$sameLevel->take($extraGroups)->all()];
    }

    /**
     * @return Collection<int, Promotion>
     */
    private function createPromotions(): Collection
    {
        return collect([
            Promotion::query()->create([
                'name' => 'Descuento pronto pago',
                'description' => '10% de descuento pagando la matrícula completa antes de iniciar.',
                'discount_type' => 'porcentaje',
                'value' => 10,
                'start_date' => today()->subMonths(3)->toDateString(),
                'end_date' => today()->addMonths(3)->toDateString(),
                'status' => 'activo',
            ]),
            Promotion::query()->create([
                'name' => 'Beca convenio empresarial',
                'description' => 'Descuento fijo para empleados de empresas aliadas.',
                'discount_type' => 'fijo',
                'value' => 50000,
                'start_date' => today()->subMonths(3)->toDateString(),
                'end_date' => today()->addMonths(6)->toDateString(),
                'status' => 'activo',
            ]),
        ]);
    }

    /**
     * @return Collection<int, Student>
     */
    private function createStudents(): Collection
    {
        return collect(range(1, self::STUDENT_COUNT))->map(function (int $number) {
            $user = User::factory()->create([
                'email' => "alumno{$number}@instituto.test",
            ]);
            $user->assignRole('estudiante');

            return Student::factory()->create([
                'user_id' => $user->id,
                'code' => sprintf('EST-%03d', $number),
                'name' => $user->name,
                'email' => $user->email,
            ]);
        });
    }

    /**
     * Matrícula activa de cada alumno en el ciclo actual, más una matrícula
     * anterior aprobada para algunos alumnos de A2/B1 (historial académico).
     *
     * @param  Collection<int, Student>  $students
     * @param  Collection<int, Level>  $levels
     * @param  array<int, ClassSchedule>  $schedules
     * @param  Collection<int, Teacher>  $teachers
     * @param  Collection<int, Promotion>  $promotions
     * @return Collection<int, Enrollment>
     */
    private function createEnrollments(Collection $students, Collection $levels, array $schedules, Collection $teachers, User $admin, Carbon $cycleStart, Collection $promotions): Collection
    {
        $levels = $levels->values();

        return $students->values()->map(function (Student $student, int $index) use ($levels, $schedules, $teachers, $admin, $cycleStart, $promotions) {
            $level = $this->scheduleFor($schedules, $index)->level;
            $startsLate = $index > 0 && fake()->boolean(30);
            $startDate = $startsLate ? $cycleStart->copy()->addWeeks(4) : $cycleStart->copy();

            $levelPosition = $levels->search(fn (Level $candidate) => $candidate->is($level));
            if ($levelPosition > 0 && $index % 2 === 0) {
                $this->createApprovedPreviousEnrollment($student, $levels[$levelPosition - 1], $teachers->random(), $admin, $cycleStart);
            }

            $promotion = $index % 7 === 3 ? $promotions->random() : null;
            $basePrice = (float) $level->price;
            $promotionDiscount = match ($promotion?->discount_type) {
                'porcentaje' => round($basePrice * $promotion->value / 100),
                'fijo' => (float) $promotion->value,
                default => 0,
            };

            return Enrollment::query()->create([
                'student_id' => $student->id,
                'level_id' => $level->id,
                'enrolled_at' => $startDate->copy()->subDays(5)->toDateString(),
                'start_date' => $startDate->toDateString(),
                'estimated_end_date' => $startDate->copy()->addMonths($level->duration_months)->toDateString(),
                'status' => 'activa',
                'required_hours' => $level->required_hours,
                'weekly_hours' => $this->weeklyHoursFor($index),
                'accumulated_hours' => 0,
                'base_price' => $basePrice,
                'promotion_id' => $promotion?->id,
                'promotion_discount' => $promotionDiscount,
                'final_price' => $basePrice - $promotionDiscount,
            ]);
        });
    }

    private function createApprovedPreviousEnrollment(Student $student, Level $level, Teacher $teacher, User $admin, Carbon $cycleStart): void
    {
        $startDate = $cycleStart->copy()->subMonths(5);

        $enrollment = Enrollment::query()->create([
            'student_id' => $student->id,
            'level_id' => $level->id,
            'enrolled_at' => $startDate->copy()->subDays(5)->toDateString(),
            'start_date' => $startDate->toDateString(),
            'estimated_end_date' => $startDate->copy()->addMonths(4)->toDateString(),
            'actual_end_date' => $startDate->copy()->addMonths(4)->toDateString(),
            'status' => 'aprobada',
            'required_hours' => $level->required_hours,
            'weekly_hours' => $level->weekly_hours,
            'accumulated_hours' => $level->required_hours,
            'base_price' => $level->price,
            'final_price' => $level->price,
        ]);

        $level->evaluations->each(function (Evaluation $evaluation) use ($enrollment, $teacher, $startDate) {
            EvaluationResult::query()->create([
                'evaluation_id' => $evaluation->id,
                'enrollment_id' => $enrollment->id,
                'teacher_id' => $teacher->id,
                'attempt_number' => 1,
                'is_recovery' => false,
                'evaluated_at' => $startDate->copy()->addMonths(4)->subWeek()->toDateString(),
                'grade' => fake()->numberBetween(72, 100),
                'result' => 'aprobado',
                'registered_by_id' => $teacher->user_id,
            ]);
        });

        Payment::query()->create([
            'student_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'concept' => "Matrícula {$level->course->name} {$level->name}",
            'base_amount' => $level->price,
            'discount_amount' => 0,
            'final_amount' => $level->price,
            'paid_at' => $startDate->copy()->subDays(5)->toDateString(),
            'payment_method' => fake()->randomElement(['efectivo', 'transferencia', 'tarjeta']),
            'receipt_reference' => 'REC-'.fake()->unique()->numerify('######'),
            'status' => 'pagado',
            'registered_by_id' => $admin->id,
        ]);
    }

    /**
     * @param  Collection<int, Student>  $students
     * @param  Collection<int, Enrollment>  $enrollments
     */
    private function createReferrals(Collection $students, Collection $enrollments): void
    {
        foreach ([[1, 10], [1, 11], [4, 20]] as [$referrerIndex, $referredIndex]) {
            $referral = Referral::query()->create([
                'referrer_student_id' => $students[$referrerIndex]->id,
                'referred_student_id' => $students[$referredIndex]->id,
                'referrer_discount' => 50000,
                'referred_discount' => 25000,
            ]);

            $enrollment = $enrollments[$referredIndex];
            $enrollment->update([
                'referral_id' => $referral->id,
                'referral_discount' => 25000,
                'final_price' => $enrollment->final_price - 25000,
            ]);
        }
    }

    /**
     * Asistencia a cada sesión dictada desde que el alumno inició. La
     * probabilidad de asistir varía por alumno para tener progresos distintos.
     */
    private function createAttendances(Enrollment $enrollment, ClassSchedule $schedule, float $attendanceRate, User $admin): void
    {
        $teacherUserId = $schedule->teacher->user_id ?? $admin->id;

        $sessions = $schedule->classSessions()
            ->where('status', 'dictada')
            ->whereDate('date', '>=', $enrollment->start_date)
            ->get();

        foreach ($sessions as $session) {
            $status = fake()->randomFloat(2, 0, 1) <= $attendanceRate
                ? 'presente'
                : (fake()->boolean(60) ? 'ausente' : 'excusado');

            $attendance = Attendance::query()->create([
                'class_session_id' => $session->id,
                'enrollment_id' => $enrollment->id,
                'teacher_id' => $session->teacher_id,
                'class_date' => $session->date->toDateString(),
                'status' => $status,
                'registered_by_id' => $teacherUserId,
            ]);

            if ($status === 'ausente' && fake()->boolean(10)) {
                AttendanceCorrection::query()->create([
                    'attendance_id' => $attendance->id,
                    'previous_status' => 'ausente',
                    'new_status' => 'presente',
                    'reason' => 'Llegó tarde; el profesor corrige el registro.',
                    'corrected_by_id' => $admin->id,
                ]);
            }
        }
    }

    /**
     * Resultados de las dos primeras evaluaciones de cada nivel para los
     * alumnos que iniciaron con el ciclo.
     *
     * @param  Collection<int, Enrollment>  $enrollments
     * @param  array<int, ClassSchedule>  $schedules
     */
    private function createEvaluationResults(Collection $enrollments, array $schedules): void
    {
        foreach ($enrollments as $index => $enrollment) {
            $schedule = $this->scheduleFor($schedules, $index);

            if ($enrollment->start_date->greaterThan($schedule->start_date)) {
                continue;
            }

            $evaluations = $enrollment->level->evaluations()->orderBy('id')->take(2)->get();

            foreach ($evaluations as $offset => $evaluation) {
                $grade = fake()->numberBetween(55, 100);

                EvaluationResult::query()->create([
                    'evaluation_id' => $evaluation->id,
                    'enrollment_id' => $enrollment->id,
                    'teacher_id' => $schedule->teacher_id,
                    'attempt_number' => 1,
                    'is_recovery' => false,
                    'evaluated_at' => today()->subWeeks(4 - $offset * 2)->toDateString(),
                    'grade' => $grade,
                    'result' => $grade >= $evaluation->minimum_grade ? 'aprobado' : 'reprobado',
                    'registered_by_id' => $schedule->teacher->user_id,
                ]);
            }
        }
    }

    /**
     * Algunos casos especiales para probar los flujos de recuperación,
     * extensión y cancelación.
     *
     * @param  Collection<int, Enrollment>  $enrollments
     */
    private function createSpecialStatuses(Collection $enrollments, User $admin): void
    {
        $enrollments[5]->update(['status' => 'en_recuperacion']);
        $enrollments[14]->update(['status' => 'en_recuperacion']);

        $extended = $enrollments[8];
        $newEndDate = $extended->estimated_end_date->copy()->addMonth();
        Extension::query()->create([
            'enrollment_id' => $extended->id,
            'previous_end_date' => $extended->estimated_end_date->toDateString(),
            'new_end_date' => $newEndDate->toDateString(),
            'reason' => 'Incapacidad médica',
            'notes' => 'Presentó certificado médico por dos semanas.',
            'extended_by_id' => $admin->id,
        ]);
        $extended->update(['status' => 'extendida', 'estimated_end_date' => $newEndDate->toDateString()]);

        $enrollments[25]->update(['status' => 'cancelada', 'actual_end_date' => today()->subWeeks(3)->toDateString()]);
    }

    /**
     * Pago de matrícula de cada alumno (mayoría pagados, algunos pendientes o
     * vencidos) y una mensualidad de material pendiente para algunos.
     *
     * @param  Collection<int, Enrollment>  $enrollments
     */
    private function createPayments(Collection $enrollments, User $admin): void
    {
        foreach ($enrollments as $index => $enrollment) {
            $status = match (true) {
                $index % 10 === 7 => 'pendiente',
                $index % 10 === 9 => 'vencido',
                default => 'pagado',
            };
            $isPaid = $status === 'pagado';

            Payment::query()->create([
                'student_id' => $enrollment->student_id,
                'enrollment_id' => $enrollment->id,
                'concept' => "Matrícula {$enrollment->level->course->name} {$enrollment->level->name}",
                'base_amount' => $enrollment->base_price,
                'discount_amount' => $enrollment->base_price - $enrollment->final_price,
                'final_amount' => $enrollment->final_price,
                'promotion_id' => $enrollment->promotion_id,
                'referral_id' => $enrollment->referral_id,
                'paid_at' => $isPaid ? $enrollment->enrolled_at->toDateString() : null,
                'payment_method' => $isPaid ? fake()->randomElement(['efectivo', 'transferencia', 'tarjeta']) : null,
                'receipt_reference' => $isPaid ? 'REC-'.fake()->unique()->numerify('######') : null,
                'status' => $status,
                'registered_by_id' => $admin->id,
            ]);

            if ($index % 4 === 1) {
                Payment::query()->create([
                    'student_id' => $enrollment->student_id,
                    'enrollment_id' => $enrollment->id,
                    'concept' => 'Material didáctico',
                    'base_amount' => 80000,
                    'discount_amount' => 0,
                    'final_amount' => 80000,
                    'status' => 'pendiente',
                    'registered_by_id' => $admin->id,
                ]);
            }
        }
    }
}
