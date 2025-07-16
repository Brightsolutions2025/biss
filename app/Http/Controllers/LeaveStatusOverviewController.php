<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Concerns\WithEvents;

class LeaveStatusOverviewController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $company = $user->preference->company;

        if (!$user->hasPermission('view leave report')) {
            abort(403, 'Unauthorized to view leave reports.');
        }

        $query = LeaveRequest::with('employee.user', 'employee.department')
            ->where('company_id', $company->id);

        $query = $this->restrictToDepartmentHead($query);

        if ($request->filled('date_from')) {
            $query->where('start_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('end_date', '<=', $request->date_to);
        }

        if ($request->filled('department_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        
        if ($request->filled('approver_id')) {
            $query->where('approver_id', $request->approver_id);
        }

        $leaveRequests = $query->get();

        $statusCounts = $leaveRequests
            ->groupBy(fn($leave) => optional($leave->employee->department)->name ?? 'Unassigned')
            ->map(function ($requests) {
                return $requests->groupBy(fn($req) => optional($req->employee->user)->name)
                    ->map(function ($byEmployee) {
                        return [
                            'pending' => $byEmployee->where('status', 'pending')->count(),
                            'approved' => $byEmployee->where('status', 'approved')->count(),
                            'rejected' => $byEmployee->where('status', 'rejected')->count(),
                        ];
                    });
            });

        $departments = Department::where('company_id', $company->id)->get();
        $employees = Employee::with('user')->where('company_id', $company->id)->get();

        return view('reports.leave_status_overview', [
            'statusCounts' => $statusCounts,
            'departments' => $departments,
            'employees' => $employees,
        ]);
    }
    public function exportPdf(Request $request)
    {
        $user = auth()->user();
        $company = $user->preference->company;

        if (!$user->hasPermission('view leave report')) {
            abort(403, 'Unauthorized to view leave reports.');
        }

        $query = \App\Models\LeaveRequest::with(['employee.user', 'employee.department'])
            ->where('company_id', $company->id);
        
        $query = $this->restrictToDepartmentHead($query);

        if ($request->filled('date_from')) {
            $query->where('start_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('start_date', '<=', $request->date_to);
        }

        if ($request->filled('department_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('approver_id')) {
            $query->where('approver_id', $request->approver_id);
        }

        $leaveRequests = $query->get();

        $statusCounts = $leaveRequests->groupBy(fn($leave) => optional($leave->employee->department)->name ?? 'Unassigned')
            ->map(function ($group) {
                return $group->groupBy(fn($leave) => optional($leave->employee->user)->name)
                    ->map(function ($leaves) {
                        return [
                            'Pending' => $leaves->where('status', 'pending')->count(),
                            'Approved' => $leaves->where('status', 'approved')->count(),
                            'Rejected' => $leaves->where('status', 'rejected')->count(),
                        ];
                    });
            });

        $pdf = Pdf::loadView('reports.leave_status_pdf', [
            'statusCounts' => $statusCounts,
            'filters' => $request->only(['date_from', 'date_to', 'department_id', 'employee_id', 'approver_id']),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('leave_status_report.pdf');
    }
    public function exportExcel(Request $request)
    {
        $user = auth()->user();
        $company = $user->preference->company;

        if (!$user->hasPermission('view leave report')) {
            abort(403, 'Unauthorized to view leave reports.');
        }

        $dateFrom = $request->input('date_from') ?? now()->startOfMonth()->toDateString();
        $dateTo = $request->input('date_to') ?? now()->endOfMonth()->toDateString();

        $query = LeaveRequest::with(['employee.user', 'employee.department'])
            ->where('company_id', $company->id)
            ->when($dateFrom, function ($query) use ($dateFrom) {
                $query->whereDate('start_date', '>=', $dateFrom);
            })
            ->when($dateTo, function ($query) use ($dateTo) {
                $query->whereDate('start_date', '<=', $dateTo);
            });

        $query = $this->restrictToDepartmentHead($query);

        $leaveRequests = $query->get();

        $statusCounts = [];

        foreach ($leaveRequests as $requestItem) {
            $department = $requestItem->employee->department->name ?? 'No Department';
            $employee = $requestItem->employee->user->name ?? 'Unknown';

            if (!isset($statusCounts[$department])) {
                $statusCounts[$department] = [];
            }

            if (!isset($statusCounts[$department][$employee])) {
                $statusCounts[$department][$employee] = [
                    'Pending' => 0,
                    'Approved' => 0,
                    'Rejected' => 0,
                ];
            }

            $status = Str::title($requestItem->status);
            $statusCounts[$department][$employee][$status] = ($statusCounts[$department][$employee][$status] ?? 0) + 1;
        }

        $flatData = collect();

        foreach ($statusCounts as $department => $employees) {
            foreach ($employees as $employee => $statuses) {
                $flatData->push([
                    'Department' => $department,
                    'Employee' => $employee,
                    'Pending' => $statuses['Pending'] ?? 0,
                    'Approved' => $statuses['Approved'] ?? 0,
                    'Rejected' => $statuses['Rejected'] ?? 0,
                ]);
            }
        }

        $reportTitle = 'Leave Status Overview';
        $reportDate = now()->format('F d, Y');

        $periodCovered = 'Period Covered: ' . \Carbon\Carbon::parse($dateFrom)->format('F d, Y') 
            . ' - ' . \Carbon\Carbon::parse($dateTo)->format('F d, Y');

        return Excel::download(new class($flatData, $company->name, $reportTitle, $periodCovered, $reportDate) implements 
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithEvents {

            protected $data, $company, $title, $period, $date;

            public function __construct(Collection $data, $company, $title, $period, $date)
            {
                $this->data = $data;
                $this->company = $company;
                $this->title = $title;
                $this->period = $period;
                $this->date = $date;
            }

            public function collection()
            {
                return $this->data;
            }

            public function headings(): array
            {
                return ['Department', 'Employee', 'Pending', 'Approved', 'Rejected'];
            }

            public function registerEvents(): array
            {
                return [
                    \Maatwebsite\Excel\Events\BeforeSheet::class => function ($event) {
                        $sheet = $event->getDelegate();

                        // Insert header rows
                        $sheet->insertNewRowBefore(1, 4);
                        $sheet->setCellValue('A1', $this->company);
                        $sheet->setCellValue('A2', $this->title);
                        $sheet->setCellValue('A3', 'Period Covered: ' . $this->period);
                        $sheet->setCellValue('A4', 'Report Date (As of): ' . $this->date);

                        // Bold header rows
                        foreach (range(1, 4) as $row) {
                            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                        }
                    },
                ];
            }
        }, 'leave_status_report.xlsx');
    }
    protected function restrictToDepartmentHead($query)
    {
        $user = auth()->user();

        if ($user->hasRole('department head') && !$user->hasAnyRole(['admin', 'hr supervisor'])) {
            $dept = \App\Models\Department::where('head_id', $user->id)->first();
            if ($dept) {
                $query->whereHas('employee', function ($q) use ($dept) {
                    $q->where('department_id', $dept->id);
                });
            } else {
                $query->whereRaw('0 = 1'); // Return no data if user is not a head of any department
            }
        }

        return $query;
    }
}
