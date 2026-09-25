<?php

namespace App\Http\Controllers;

use App\Exports\LeaveSummaryExcelExport;
use App\Models\LeaveRequest;
use App\Services\LeaveCreditService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class LeaveSummaryReportController extends Controller
{
    public function __construct(private LeaveCreditService $leaveCredits)
    {
    }

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

        $year = (int) $request->input('year', now()->year);
        [$leaveBalances, $leaveDetails] = $this->buildEmployeeLeaveData($employee, $year);

        return view('reports.leave_summary', compact('leaveBalances', 'leaveDetails', 'year'));
    }

    public function leaveSummaryPdf(Request $request)
    {
        $user = Auth::user();
        $company = $user->preference->company;
        $employee = $user->employee;

        if (!$employee || $employee->company_id !== $company->id) {
            abort(403, 'Unauthorized.');
        }

        $year = (int) $request->input('year', now()->year);
        [$leaveBalances, $leaveDetails] = $this->buildEmployeeLeaveData($employee, $year);

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
        $year = (int) $request->input('year', now()->year);

        return Excel::download(
            new LeaveSummaryExcelExport($user, $company, $year),
            'leave_summary_report.xlsx'
        );
    }

    private function buildEmployeeLeaveData($employee, int $year): array
    {
        $leaveBalances = collect($this->leaveCredits->types())->map(function (string $leaveType) use ($employee, $year) {
            $summary = $this->leaveCredits->summary($employee, $year, $leaveType);
            $beginning = $summary['beginning'];
            $used = $summary['used'];

            return [
                'employee_name' => $employee->user->name ?? 'N/A',
                'department_name' => $employee->department->name ?? null,
                'team_name' => $employee->team->name ?? null,
                'approver_name' => $employee->approver->name ?? null,
                'leave_type' => $summary['label'],
                'beginning_balance' => $beginning,
                'used' => $used,
                'remaining' => $summary['remaining'],
                'utilization' => $beginning > 0 ? round(($used / $beginning) * 100, 1) : 0,
            ];
        });

        $leaveDetails = LeaveRequest::where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->whereYear('start_date', $year)
            ->where('status', 'approved')
            ->where('leave_with_pay', true)
            ->orderBy('start_date')
            ->get();

        return [$leaveBalances, $leaveDetails];
    }
}
