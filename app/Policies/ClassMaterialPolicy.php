<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\ClassMaterial;
use App\Models\ClassSession;
use App\Models\User;

class ClassMaterialPolicy
{
    /**
     * Grant all abilities to administrators.
     */
    public function before(User $user): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    /**
     * Solo el profesor que dicta la clase puede ver y compartir su material
     * (en cualquier fecha, para poder subirlo después de la clase).
     */
    public function create(User $user, ClassSession $classSession): bool
    {
        return $classSession->teacher?->user_id === $user->id;
    }

    /**
     * Ver un archivo: el docente de la clase y los estudiantes que quedaron
     * presentes en ella (los mismos que lo ven en "Mi material").
     */
    public function view(User $user, ClassMaterial $classMaterial): bool
    {
        if ($this->create($user, $classMaterial->classSession)) {
            return true;
        }

        $student = $user->student;

        return $student !== null && Attendance::query()
            ->where('class_session_id', $classMaterial->class_session_id)
            ->whereHas('enrollment', fn ($query) => $query->where('student_id', $student->id))
            ->with('corrections')
            ->get()
            ->contains(fn (Attendance $attendance) => $attendance->effective_status === 'presente');
    }

    public function delete(User $user, ClassMaterial $classMaterial): bool
    {
        return $this->create($user, $classMaterial->classSession);
    }
}
