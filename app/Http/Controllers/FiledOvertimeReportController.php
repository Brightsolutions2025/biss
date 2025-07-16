<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\OvertimeRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FiledOvertimeExport;

class FiledOvertimeReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $company = $user->preference->company;

        if (!$user->hasPermission('view overtime report')) {
            abort(403, 'Unauthorized.');
        }

        $employee = $user->employee;

        if (!$employee || $employee->company_id !== $company->id) {
            abort(403, 'Unauthorized employee.');
        }

        $overtimeRequests = OvertimeRequest::where('company_id', $company->id)
            ->where('employee_id', $employee->id)
            ->when($request->start_date, fn($q) => $q->whereDate('date', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('date', '<=', $request->end_date))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('date')
            ->get();
            
        return view('reports.filed_overtime', [
            'overtimeRequests' => $overtimeRequests,
        ]);
    }
    public function overtimeHistoryPdf(Request $request)
    {
        $user = auth()->user();
        $company = $user->preference->company;

        $query = \App\Models\OvertimeRequest::with('employee.user')
            ->where('company_id', $company->id)
            ->when($user->employee, fn($q) => $q->where('employee_id', $user->employee->id))
            ->when($request->filled('start_date'), fn($q) => $q->where('date', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn($q) => $q->where('date', '<=', $request->end_date))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->orderByDesc('date');

        $overtimeRequests = $query->get();

        $pdf = Pdf::loadView('reports.overtime_history_pdf', [
            'overtimeRequests' => $overtimeRequests,
            'companyName' => $company->name,
            'filters' => $request->only(['start_date', 'end_date', 'status']),
        ]);

        return $pdf->download('filed_overtime_report.pdf');
    }

    public function overtimeHistoryExcel(Request $request)
    {
        $user = auth()->user();
        $company = $user->preference->company;

        return Excel::download(
            new FiledOvertimeExport($user, $company, $request),
            'filed_overtime_report.xlsx'
        );
    }
}
