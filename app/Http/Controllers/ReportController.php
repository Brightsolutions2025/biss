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
                'roles'       => ['admin', 'hr supervisor', 'department head'],
            ],
            [
                'title'       => 'Leave Utilization Summary',
                'description' => 'Shows leave usage vs. balance by employee, department, and leave type.',
                'route'       => 'reports.leave_utilization',
                'permission'  => 'view leave report',
                'roles'       => ['admin', 'hr supervisor', 'department head'],
            ],
            [
                'title'       => 'Overtime vs Offset Report',
                'description' => 'Compare total overtime filed vs. how much has been used for offset.',
                'route'       => 'reports.overtime_offset_comparison',
                'permission'  => 'view overtime report',
                'roles'       => ['admin', 'hr supervisor', 'department head'],
            ],
            [
                'title'       => 'Late and Undertime Report',
                'description' => 'Employees with frequent late arrivals or undertime grouped by department.',
                'route'       => 'reports.late_undertime',
                'permission'  => 'view attendance report',
                'roles'       => ['admin', 'hr supervisor', 'department head'],
            ],
            [
                'title'       => 'Leave Requests by Status',
                'description' => 'Summary of pending, approved, and rejected leave requests over a selected period.',
                'route'       => 'reports.leave_status_overview',
                'permission'  => 'view leave report',
                'roles'       => ['admin', 'hr supervisor', 'department head'],
            ],
            [
                'title'       => 'Outbase Request Summary',
                'description' => 'Monitor volume and distribution of outbase (field) work across employees.',
                'route'       => 'reports.outbase_summary',
                'permission'  => 'view outbase report',
                'roles'       => ['admin', 'hr supervisor', 'department head'],
            ],
            [
                'title'       => 'Offset Usage and Expiry Tracker',
                'description' => 'Track offset usage and monitor expiration of eligible overtime hours.',
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

            // Filed Overtime Report
            [
                'title'       => 'Filed Overtime Report',
                'description' => 'List all your overtime requests with status, hours, and usage.',
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

            // Offset Request Usage Summary
            [
                'title'       => 'Offset Request Summary',
                'description' => 'Detailed view of how your offset hours were applied to absences or undertime.',
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

        $leaveBalancesQuery = \App\Models\LeaveBalance::with(['employee.user', 'employee.department'])
            ->where('company_id', $company->id);

        $leaveBalancesQuery->whereHas('employee', function ($q) {
            $this->restrictToDepartmentHead($q);
        });

        if ($yearFilter) {
            $leaveBalancesQuery->where('year', $yearFilter);
        }

        if ($departmentFilter) {
            $leaveBalancesQuery->whereHas('employee', function ($q) use ($departmentFilter) {
                $q->where('department_id', $departmentFilter);
            });
        }

        $leaveBalances = $leaveBalancesQuery->get()->map(function ($balance) {
            $used = \App\Models\LeaveRequest::where('employee_id', $balance->employee_id)
                ->where('status', 'approved')
                ->whereYear('start_date', $balance->year)
                ->sum('number_of_days');

            return [
                'employee_name' => $balance->employee->user->name       ?? 'N/A',
                'department'    => $balance->employee->department->name ?? 'Unassigned',
                'year'          => $balance->year,
                'beginning'     => $balance->beginning_balance,
                'used'          => $used,
                'remaining'     => $balance->beginning_balance - $used,
            ];
        });

        // Needed for the filter dropdowns
        $departments = \App\Models\Department::where('company_id', $company->id)->get();
        $years       = \App\Models\LeaveBalance::where('company_id', $company->id)
            ->select('year')->distinct()->pluck('year')->sortDesc();

        return view('reports.leave_utilization', compact('leaveBalances', 'departments', 'years', 'yearFilter', 'departmentFilter'));
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
        ])->setPaper('A4', 'portrait');

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

        return $query->get()->map(function ($balance) {
            $used = \App\Models\LeaveRequest::where('employee_id', $balance->employee_id)
                ->where('status', 'approved')
                ->whereYear('start_date', $balance->year)
                ->sum('number_of_days');

            return [
                'employee_name' => $balance->employee->user->name       ?? 'N/A',
                'department'    => $balance->employee->department->name ?? 'Unassigned',
                'year'          => $balance->year,
                'beginning'     => $balance->beginning_balance,
                'used'          => $used,
                'remaining'     => $balance->beginning_balance - $used,
            ];
        });
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

        if ($user->hasRole('department head') && !$user->hasAnyRole(['admin', 'hr supervisor'])) {
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
