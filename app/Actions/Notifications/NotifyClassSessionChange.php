<?php

namespace App\Actions\Notifications;

use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Student;
use App\Notifications\ClassCancelledNotification;
use App\Notifications\ClassroomChangedNotification;
use Illuminate\Support\Collection;

class NotifyClassSessionChange
{
    public function __construct(
        protected NotifyStudent $notifyStudent,
        protected NotifyTeacher $notifyTeacher,
    ) {}

    public function cancelled(ClassSession $session): void
    {
        $notification = new ClassCancelledNotification($session);

        $this->enrolledStudents($session)->each(fn (Student $student) => $this->notifyStudent->handle($student, clone $notification));
        $this->notifyTeacher->handle($session->teacher, $notification);
    }

    public function classroomChanged(ClassSession $session, string $previousClassroomName): void
    {
        $notification = new ClassroomChangedNotification($session, $previousClassroomName);

        $this->enrolledStudents($session)->each(fn (Student $student) => $this->notifyStudent->handle($student, clone $notification));
        $this->notifyTeacher->handle($session->teacher, $notification);
    }

    /**
     * @return Collection<int, Student>
     */
    protected function enrolledStudents(ClassSession $session): Collection
    {
        $studentIds = Enrollment::query()
            ->where('level_id', $session->level_id)
            ->whereIn('status', ['activa', 'en_recuperacion', 'extendida'])
            ->pluck('student_id');

        return Student::query()->whereIn('id', $studentIds)->get();
    }
}
