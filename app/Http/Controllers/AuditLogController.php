<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        return view('audit.index', [
            'logs' => $this->query($request)->paginate(50)->withQueryString(),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        Audit::log('exported_audit_log', null, $request->only(['action', 'user', 'from', 'to']));

        $logs = $this->query($request)->get();

        return response()->streamDownload(function () use ($logs) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'when', 'user', 'action', 'subject', 'meta', 'ip']);
            foreach ($logs as $log) {
                fputcsv($out, [
                    $log->id, $log->created_at->toIso8601String(), $log->user?->email, $log->action,
                    $log->subjectLabel(), json_encode($log->meta), $log->ip,
                ]);
            }
            fclose($out);
        }, 'jb-people-audit-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function query(Request $request)
    {
        return AuditLog::with('user')
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')))
            ->when($request->filled('user'), fn ($q) => $q->whereHas('user', fn ($u) => $u->where('email', 'like', '%'.$request->string('user').'%')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest('id');
    }
}
