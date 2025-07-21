<?php

namespace App\Exports;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class LeaveSummaryExcelExport implements FromView
{
    protected $user;
    protected $company;
    protected $year;

    public function __construct($user, $company, $year)
    {
        $this->user    = $user;
        $this->company = $company;
        $this->year    = $year;
    }

    public function view(): View
    {
        $employee = $this->user->employee;

        $leaveBalance = LeaveBalance::with(['employee.user', 'employee.department', 'employee.team', 'employee.approver'])
            ->where('company_id', $this->company->id)
            ->where('employee_id', $employee->id)
            ->where('year', $this->year)
            ->first();

        $used = LeaveRequest::where('company_id', $this->company->id)
            ->where('employee_id', $employee->id)
            ->whereYear('start_date', $this->year)
            ->where('status', 'approved')
            ->sum('number_of_days');

        $beginning   = $leaveBalance?->beginning_balance ?? 0;
        $remaining   = max(0, $beginning - $used);
        $utilization = $beginning > 0 ? round(($used / $beginning) * 100, 1) : 0;

        $leaveBalances = collect([[
            'employee_name'     => $employee->user->name       ?? 'N/A',
            'department_name'   => $employee->department->name ?? null,
            'team_name'         => $employee->team->name       ?? null,
            'approver_name'     => $employee->approver->name   ?? null,
            'beginning_balance' => $beginning,
            'used'              => $used,
            'remaining'         => $remaining,
            'utilization'       => $utilization,
        ]]);

        $leaveDetails = LeaveRequest::where('company_id', $this->company->id)
            ->where('employee_id', $employee->id)
            ->whereYear('start_date', $this->year)
            ->where('status', 'approved')
            ->orderBy('start_date')
            ->get();

        return view('reports.leave_summary_excel', [
            'leaveBalances' => $leaveBalances,
            'leaveDetails'  => $leaveDetails,
            'year'          => $this->year,
            'companyName'   => $this->company->name,
        ]);
    }
}
