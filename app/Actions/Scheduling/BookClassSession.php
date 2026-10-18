<?php

namespace App\Actions\Scheduling;

use App\Models\ClassBooking;
use App\Models\ClassSession;
use App\Models\Enrollment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El estudiante se programa en una clase de su nivel. En las presenciales
 * los cupos son la capacidad del aula: si ya está llena no se puede
 * inscribir y debe escribir al instituto.
 */
class BookClassSession
{
    public function handle(Enrollment $enrollment, ClassSession $classSession): ClassBooking
    {
        return DB::transaction(function () use ($enrollment, $classSession): ClassBooking {
            // Bloquea la clase para que dos estudiantes no tomen el último cupo a la vez.
            $session = ClassSession::query()->with('classroom')->lockForUpdate()->findOrFail($classSession->id);

            $existing = ClassBooking::query()
                ->where('class_session_id', $session->id)
                ->where('enrollment_id', $enrollment->id)
                ->first();

            if ($existing) {
                return $existing;
            }

            $this->ensureCanBeBooked($enrollment, $session);

            $capacity = $session->capacity();

            if ($capacity !== null && $session->bookings()->count() >= $capacity) {
                throw ValidationException::withMessages([
                    'booking' => 'Esta clase ya no tiene cupos disponibles. Escríbele al instituto para buscarte otro horario.',
                ]);
            }

            return ClassBooking::create([
                'class_session_id' => $session->id,
                'enrollment_id' => $enrollment->id,
            ]);
        });
    }

    /**
     * Libera el cupo mientras la clase no haya empezado.
     */
    public function cancel(Enrollment $enrollment, ClassSession $classSession): void
    {
        if ($classSession->startsAt()->isPast()) {
            throw ValidationException::withMessages([
                'booking' => 'La clase ya empezó: no puedes cancelar tu cupo.',
            ]);
        }

        ClassBooking::query()
            ->where('class_session_id', $classSession->id)
            ->where('enrollment_id', $enrollment->id)
            ->delete();
    }

    private function ensureCanBeBooked(Enrollment $enrollment, ClassSession $session): void
    {
        $message = match (true) {
            (int) $session->level_id !== (int) $enrollment->level_id => 'Solo puedes programarte en clases de tu nivel.',
            $session->status === 'cancelada' => 'Esta clase fue cancelada.',
            $session->startsAt()->isPast() => 'Esta clase ya empezó o ya pasó.',
            $this->overlapsAnotherBooking($enrollment, $session) => 'Ya estás programado en otra clase a esa misma hora.',
            default => null,
        };

        if ($message !== null) {
            throw ValidationException::withMessages(['booking' => $message]);
        }
    }

    private function overlapsAnotherBooking(Enrollment $enrollment, ClassSession $session): bool
    {
        $start = Carbon::parse($session->start_time)->format('H:i:s');
        $end = Carbon::parse($session->end_time)->format('H:i:s');

        return ClassBooking::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereHas('classSession', fn ($query) => $query
                ->whereDate('date', $session->date)
                ->where('status', '!=', 'cancelada')
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start))
            ->exists();
    }
}
