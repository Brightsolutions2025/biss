<?php

namespace App\Exports;

use App\Services\LeaveCreditService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LeaveSummaryExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected $user,
        protected $company,
        protected $year
    ) {
    }

    public function collection(): Collection
    {
        $employee = $this->user->employee;
        $leaveCredits = app(LeaveCreditService::class);

        return collect($leaveCredits->types())->map(function (string $leaveType) use ($employee, $leaveCredits) {
            $summary = $leaveCredits->summary($employee, (int) $this->year, $leaveType);
            $beginning = $summary['beginning'];
            $used = $summary['used'];

            return [
                'Employee' => $employee->user->name,
                'Department' => $employee->department->name ?? '',
                'Team' => $employee->team->name ?? '',
                'Approver' => $employee->approver->name ?? '',
                'Year' => $this->year,
                'Leave Type' => $summary['label'],
                'Beginning Balance' => $beginning,
                'Used' => $used,
                'Remaining' => $summary['remaining'],
                'Utilization (%)' => $beginning > 0 ? round(($used / $beginning) * 100, 1) : 0,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Employee',
            'Department',
            'Team',
            'Approver',
            'Year',
            'Leave Type',
            'Beginning Balance',
            'Used',
            'Remaining',
            'Utilization (%)',
        ];
    }
}
