<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    /**
     * Display a listing of the audit logs.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $company = auth()->user()->preference->company;
        $companyId = auth()->user()->preference->company_id;

        $query = AuditLog::with('user') // eager load the user relationship
            ->where('company_id', $company->id);

        // Optional filters
        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('performed_by')) {
            $query->where('performed_by', $request->input('performed_by'));
        }

        if ($request->filled('model_type')) {
            $query->where('model_type', 'like', '%' . $request->input('model_type') . '%');
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                ->orWhere('model_type', 'like', "%{$search}%")
                ->orWhereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Optional sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $query->orderBy($sortBy, 'desc');

        $auditLogs = $query->paginate(20)->appends($request->query());

        return view('audit_logs.index', [
            'company' => $company,
            'auditLogs' => $auditLogs,
        ]);
    }

    /**
     * Display the specified audit log.
     */
    public function show($id)
    {
        $log = AuditLog::where('company_id', Auth::user()->company_id)->findOrFail($id);

        return response()->json($log);
    }

    /**
     * (Optional) Remove the specified audit log from storage.
     * Not recommended unless you implement a soft-delete or archive strategy.
     */
    public function destroy($id)
    {
        $log = AuditLog::where('company_id', Auth::user()->company_id)->findOrFail($id);

        $log->delete();

        return response()->json(['message' => 'Audit log deleted.']);
    }
}
