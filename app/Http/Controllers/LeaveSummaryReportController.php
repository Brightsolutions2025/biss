<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LeaveSummaryExport;
use App\Exports\LeaveSummaryExcelExport;

class LeaveSummaryReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $company = $user->preference->company;

        if (!$user->hasPermission('view leave report')) {
            abort(403, 'Unauthorized to view leave reports.');
        }

        $employee = $user->employee;

        if (!$employee || $employee->company_id !== $company->id) {
            abort(403, 'Employee record not found or unauthorized.');
        }

        $year = $request->input('year', now()->year);

        $leaveBalance = LeaveBalance::with([
                'employee.user',
                'employee.department',
                'employee.team',
                'employee.approver',
            ])
            ->where('company_id', $company->id)
            ->where('employee_id', $employee->id)
            ->where('year', $year)
            ->first();

        $used = LeaveRequest::where('company_id', $company->id)
            ->where('employee_id', $employee->id)
            ->whereYear('start_date', $year)
            ->where('status', 'approved')
            ->sum('number_of_days');

        $leaveDetails = LeaveRequest::where('company_id', $company->id)
            ->where('employee_id', $employee->id)
            ->whereYear('start_date', $year)
            ->where('status', 'approved')
            ->orderBy('start_date')
            ->get(['start_date', 'end_date', 'number_of_days', 'reason', 'approval_date']);

        $beginning = $leaveBalance?->beginning_balance ?? 0;
        $remaining = max(0, $beginning - $used);
        $utilization = $beginning > 0 ? round(($used / $beginning) * 100, 1) : 0;

        $leaveBalances = collect([[
            'employee_name'     => $employee->user->name ?? 'N/A',
            'department_name'   => $employee->department->name ?? null,
            'team_name'         => $employee->team->name ?? null,
            'approver_name'     => $employee->approver->name ?? null,
            'beginning_balance' => $beginning,
            'used'              => $used,
            'remaining'         => $remaining,
            'utilization'       => $utilization,
        ]]);

        return view('reports.leave_summary', [
            'leaveBalances' => $leaveBalances,
            'leaveDetails' => $leaveDetails,
            'year' => $year,
        ]);
    }
    public function leaveSummaryPdf(Request $request)
    {
        $user = Auth::user();
        $company = $user->preference->company;
        $employee = $user->employee;

        if (!$employee || $employee->company_id !== $company->id) {
            abort(403, 'Unauthorized.');
        }

        $year = $request->input('year', now()->year);

        // Get leave balance
        $leaveBalance = LeaveBalance::with([
                'employee.user',
                'employee.department',
                'employee.team',
                'employee.approver',
            ])
            ->where('company_id', $company->id)
            ->where('employee_id', $employee->id)
            ->where('year', $year)
            ->first();

        // Compute totals
        $used = LeaveRequest::where('company_id', $company->id)
            ->where('employee_id', $employee->id)
            ->whereYear('start_date', $year)
            ->where('status', 'approved')
            ->sum('number_of_days');

        $beginning = $leaveBalance?->beginning_balance ?? 0;
        $remaining = max(0, $beginning - $used);
        $utilization = $beginning > 0 ? round(($used / $beginning) * 100, 1) : 0;

        // Summary data (single employee wrapped in array)
        $leaveBalances = collect([[
            'employee_name'     => $employee->user->name ?? 'N/A',
            'department_name'   => $employee->department->name ?? null,
            'team_name'         => $employee->team->name ?? null,
            'approver_name'     => $employee->approver->name ?? null,
            'beginning_balance' => $beginning,
            'used'              => $used,
            'remaining'         => $remaining,
            'utilization'       => $utilization,
        ]]);

        // Detailed list of approved leaves
        $leaveDetails = LeaveRequest::where('company_id', $company->id)
            ->where('employee_id', $employee->id)
            ->whereYear('start_date', $year)
            ->where('status', 'approved')
            ->orderBy('start_date')
            ->get();

        return Pdf::loadView('reports.leave_summary_pdf', [
            'leaveBalances' => $leaveBalances,
            'leaveDetails' => $leaveDetails,
            'year' => $year,
            'companyName' => $company->name,
        ])->download('leave_summary_report.pdf');
    }
    public function leaveSummaryExcel(Request $request)
    {
        $user = Auth::user();
        $company = $user->preference->company;
        $year = $request->input('year', now()->year);

        return Excel::download(
            new LeaveSummaryExcelExport($user, $company, $year),
            'leave_summary_report.xlsx'
        );
    }
}
