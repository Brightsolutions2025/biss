<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;

class LeaveCreditService
{
    public const VACATION = 'vacation';
    public const EMERGENCY = 'emergency';

    public function types(): array
    {
        return array_keys(config('leave.types', [
            self::VACATION => [],
            self::EMERGENCY => [],
        ]));
    }

    public function labels(): array
    {
        return collect(config('leave.types', []))
            ->mapWithKeys(fn (array $settings, string $type) => [
                $type => $settings['label'] ?? ucfirst($type),
            ])
            ->all();
    }

    public function label(string $leaveType): string
    {
        return config("leave.types.{$leaveType}.label", ucfirst($leaveType));
    }

    public function defaultAnnualCredits(string $leaveType): float
    {
        return (float) config("leave.types.{$leaveType}.annual_credits", 0);
    }

    public function balance(Employee $employee, int $year, string $leaveType): ?LeaveBalance
    {
        return LeaveBalance::query()
            ->where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->where('year', $year)
            ->where('leave_type', $leaveType)
            ->first();
    }

    public function usedCredits(
        Employee $employee,
        int $year,
        string $leaveType,
        ?int $excludeLeaveRequestId = null
    ): float {
        return (float) LeaveRequest::query()
            ->where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->where('leave_type', $leaveType)
            ->where('status', 'approved')
            ->where('leave_with_pay', true)
            ->whereYear('start_date', $year)
            ->when($excludeLeaveRequestId, fn ($query) => $query->where('id', '!=', $excludeLeaveRequestId))
            ->sum('number_of_days');
    }

    public function summary(
        Employee $employee,
        int $year,
        string $leaveType,
        ?int $excludeLeaveRequestId = null
    ): array {
        $balance = $this->balance($employee, $year, $leaveType);
        $beginning = (float) ($balance?->beginning_balance ?? 0);
        $used = $this->usedCredits($employee, $year, $leaveType, $excludeLeaveRequestId);

        return [
            'type' => $leaveType,
            'label' => $this->label($leaveType),
            'beginning' => $beginning,
            'used' => $used,
            'remaining' => max(0, $beginning - $used),
            'configured' => $balance !== null,
        ];
    }

    public function summaries(Employee $employee, int $year): array
    {
        $summaries = [];

        foreach ($this->types() as $leaveType) {
            $summaries[$leaveType] = $this->summary($employee, $year, $leaveType);
        }

        return $summaries;
    }
}
