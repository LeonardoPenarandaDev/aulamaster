<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    /**
     * Display a listing of the resource (sección 38 del plan: consulta de
     * auditoría). Es un reporte de solo lectura, alimentado por AuditObserver.
     */
    public function index(): Response
    {
        return Inertia::render('AuditLogs/Index', [
            'logs' => AuditLog::query()
                ->when(request('module'), fn ($query, $module) => $query->where('module', $module))
                ->when(request('user_id'), fn ($query, $id) => $query->where('user_id', $id))
                ->when(request('action'), fn ($query, $action) => $query->where('action', $action))
                ->when(request('date'), fn ($query, $date) => $query->whereDate('created_at', $date))
                ->orderByDesc('created_at')
                ->paginate(25)
                ->withQueryString(),
            'filters' => request()->only('module', 'user_id', 'action', 'date'),
            'modules' => AuditLog::query()->distinct()->orderBy('module')->pluck('module'),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
