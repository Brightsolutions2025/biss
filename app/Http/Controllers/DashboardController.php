<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Team;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\OffsetRequest;
use App\Models\OutbaseRequest;
use App\Models\TimeLog;
use App\Models\TimeRecord;
use App\Models\TimeRecordLine;
use App\Models\PayrollPeriod;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $company = $user->preference->company;

        if (!$company) {
            abort(403, 'No preferred company set.');
        }

        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startYear = Carbon::parse($request->start_date)->year;
            $endYear = Carbon::parse($request->end_date)->year;

            if ($startYear !== $endYear) {
                return back()->withErrors([
                    'end_date' => 'Start date and end date must be in the same year.',
                ])->withInput();
            }
        }

        $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date'] ?? now()->toDateString();
        $year = Carbon::parse($endDate)->year;

        $employee = $user->employee;

        $data = [
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];

        if ($user->hasRole('Employee') && $employee && $employee->company_id === $company->id) {
            $leaveBalance = \App\Models\LeaveBalance::where('employee_id', $employee->id)
                ->where('company_id', $company->id)
                ->where('year', $year)
                ->first();

            $approvedLeaveDays = LeaveRequest::where('employee_id', $employee->id)
                ->where('company_id', $company->id)
                ->whereYear('start_date', $year)
                ->where('start_date', '<=', $endDate)
                ->where('status', 'approved')
                ->sum('number_of_days');

            $remaining = ($leaveBalance->beginning_balance ?? 0) - $approvedLeaveDays;

            $today = $endDate;

            $eligibleOtHours = $employee->overtimeRequests()
                ->where('company_id', $company->id)
                ->where('status', 'approved')
                ->whereDate('date', '<=', $today)
                ->where(function ($query) use ($today) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', $today);
                })
                ->withSum(['offsetOvertimes as used_hours_sum'], 'used_hours')
                ->get()
                ->map(function ($ot) {
                    return max($ot->number_of_hours - ($ot->used_hours_sum ?? 0), 0);
                })
                ->sum();

            $data += [
                'employeeLeaveBalance' => max(0, $remaining),
                'employeeUpcomingLeaves' => $employee->leaveRequests()
                    ->where('company_id', $company->id)
                    ->where('status', 'approved')
                    ->where('end_date', '>', $endDate)
                    ->count(),

                'employeeFiledOtHours' => $employee->overtimeRequests()
                    ->where('company_id', $company->id)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->sum('number_of_hours'),

                'employeeLateCount' => TimeRecordLine::where('late_minutes', '>', 0)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->whereHas('timeRecord', function ($query) use ($employee, $company) {
                        $query->where('employee_id', $employee->id)
                            ->where('company_id', $company->id);
                    })
                    ->count(),

                'employeeUndertimeCount' => TimeRecordLine::where('undertime_minutes', '>', 0)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->whereHas('timeRecord', function ($query) use ($employee, $company) {
                        $query->where('employee_id', $employee->id)
                            ->where('company_id', $company->id);
                    })
                    ->count(),

                'employeeOffsetEligibleOtHours' => $eligibleOtHours,

                'pendingLeaveRequestList' => LeaveRequest::where('company_id', $company->id)
                    ->where('employee_id', $employee->id)
                    ->where('status', 'pending')
                    ->latest()
                    ->get(),

                'pendingOvertimeRequestList' => OvertimeRequest::where('company_id', $company->id)
                    ->where('employee_id', $employee->id)
                    ->where('status', 'pending')
                    ->latest()
                    ->get(),

                'pendingOffsetRequestList' => OffsetRequest::where('company_id', $company->id)
                    ->where('employee_id', $employee->id)
                    ->where('status', 'pending')
                    ->latest()
                    ->get(),

                'pendingOutbaseRequestList' => OutbaseRequest::where('company_id', $company->id)
                    ->where('employee_id', $employee->id)
                    ->where('status', 'pending')
                    ->latest()
                    ->get(),

                'pendingTimeRecordList' => $company->timeRecords()
                    ->where('status', 'pending')
                    ->where('employee_id', $employee->id)
                    ->latest()
                    ->get(),

                'forApprovalLeaveRequestList' => LeaveRequest::with('employee')
                    ->where('company_id', $company->id)
                    ->where('status', 'pending')
                    ->where('approver_id', $employee->user_id)
                    ->latest()
                    ->get(),

                'forApprovalOvertimeRequestList' => OvertimeRequest::with('employee')
                    ->where('company_id', $company->id)
                    ->where('status', 'pending')
                    ->whereHas('employee', function ($q) use ($employee) {
                        $q->where('approver_id', $employee->user_id);
                    })
                    ->latest()
                    ->get(),

                'forApprovalOffsetRequestList' => OffsetRequest::with('employee')
                    ->where('company_id', $company->id)
                    ->where('status', 'pending')
                    ->where('approver_id', $employee->user_id)
                    ->latest()
                    ->get(),

                'forApprovalOutbaseRequestList' => OutbaseRequest::with('employee')
                    ->where('company_id', $company->id)
                    ->where('status', 'pending')
                    ->where('approver_id', $employee->user_id)
                    ->latest()
                    ->get(),

                'forApprovalTimeRecordList' => $company->timeRecords()
                    ->with('employee')
                    ->where('status', 'pending')
                    ->whereHas('employee', function ($q) use ($employee) {
                        $q->where('approver_id', $employee->user_id);
                    })
                    ->latest()
                    ->get(),
            ];
        }

        if ($user->hasAnyRole(['admin', 'hr supervisor'])) {
            $data += [
                'pendingLeaveRequests' => LeaveRequest::where('company_id', $company->id)
                    ->where('status', 'pending')
                    ->whereBetween('start_date', [$startDate, $endDate])
                    ->count(),

                'pendingOvertimeRequests' => OvertimeRequest::where('company_id', $company->id)
                    ->where('status', 'pending')
                    ->whereBetween('date', [$startDate, $endDate])
                    ->count(),

                'pendingOffsetRequests' => OffsetRequest::where('company_id', $company->id)
                    ->where('status', 'pending')
                    ->whereBetween('date', [$startDate, $endDate])
                    ->count(),

                'pendingOutbaseRequests' => OutbaseRequest::where('company_id', $company->id)
                    ->where('status', 'pending')
                    ->whereBetween('date', [$startDate, $endDate])
                    ->count(),

                'pendingTimeRecords' => $company->timeRecords()
                    ->where('status', 'pending')
                    ->whereHas('payrollPeriod', function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('start_date', [$startDate, $endDate])
                            ->orWhereBetween('end_date', [$startDate, $endDate])
                            ->orWhere(function ($q) use ($startDate, $endDate) {
                                $q->where('start_date', '<=', $startDate)
                                    ->where('end_date', '>=', $endDate);
                            });
                    })
                    ->count(),

                'monthlyOtHours' => OvertimeRequest::where('company_id', $company->id)
                    ->where('status', 'approved')
                    ->whereBetween('date', [$startDate, $endDate])
                    ->sum('number_of_hours'),

                'leaveStats' => LeaveRequest::where('company_id', $company->id)
                    ->whereBetween('start_date', [$startDate, $endDate])
                    ->selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray(),

                'departmentEmployeeCounts' => Department::where('company_id', $company->id)
                    ->withCount('employees')
                    ->pluck('employees_count', 'name')
                    ->toArray(),
            ];
        }

        return view('dashboard', $data);
    }
}
