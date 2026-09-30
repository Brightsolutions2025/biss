<?php

namespace App\Http\Controllers;

use App\Exports\DtrStatusExport;
use App\Exports\LeaveUtilizationExport;
use App\Exports\OvertimeOffsetExport;
use App\Models\{Employee, PayrollPeriod, TimeRecord};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $reports = [
            [
                'title'       => 'Employee DTR Status by Department & Team',
                'description' => 'Track DTR status of employees by department and team: Not submitted, Submitted, and Approved.',
                'route'       => 'reports.dtr_status_by_team',
                'permission'  => 'view time record report',
                'roles'       => ['admin', 'hr supervisor', 'department head', 'finance hris'],
            ],
            [
                'title'       => 'Leave Utilization Summary',
                'description' => 'Shows leave usage vs. balance by employee, department, and leave type.',
                'route'       => 'reports.leave_utilization',
                'permission'  => 'view leave report',
                'roles'       => ['admin', 'hr supervisor', 'department head', 'finance hris'],
            ],
            [
                'title'       => 'Compensatory Overtime Credit vs Compensatory Time-Off Report',
                'description' => 'Compare total overtime filed vs. how much has been used for offset.',
                'route'       => 'reports.overtime_offset_comparison',
                'permission'  => 'view overtime report',
                'roles'       => ['admin', 'hr supervisor', 'department head', 'finance hris'],
            ],
            [
                'title'       => 'Late and Undertime Report',
                'description' => 'Employees with frequent late arrivals or undertime grouped by department.',
                'route'       => 'reports.late_undertime',
                'permission'  => 'view attendance report',
                'roles'       => ['admin', 'hr supervisor', 'department head', 'finance hris'],
            ],
            [
                'title'       => 'Leave Requests by Status',
                'description' => 'Summary of pending, approved, and rejected leave requests over a selected period.',
                'route'       => 'reports.leave_status_overview',
                'permission'  => 'view leave report',
                'roles'       => ['admin', 'hr supervisor', 'department head', 'finance hris'],
            ],
            [
                'title'       => 'Outbase Request Summary',
                'description' => 'Monitor volume and distribution of outbase (field) work across employees.',
                'route'       => 'reports.outbase_summary',
                'permission'  => 'view outbase report',
                'roles'       => ['admin', 'hr supervisor', 'department head', 'finance hris'],
            ],
            [
                'title'       => 'Compensatory Time-Off Usage and Expiry Tracker',
                'description' => 'Track Compensatory Time-Off usage and monitor expiration of eligible Compensatory Overtime Credit hours.',
                'route'       => 'reports.offset_tracker',
                'permission'  => 'view offset report',
                'roles'       => ['admin', 'hr supervisor', 'employee'],
            ],
            // Leave Summary Report
            [
                'title'       => 'Leave Summary Report',
                'description' => 'View yearly leave balance, used leaves, and remaining credits.',
                'route'       => 'reports.leave_summary',
                'permission'  => 'view leave report',
                'roles'       => ['admin', 'hr supervisor', 'employee'],
            ],

            // Compensatory Overtime Credit Report
            [
                'title'       => 'Compensatory Overtime Credit Report',
                'description' => 'List all your Compensatory Overtime Credit requests with status, hours, and usage.',
                'route'       => 'reports.overtime_history',
                'permission'  => 'view overtime report',
                'roles'       => ['admin', 'hr supervisor', 'employee'],
            ],

            // Approved Leaves Timeline
            [
                'title'       => 'Approved Leaves Timeline',
                'description' => 'See a timeline of your past and upcoming approved leaves.',
                'route'       => 'reports.leave_timeline',
                'permission'  => 'view leave report',
                'roles'       => ['admin', 'hr supervisor', 'employee'],
            ],

            // Field Work (Outbase) Report
            [
                'title'       => 'Outbase Request Report',
                'description' => 'Review history of your field work requests including dates and locations.',
                'route'       => 'reports.outbase_history',
                'permission'  => 'view outbase report',
                'roles'       => ['admin', 'hr supervisor', 'employee'],
            ],

            // Compensatory Time-Off Request Usage Summary
            [
                'title'       => 'Compensatory Time-Off Summary',
                'description' => 'Detailed view of how your Compensatory Time-Off hours were applied to absences or undertime.',
                'route'       => 'reports.offset_summary',
                'permission'  => 'view offset report',
                'roles'       => ['admin', 'hr supervisor', 'employee'],
            ],
        ];

        /*
        $reports = collect($reports)->filter(function ($report) {
            return auth()->user()->can($report['permission']);
        });
        */

        return view('reports.index', compact('reports'));
    }
    public function dtrStatusByTeam(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermission('view time record report')) {
            abort(403, 'Unauthorized to view time record reports.');
        }

        $companyId       = auth()->user()->preference->company_id;
        $payrollPeriodId = $request->input('payroll_period_id');

        $payrollPeriods = PayrollPeriod::where('company_id', $companyId)->orderByDesc('start_date')->get();

        if (!$payrollPeriodId && $payrollPeriods->isNotEmpty()) {
            $payrollPeriodId = $payrollPeriods->first()->id;
        }

        // Base employee list for the company
        $employeesQuery = Employee::with(['department', 'team'])
            ->where('company_id', $companyId);

        $employeesQuery = $this->restrictToDepartmentHead($employeesQuery);

        $employees = $employeesQuery->get();

        // Time records for this payroll period
        $timeRecords = TimeRecord::where('company_id', $companyId)
            ->where('payroll_period_id', $payrollPeriodId)
            ->get()
            ->keyBy('employee_id');

        // Determine status for each employee
        $reportData = $employees->map(function ($employee) use ($timeRecords) {
            $record = $timeRecords->get($employee->id);
            $status = 'Not Submitted';

            if ($record) {
                if ($record->status === 'approved') {
                    $status = 'Approved';
                } elseif ($record->status === 'rejected') {
                    $status = 'Rejected';
                } elseif (is_null($record->status)) {
                    $status = null; // or use 'N/A' or 'Pending Review'
                } else {
                    $status = 'Submitted';
                }
            }

            return [
                'employee'   => $employee,
                'department' => optional($employee->department)->name,
                'team'       => optional($employee->team)->name,
                'status'     => $status,
            ];
        });

        return view('reports.dtr_status_by_team', compact('reportData', 'payrollPeriods', 'payrollPeriodId'));
    }
    private function getDtrStatusData($companyId, $payrollPeriodId)
    {
        $employeesQuery = Employee::with(['department', 'team'])
            ->where('company_id', $companyId);

        $employeesQuery = $this->restrictToDepartmentHead($employeesQuery);

        $employees = $employeesQuery->get();

        $timeRecords = TimeRecord::where('company_id', $companyId)
            ->where('payroll_period_id', $payrollPeriodId)
            ->get()
            ->keyBy('employee_id');

        return $employees->map(function ($employee) use ($timeRecords) {
            $record = $timeRecords->get($employee->id);
            $status = 'Not Submitted';

            if ($record) {
                if ($record->status === 'approved') {
                    $status = 'Approved';
                } elseif ($record->status === 'rejected') {
                    $status = 'Rejected';
                } elseif (is_null($record->status)) {
                    $status = null;
                } else {
                    $status = 'Submitted';
                }
            }

            return [
                'employee'   => $employee,
                'department' => optional($employee->department)->name,
                'team'       => optional($employee->team)->name,
                'status'     => $status,
            ];
        });
    }
    public function downloadPdf(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermission('view time record report')) {
            abort(403, 'Unauthorized to download DTR reports.');
        }

        $companyId       = auth()->user()->preference->company_id;
        $payrollPeriodId = $request->input('payroll_period_id');

        if (!$payrollPeriodId) {
            $payrollPeriodId = PayrollPeriod::where('company_id', $companyId)
                ->orderByDesc('start_date')
                ->value('id');
        }

        $reportData    = $this->getDtrStatusData($companyId, $payrollPeriodId);
        $payrollPeriod = PayrollPeriod::find($payrollPeriodId);

        $pdf = Pdf::loadView('reports.dtr_status_by_team_pdf', compact('reportData', 'payrollPeriod'));
        return $pdf->download('DTR_Status_Report.pdf');
    }
    public function downloadExcel(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermission('view time record report')) {
            abort(403, 'Unauthorized to download DTR reports.');
        }

        $companyId       = auth()->user()->preference->company_id;
        $payrollPeriodId = $request->input('payroll_period_id');

        if (!$payrollPeriodId) {
            $payrollPeriodId = PayrollPeriod::where('company_id', $companyId)
                ->orderByDesc('start_date')
                ->value('id');
        }

        $reportData = $this->getDtrStatusData($companyId, $payrollPeriodId);

        $payrollPeriod = PayrollPeriod::where('company_id', $companyId)
            ->where('id', $payrollPeriodId)
            ->first();

        return Excel::download(new DtrStatusExport($reportData, $payrollPeriod), 'DTR_Status_Report.xlsx');
    }
    public function leaveUtilization(Request $request)
    {
        $user    = auth()->user();
        $company = $user->preference->company;

        if (!$user->hasPermission('view leave report')) {
            abort(403, 'Unauthorized to view leave reports.');
        }

        $yearFilter       = $request->input('year');
        $departmentFilter = $request->input('department_id');
        $leaveBalances    = $this->getLeaveUtilizationData($request, $company);

        // Needed for the filter dropdowns.
        $departments = \App\Models\Department::where('company_id', $company->id)
            ->orderBy('name')
            ->get();

        $years = \App\Models\LeaveBalance::where('company_id', $company->id)
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return view('reports.leave_utilization', compact(
            'leaveBalances',
            'departments',
            'years',
            'yearFilter',
            'departmentFilter'
        ));
    }

    public function leaveUtilizationPdf(Request $request)
    {
        $user    = auth()->user();
        $company = $user->preference->company;

        if (!$user->hasPermission('view leave report')) {
            abort(403, 'Unauthorized to view leave reports.');
        }

        $data          = $this->getLeaveUtilizationData($request, $company);
        $periodCovered = $this->getPeriodText($request);

        $pdf = Pdf::loadView('reports.leave_utilization_pdf', [
            'company'       => $company,
            'leaveBalances' => $data,
            'periodCovered' => $periodCovered,
        ])->setPaper('A4', 'landscape');

        return $pdf->download('leave_utilization_summary.pdf');
    }

    public function leaveUtilizationExcel(Request $request)
    {
        $user    = auth()->user();
        $company = $user->preference->company;

        if (!$user->hasPermission('view leave report')) {
            abort(403, 'Unauthorized to view leave reports.');
        }

        $data          = $this->getLeaveUtilizationData($request, $company);
        $periodCovered = $this->getPeriodText($request);

        return Excel::download(
            new LeaveUtilizationExport($company, $data, $periodCovered),
            'leave_utilization_summary.xlsx'
        );
    }

    /**
     * Build one leave-utilization row per employee/year and keep Vacation Leave
     * and Emergency Leave completely separate.
     */
    protected function getLeaveUtilizationData(Request $request, $company)
    {
        $yearFilter       = $request->input('year');
        $departmentFilter = $request->input('department_id');

        $query = \App\Models\LeaveBalance::with(['employee.user', 'employee.department'])
            ->where('company_id', $company->id);

        $query->whereHas('employee', function ($q) {
            $this->restrictToDepartmentHead($q);
        });

        if ($yearFilter) {
            $query->where('year', $yearFilter);
        }

        if ($departmentFilter) {
            $query->whereHas('employee', function ($q) use ($departmentFilter) {
                $q->where('department_id', $departmentFilter);
            });
        }

        $balances = $query->get();

        if ($balances->isEmpty()) {
            return collect();
        }

        $employeeIds = $balances->pluck('employee_id')->unique()->values();
        $years       = $balances->pluck('year')->map(fn ($year) => (int) $year)->unique()->values();
        $minYear     = (int) $years->min();
        $maxYear     = (int) $years->max();

        // Aggregate approved paid usage once, by employee/year/leave type.
        $usedCredits = \App\Models\LeaveRequest::query()
            ->selectRaw("employee_id, YEAR(start_date) as leave_year, COALESCE(leave_type, 'vacation') as leave_type, SUM(number_of_days) as used")
            ->where('company_id', $company->id)
            ->whereIn('employee_id', $employeeIds)
            ->where('status', 'approved')
            ->where('leave_with_pay', true)
            ->whereDate('start_date', '>=', sprintf('%04d-01-01', $minYear))
            ->whereDate('start_date', '<=', sprintf('%04d-12-31', $maxYear))
            ->groupBy('employee_id', \Illuminate\Support\Facades\DB::raw('YEAR(start_date)'), \Illuminate\Support\Facades\DB::raw("COALESCE(leave_type, 'vacation')"))
            ->get()
            ->keyBy(fn ($row) => $row->employee_id . '|' . $row->leave_year . '|' . $row->leave_type);

        return $balances
            ->groupBy(fn ($balance) => $balance->employee_id . '|' . $balance->year)
            ->map(function ($group) use ($usedCredits) {
                $first      = $group->first();
                $employeeId = $first->employee_id;
                $year       = (int) $first->year;

                $vacationBalance  = $group->firstWhere('leave_type', 'vacation');
                $emergencyBalance = $group->firstWhere('leave_type', 'emergency');

                $vacationBeginning  = (float) ($vacationBalance?->beginning_balance ?? 0);
                $emergencyBeginning = (float) ($emergencyBalance?->beginning_balance ?? 0);

                $vacationUsed = (float) optional(
                    $usedCredits->get($employeeId . '|' . $year . '|vacation')
                )->used;

                $emergencyUsed = (float) optional(
                    $usedCredits->get($employeeId . '|' . $year . '|emergency')
                )->used;

                return [
                    'employee_name' => $first->employee->user->name       ?? 'N/A',
                    'department'    => $first->employee->department->name ?? 'Unassigned',
                    'year'          => $year,

                    'vacation_opening'   => $vacationBeginning,
                    'vacation_used'      => $vacationUsed,
                    'vacation_remaining' => $vacationBeginning - $vacationUsed,

                    'emergency_opening'   => $emergencyBeginning,
                    'emergency_used'      => $emergencyUsed,
                    'emergency_remaining' => $emergencyBeginning - $emergencyUsed,
                ];
            })
            ->sortBy([
                ['employee_name', 'asc'],
                ['year', 'desc'],
            ])
            ->values();
    }
    protected function getPeriodText(Request $request): string
    {
        $year         = $request->input('year');
        $departmentId = $request->input('department_id');
        $parts        = [];

        if ($year) {
            $parts[] = "Year: $year";
        }

        if ($departmentId) {
            $department = \App\Models\Department::find($departmentId);
            if ($department) {
                $parts[] = "Department: {$department->name}";
            }
        }

        return count($parts) ? implode(' | ', $parts) : 'All Records';
    }
    public function overtimeOffsetComparison(Request $request)
    {
        $user    = auth()->user();
        $company = $user->preference->company;

        if (!$user->hasPermission('view overtime report')) {
            abort(403, 'Unauthorized to view overtime reports.');
        }

        $departmentId = $request->input('department_id');
        $employeeId   = $request->input('employee_id');

        $employeesQuery = \App\Models\Employee::with('user', 'department')
            ->where('company_id', $company->id);

        $employeesQuery = $this->restrictToDepartmentHead($employeesQuery);

        if ($departmentId) {
            $employeesQuery->where('department_id', $departmentId);
        }

        if ($employeeId) {
            $employeesQuery->where('id', $employeeId);
        }

        $asOf = $request->input('as_of', now()->toDateString());

        $employees = $employeesQuery->get()->map(function ($employee) use ($company, $asOf) {
            $overtimeRequests = \App\Models\OvertimeRequest::where('company_id', $company->id)
                ->where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereDate('date', '<=', $asOf)
                ->get();

            $totalOvertime   = $overtimeRequests->sum('number_of_hours');
            $expiredOvertime = $overtimeRequests->where('expires_at', '<', $asOf)->sum('number_of_hours');
            $validOvertime   = $totalOvertime - $expiredOvertime;

            $totalOffset = \App\Models\OffsetOvertime::where('company_id', $company->id)
                ->whereHas('offsetRequest', function ($q) use ($employee, $asOf) {
                    $q->where('employee_id', $employee->id)
                    ->where('status', 'approved')
                    ->whereDate('date', '<=', $asOf);
                })
                ->sum('used_hours');

            return [
                'employee_name'        => $employee->user->name       ?? 'N/A',
                'department'           => $employee->department->name ?? 'Unassigned',
                'overtime_hours'       => $totalOvertime,
                'expired_hours'        => $expiredOvertime,
                'valid_overtime_hours' => $validOvertime,
                'offset_hours'         => $totalOffset,
                'balance'              => $validOvertime - $totalOffset,
            ];
        });

        $departments     = \App\Models\Department::where('company_id', $company->id)->get();
        $employeeOptions = \App\Models\Employee::with('user')->where('company_id', $company->id)->get();

        return view('reports.overtime_offset_comparison', compact('employees', 'departments', 'employeeOptions'));
    }
    public function overtimeOffsetComparisonPdf(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermission('view overtime report')) {
            abort(403, 'Unauthorized to download overtime reports.');
        }

        $data = $this->generateOvertimeOffsetData($request);
        $pdf  = PDF::loadView('reports.overtime_offset_pdf', $data);
        return $pdf->download('Overtime_vs_Offset_Report.pdf');
    }

    public function overtimeOffsetComparisonExcel(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermission('view overtime report')) {
            abort(403, 'Unauthorized to download overtime reports.');
        }

        return Excel::download(
            new OvertimeOffsetExport($request),
            'Overtime_vs_Offset_Report.xlsx'
        );
    }
    public function generateOvertimeOffsetData(Request $request)
    {
        $user    = auth()->user();
        $company = $user->preference->company;

        $asOf         = $request->input('as_of', now()->toDateString());
        $departmentId = $request->input('department_id');
        $employeeId   = $request->input('employee_id');

        $employeesQuery = \App\Models\Employee::with('user', 'department')
            ->where('company_id', $company->id);

        $employeesQuery = $this->restrictToDepartmentHead($employeesQuery);

        if ($departmentId) {
            $employeesQuery->where('department_id', $departmentId);
        }

        if ($employeeId) {
            $employeesQuery->where('id', $employeeId);
        }

        $employees = $employeesQuery->get()->map(function ($employee) use ($company, $asOf) {
            $totalOvertime = \App\Models\OvertimeRequest::where('company_id', $company->id)
                ->where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereDate('date', '<=', $asOf)
                ->sum('number_of_hours');

            $expiredOvertime = \App\Models\OvertimeRequest::where('company_id', $company->id)
                ->where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereDate('expires_at', '<', $asOf)
                ->sum('number_of_hours');

            $validOvertime = $totalOvertime - $expiredOvertime;

            $totalOffset = \App\Models\OffsetOvertime::where('company_id', $company->id)
                ->whereHas('offsetRequest', function ($q) use ($employee, $asOf) {
                    $q->where('employee_id', $employee->id)
                        ->where('status', 'approved')
                        ->whereDate('date', '<=', $asOf);
                })
                ->sum('used_hours');

            return [
                'company_name'          => $company->name,
                'employee_name'         => $employee->user->name       ?? 'N/A',
                'department'            => $employee->department->name ?? 'Unassigned',
                'overtime_hours'        => $totalOvertime,
                'expired_hours'         => $expiredOvertime,
                'valid_overtime_hours'  => $validOvertime,
                'offset_hours'          => $totalOffset,
                'balance'               => $validOvertime - $totalOffset,
            ];
        })->values()->toArray(); // Convert collection to plain array

        return [
            'employees' => $employees,
            'asOf'      => $asOf,
        ];
    }
    protected function restrictToDepartmentHead($query)
    {
        $user = auth()->user();

        if ($user->hasRole('department head') && !$user->hasAnyRole(['admin', 'hr supervisor', 'finance hris'])) {
            $dept = \App\Models\Department::where('head_id', $user->id)->first();
            if ($dept) {
                $query->where('department_id', $dept->id);
            } else {
                $query->whereRaw('0 = 1'); // Return no data if user is not a head of any department
            }
        }

        return $query;
    }
}
