<?php

namespace App\Http\Controllers;

use App\Exports\OutbaseSummaryExport;
use App\Models\Employee;
use App\Models\OutbaseRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // If using Barryvdh\DomPDF\Facade\Pdf
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class OutbaseSummaryController extends Controller
{
    public function index(Request $request)
    {
        $user    = Auth::user();
        $company = $user->preference->company;

        if (! $user->hasPermission('view outbase report')) {
            abort(403, 'Unauthorized to view outbase reports.');
        }

        $dateFrom     = $request->input('date_from');
        $dateTo       = $request->input('date_to');
        $departmentId = $request->input('department_id');
        $employeeId   = $request->input('employee_id');

        $query = OutbaseRequest::with(['employee.user', 'employee.department'])
            ->where('company_id', $company->id)
            ->where('status', 'approved');

        $query = $this->restrictToDepartmentHead($query);

        if ($dateFrom && $dateTo) {
            $query->whereBetween('date', [$dateFrom, $dateTo]);
        }

        if ($departmentId) {
            $query->whereHas('employee.department', function ($q) use ($departmentId) {
                $q->where('id', $departmentId);
            });
        }

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $outbaseRequests = $query->get();

        // Group and count per department and employee
        $grouped = $outbaseRequests->groupBy(function ($item) {
            return $item->employee->department->name ?? 'No Department';
        })->map(function ($group) {
            return $group->groupBy(function ($item) {
                return $item->employee->user->name ?? 'Unknown';
            })->map(function ($records) {
                return $records->count();
            });
        });

        $flatData  = collect();
        $locations = collect();

        foreach ($grouped as $department => $employees) {
            foreach ($employees as $employee => $count) {
                $flatData->push([
                    'department'    => $department,
                    'employee'      => $employee,
                    'outbase_count' => $count
                ]);

                // Get all unique locations for this employee
                $employeeLocations = $outbaseRequests->filter(function ($item) use ($employee) {
                    return $item->employee->user->name === $employee;
                })->pluck('location')->unique()->values();

                $locations->put($employee, $employeeLocations);
            }
        }

        $periodCovered = ($dateFrom && $dateTo)
            ? Carbon::parse($dateFrom)->format('F d, Y') . ' - ' . Carbon::parse($dateTo)->format('F d, Y')
            : 'All Time';

        // Filter options
        $departments = \App\Models\Department::where('company_id', $company->id)->orderBy('name')->get();
        $employees   = Employee::with('user')
            ->where('company_id', $company->id)
            ->orderBy(
                Employee::select('name')
                ->join('users', 'users.id', '=', 'employees.user_id')
                ->whereColumn('employees.id', 'employees.id')
                ->limit(1)
            )
            ->get();

        return view('reports.outbase_summary', [
            'data'        => $flatData,
            'locations'   => $locations,
            'period'      => $periodCovered,
            'company'     => $company->name,
            'departments' => $departments,
            'employees'   => $employees,
        ]);
    }
    public function exportExcel(Request $request)
    {
        $user    = auth()->user();
        $company = $user->preference->company;

        if (!$user->hasPermission('view outbase report')) {
            abort(403, 'Unauthorized.');
        }

        // You may want to include department head's department as filter
        if ($user->hasRole('department head') && !$user->hasAnyRole(['admin', 'hr supervisor'])) {
            $dept = \App\Models\Department::where('head_id', $user->id)->first();
            if ($dept) {
                $request->merge(['department_id' => $dept->id]);
            } else {
                // Return empty Excel if department not found
                return Excel::download(new OutbaseSummaryExport(['empty' => true], $company), 'outbase_summary.xlsx');
            }
        }

        $filters              = $request->only(['date_from', 'date_to', 'department_id', 'employee_id', 'location', 'sort']);
        $filters['date_from'] = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $filters['date_to']   = $filters['date_to']   ?? now()->endOfMonth()->toDateString();

        return Excel::download(
            new OutbaseSummaryExport($filters, $company),
            'outbase_summary.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $user    = auth()->user();
        $company = $user->preference->company;
        if (! $user->hasPermission('view outbase report')) {
            abort(403, 'Unauthorized to view outbase reports.');
        }

        $data = $this->getFilteredOutbaseData($request, $company);

        $pdf = Pdf::loadView('reports.outbase_summary_pdf', [
            'data'      => $data['flatData'],
            'locations' => $data['locations'],
            'period'    => $data['periodCovered'],
            'company'   => $company->name,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('outbase_summary_report.pdf');
    }

    private function getFilteredOutbaseData(Request $request, $company): array
    {
        $outbaseRequests = OutbaseRequest::with('employee.user', 'employee.department')
            ->where('company_id', $company->id)
            ->approved();

        $outbaseRequests = $this->restrictToDepartmentHead($outbaseRequests);

        if ($request->filled('date_from')) {
            $outbaseRequests->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $outbaseRequests->whereDate('date', '<=', $request->date_to);
        }

        $outbaseRequests = $outbaseRequests->get();

        $grouped = $outbaseRequests->groupBy(function ($item) {
            return optional($item->employee->department)->name ?? 'No Department';
        })->map(function ($group) {
            return $group->groupBy(function ($item) {
                return $item->employee->user->name;
            })->map->count();
        });

        $flatData  = collect();
        $locations = collect();

        foreach ($grouped as $department => $employees) {
            foreach ($employees as $employee => $count) {
                $flatData->push([
                    'department'    => $department,
                    'employee'      => $employee,
                    'outbase_count' => $count,
                ]);

                $employeeLocations = $outbaseRequests->filter(function ($item) use ($employee) {
                    return $item->employee->user->name === $employee;
                })->pluck('location')->unique()->values();

                $locations->put($employee, $employeeLocations);
            }
        }

        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->input('date_to', now()->endOfMonth()->toDateString());

        $periodCovered = 'from ' . Carbon::parse($dateFrom)->format('F d, Y') . ' to ' . Carbon::parse($dateTo)->format('F d, Y');

        return [
            'flatData'      => $flatData,
            'locations'     => $locations,
            'periodCovered' => $periodCovered,
        ];
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
