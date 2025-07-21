<?php

namespace App\Http\Controllers;

use App\Exports\OffsetSummaryExport;
use App\Models\OffsetRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class OffsetSummaryReportController extends Controller
{
    public function index(Request $request)
    {
        $user    = auth()->user();
        $company = $user->preference->company;

        if (!$user->hasPermission('view offset report')) {
            abort(403, 'Unauthorized to view offset reports.');
        }

        $employeeId = optional($user->employee)->id;

        if (!$employeeId) {
            abort(403, 'No employee profile found.');
        }

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : now()->endOfMonth();

        $status   = $request->input('status');
        $project  = $request->input('project');
        $minHours = $request->input('min_hours');
        $maxHours = $request->input('max_hours');
        $approver = $request->input('approver');
        $sort     = $request->input('sort', 'asc');

        $offsetRequests = OffsetRequest::with(['approver'])
            ->where('company_id', $company->id)
            ->where('employee_id', $employeeId)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($project, fn ($q) => $q->where('project_or_event_description', 'like', "%{$project}%"))
            ->when($minHours, fn ($q) => $q->where('number_of_hours', '>=', $minHours))
            ->when($maxHours, fn ($q) => $q->where('number_of_hours', '<=', $maxHours))
            ->when($approver, fn ($q) => $q->whereHas('approver', fn ($a) => $a->where('name', 'like', "%{$approver}%")))
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', $sort)
            ->get();

        return view('reports.offset_summary', compact(
            'offsetRequests',
            'startDate',
            'endDate'
        ));
    }
    public function exportPdf(Request $request)
    {
        $data = $this->getFilteredOffsetRequests($request);
        $pdf  = Pdf::loadView('reports.offset_summary_pdf', $data);
        return $pdf->download('offset_request_summary.pdf');
    }

    public function exportExcel(Request $request)
    {
        $user    = auth()->user();
        $company = $user->preference->company;

        if (!$user->hasPermission('view offset report')) {
            abort(403);
        }

        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date) : now()->startOfMonth();
        $endDate   = $request->filled('end_date') ? Carbon::parse($request->end_date) : now()->endOfMonth();

        // Use your existing getFilteredOffsetRequests logic or replicate it here
        $offsetRequests = OffsetRequest::with('approver')
            ->where('company_id', $company->id)
            ->where('employee_id', optional($user->employee)->id)
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        return Excel::download(
            new OffsetSummaryExport($offsetRequests, $startDate, $endDate),
            'offset_summary_report.xlsx'
        );
    }
    protected function getFilteredOffsetRequests(Request $request): array
    {
        $user    = auth()->user();
        $company = $user->preference->company;

        $employeeId = optional($user->employee)->id;

        if (!$employeeId) {
            abort(403, 'No employee profile found.');
        }

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : now()->endOfMonth();

        $status    = $request->input('status');
        $project   = $request->input('project');
        $minHours  = $request->input('min_hours');
        $maxHours  = $request->input('max_hours');
        $approver  = $request->input('approver');
        $sortOrder = $request->input('sort', 'asc');

        $offsetRequests = OffsetRequest::with('approver')
            ->where('company_id', $company->id)
            ->where('employee_id', $employeeId)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($project, fn ($q) => $q->where('project_or_event_description', 'like', "%$project%"))
            ->when($minHours, fn ($q) => $q->where('number_of_hours', '>=', $minHours))
            ->when($maxHours, fn ($q) => $q->where('number_of_hours', '<=', $maxHours))
            ->when($approver, fn ($q) => $q->whereHas('approver', function ($query) use ($approver) {
                $query->where('name', 'like', "%$approver%");
            }))
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', $sortOrder)
            ->get();

        return compact('offsetRequests', 'startDate', 'endDate');
    }
}
