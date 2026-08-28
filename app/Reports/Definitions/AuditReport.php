<?php

namespace App\Reports\Definitions;

use App\Models\AuditLog;
use App\Reports\BaseReport;
use Illuminate\Support\Collection;

class AuditReport extends BaseReport
{
    public function __construct()
    {
        parent::__construct('audit', 'Auditoría', 'auditoria');
    }

    public function headings(): array
    {
        return ['Fecha y hora', 'Usuario', 'Rol', 'Módulo', 'Acción', 'Descripción', 'IP'];
    }

    public function rows(array $filters): Collection
    {
        return AuditLog::query()
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->orderByDesc('created_at')
            ->get();
    }

    public function toRow($row): array
    {
        return [
            $row->created_at->format('Y-m-d H:i:s'),
            $row->user_name ?? 'Sistema',
            $row->role ?? '—',
            $row->module,
            $row->action,
            $row->description,
            $row->ip_address ?? '—',
        ];
    }
}
