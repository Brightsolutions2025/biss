<?php

namespace App\Http\Controllers;

use App\Exports\OutbaseReportExport;
use App\Models\OutbaseRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class OutbaseReportController extends Controller
{
    public function index(Request $request)
    {
        $user    = auth()->user();
        $company = $user->preference->company;

        if (!$user->hasPermission('view outbase report')) {
            abort(403, 'Unauthorized to view outbase reports.');
        }

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : now()->endOfMonth();

        // Get current user's employee record
        $employeeId = optional($user->employee)->id;

        if (!$employeeId) {
            abort(403, 'No associated employee record found for the current user.');
        }

        // Build query with filters
        $query = OutbaseRequest::with(['employee.user', 'employee.department'])
            ->where('company_id', $company->id)
            ->where('employee_id', $employeeId)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        $outbaseRequests = $query->orderBy('date')->get();

        return view('reports.outbase_history', [
            'outbaseRequests' => $outbaseRequests,
            'startDate'       => $startDate->toDateString(),
            'endDate'         => $endDate->toDateString(),
        ]);
    }
    public function outbaseHistoryPdf(Request $request)
    {
        $user    = auth()->user();
        $company = $user->preference->company;

        if (!$user->hasPermission('view outbase report')) {
            abort(403, 'Unauthorized to view outbase reports.');
        }

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : now()->endOfMonth();

        $employeeId = optional($user->employee)->id;

        if (!$employeeId) {
            abort(403, 'No associated employee record found for the current user.');
        }

        $query = OutbaseRequest::with(['employee.user', 'employee.department'])
            ->where('company_id', $company->id)
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        $outbaseRequests = $query->orderBy('date')->get();

        $pdf = Pdf::loadView('reports.outbase_history_pdf', [
            'outbaseRequests' => $outbaseRequests,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
        ])->setPaper('A4', 'landscape');

        return $pdf->download('outbase_requests_report.pdf');
    }

    public function outbaseHistoryExcel(Request $request)
    {
        $user    = auth()->user();
        $company = $user->preference->company;

        if (!$user->hasPermission('view outbase report')) {
            abort(403, 'Unauthorized to export outbase reports.');
        }

        $startDate  = $request->filled('start_date') ? Carbon::parse($request->start_date) : now()->startOfMonth();
        $endDate    = $request->filled('end_date') ? Carbon::parse($request->end_date) : now()->endOfMonth();
        $employeeId = optional($user->employee)->id;

        if (!$employeeId) {
            abort(403, 'No employee record found.');
        }

        $query = OutbaseRequest::with(['employee.user', 'employee.department'])
            ->where('company_id', $company->id)
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        $outbaseRequests = $query->orderBy('date')->get();

        return Excel::download(
            new OutbaseReportExport($outbaseRequests, $startDate, $endDate),
            'outbase_report.xlsx'
        );
    }
}
