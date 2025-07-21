<?php

namespace App\Exports;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LeaveSummaryExport implements FromCollection, WithHeadings
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

    public function collection(): Collection
    {
        $employee = $this->user->employee;

        $leaveBalance = LeaveBalance::where('company_id', $this->company->id)
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

        return collect([
            [
                'Employee'           => $employee->user->name,
                'Department'         => $employee->department->name ?? '',
                'Team'               => $employee->team->name       ?? '',
                'Approver'           => $employee->approver->name   ?? '',
                'Year'               => $this->year,
                'Beginning Balance'  => $beginning,
                'Used'               => $used,
                'Remaining'          => $remaining,
                'Utilization (%)'    => $utilization,
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'Employee',
            'Department',
            'Team',
            'Approver',
            'Year',
            'Beginning Balance',
            'Used',
            'Remaining',
            'Utilization (%)',
        ];
    }
}
