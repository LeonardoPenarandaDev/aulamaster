<?php

namespace App\Actions\Students;

use App\Models\ChecklistItem;
use App\Models\ContractSignature;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentChecklistMark;
use Illuminate\Support\Collection;

/**
 * Estado del checklist de la gestión académica de uno o varios estudiantes:
 * los requisitos manuales según las marcas del personal y los automáticos
 * según los datos de la plataforma.
 */
class EvaluateStudentChecklist
{
    /**
     * @return array{done: int, total: int, complete: bool, items: list<array<string, mixed>>}
     */
    public function handle(Student $student): array
    {
        return $this->forStudents(collect([$student]))[$student->id];
    }

    /**
     * @param  Collection<int, Student>  $students
     * @return array<int, array{done: int, total: int, complete: bool, items: list<array<string, mixed>>}>
     */
    public function forStudents(Collection $students): array
    {
        $items = ChecklistItem::query()->active()->get();
        $studentIds = $students->pluck('id');

        $marks = StudentChecklistMark::query()
            ->whereIn('student_id', $studentIds)
            ->with('checkedBy:id,name')
            ->get()
            ->groupBy('student_id');

        $signatures = ContractSignature::query()
            ->whereIn('student_id', $studentIds)
            ->where('status', '!=', 'anulado')
            ->get(['student_id', 'status'])
            ->groupBy('student_id');

        $paidEnrollmentFees = Payment::query()
            ->whereIn('student_id', $studentIds)
            ->where('type', 'matricula')
            ->where('status', 'pagado')
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        return $students->mapWithKeys(function (Student $student) use ($items, $marks, $signatures, $paidEnrollmentFees) {
            $studentMarks = ($marks[$student->id] ?? collect())->keyBy('checklist_item_id');
            $studentSignatures = $signatures[$student->id] ?? collect();

            $rows = $items->map(function (ChecklistItem $item) use ($student, $studentMarks, $studentSignatures, $paidEnrollmentFees) {
                $mark = $studentMarks->get($item->id);

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'automatic' => ! $item->isManual(),
                    'done' => $item->isManual()
                        ? $mark !== null
                        : $this->automaticCheck($item->automatic_check, $student, $studentSignatures, $paidEnrollmentFees),
                    'checked_by' => $mark?->checkedBy?->name,
                    'checked_at' => $mark?->created_at?->format('Y-m-d H:i'),
                    'notes' => $mark?->notes,
                ];
            })->values();

            $done = $rows->where('done', true)->count();

            return [$student->id => [
                'done' => $done,
                'total' => $rows->count(),
                'complete' => $rows->isNotEmpty() && $done === $rows->count(),
                'items' => $rows->all(),
            ]];
        })->all();
    }

    /**
     * @param  Collection<int, ContractSignature>  $signatures
     * @param  list<int>  $paidEnrollmentFees
     */
    private function automaticCheck(string $check, Student $student, Collection $signatures, array $paidEnrollmentFees): bool
    {
        return match ($check) {
            'contratos_firmados' => $signatures->isNotEmpty()
                && $signatures->every(fn (ContractSignature $signature) => $signature->status === 'firmado'),
            'foto_perfil' => $student->photo_path !== null,
            'acceso_plataforma' => $student->user_id !== null,
            'matricula_pagada' => in_array((int) $student->id, $paidEnrollmentFees, true),
            default => false,
        };
    }
}
