<?php

namespace App\Exports;

use App\Models\LeaveRequest;
use App\Services\LeaveCreditService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class LeaveSummaryExcelExport implements FromView
{
    public function __construct(
        protected $user,
        protected $company,
        protected $year
    ) {
    }

    public function view(): View
    {
        $employee = $this->user->employee;
        $leaveCredits = app(LeaveCreditService::class);

        $leaveBalances = collect($leaveCredits->types())->map(function (string $leaveType) use ($employee, $leaveCredits) {
            $summary = $leaveCredits->summary($employee, (int) $this->year, $leaveType);
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

        $leaveDetails = LeaveRequest::where('company_id', $this->company->id)
            ->where('employee_id', $employee->id)
            ->whereYear('start_date', $this->year)
            ->where('status', 'approved')
            ->where('leave_with_pay', true)
            ->orderBy('start_date')
            ->get();

        return view('reports.leave_summary_excel', [
            'leaveBalances' => $leaveBalances,
            'leaveDetails' => $leaveDetails,
            'year' => $this->year,
            'companyName' => $this->company->name,
        ]);
    }
}
