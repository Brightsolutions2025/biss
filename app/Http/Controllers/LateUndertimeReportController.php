<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TimeRecordLine;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\LateUndertimeExport;
use Maatwebsite\Excel\Facades\Excel;

class LateUndertimeReportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $company = $user->preference->company;

        if (!$user->hasPermission('view attendance report')) {
            abort(403, 'Unauthorized to view attendance reports.');
        }

        // Apply default dates if not provided
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->endOfMonth()->toDateString());

        $query = TimeRecordLine::with(['timeRecord.employee.user', 'timeRecord.employee.department'])
            ->where('company_id', $company->id)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->where(function ($q) {
                $q->where('late_minutes', '>', 0)
                  ->orWhere('undertime_minutes', '>', 0);
            });
        
        $query = $this->restrictToDepartmentHead($query);

        if ($request->filled('department_id')) {
            $query->whereHas('timeRecord.employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        if ($request->filled('employee_id')) {
            $query->whereHas('timeRecord.employee', function ($q) use ($request) {
                $q->where('id', $request->employee_id);
            });
        }

        $records = $query->get();

        $grouped = $records
            ->groupBy(fn($line) => optional($line->timeRecord->employee->department)->name ?? 'Unassigned')
            ->map(function ($group) use ($request) {
                return $group->groupBy(fn($line) => optional($line->timeRecord->employee->user)->name)
                    ->map(function ($records) use ($request) {
                        $late = $records->sum('late_minutes');
                        $undertime = $records->sum('undertime_minutes');

                        $minLate = (float) $request->input('min_late', 0);
                        $minUndertime = (float) $request->input('min_undertime', 0);

                        return ($late >= $minLate || $undertime >= $minUndertime)
                            ? [
                                'late_minutes' => $late,
                                'undertime_minutes' => $undertime,
                            ]
                            : null;
                    })->filter(); // Remove nulls
            })->filter(); // Remove empty departments

        return view('reports.late_undertime', [
            'grouped' => $grouped,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }
    public function exportPdf(Request $request)
    {
        $user = auth()->user();
        $company = $user->preference->company;

        if (!$user->hasPermission('view attendance report')) {
            abort(403, 'Unauthorized to view attendance reports.');
        }

        // Reuse your index logic or extract to a private method if needed
        $query = TimeRecordLine::with(['timeRecord.employee.user', 'timeRecord.employee.department'])
            ->where('company_id', $company->id)
            ->where(function ($q) {
                $q->where('late_minutes', '>', 0)
                ->orWhere('undertime_minutes', '>', 0);
            });
        
        $query = $this->restrictToDepartmentHead($query);

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        if ($request->filled('department_id')) {
            $query->whereHas('timeRecord.employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        if ($request->filled('employee_id')) {
            $query->whereHas('timeRecord.employee', function ($q) use ($request) {
                $q->where('id', $request->employee_id);
            });
        }

        $records = $query->get();

        $grouped = $records
            ->groupBy(fn($line) => optional($line->timeRecord->employee->department)->name ?? 'Unassigned')
            ->map(function ($group) use ($request) {
                return $group->groupBy(fn($line) => optional($line->timeRecord->employee->user)->name)
                    ->map(function ($records) {
                        return [
                            'late_minutes' => $records->sum('late_minutes'),
                            'undertime_minutes' => $records->sum('undertime_minutes'),
                        ];
                    });
            });

        $pdf = Pdf::loadView('reports.late_undertime_pdf', [
            'grouped' => $grouped,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ]);

        return $pdf->download('late-undertime-report.pdf');
    }
    public function exportExcel(Request $request)
    {
        $user = auth()->user();
        $company = $user->preference->company;

        if (!$user->hasPermission('view attendance report')) {
            abort(403, 'Unauthorized to export attendance reports.');
        }

        // Generate report data
        $query = TimeRecordLine::with(['timeRecord.employee.user', 'timeRecord.employee.department'])
            ->where('company_id', $company->id)
            ->where(function ($q) {
                $q->where('late_minutes', '>', 0)
                ->orWhere('undertime_minutes', '>', 0);
            });

        $query = $this->restrictToDepartmentHead($query);

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        if ($request->filled('department_id')) {
            $query->whereHas('timeRecord.employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        if ($request->filled('employee_id')) {
            $query->whereHas('timeRecord.employee', function ($q) use ($request) {
                $q->where('id', $request->employee_id);
            });
        }

        $records = $query->get();

        $grouped = $records
            ->groupBy(fn($line) => optional($line->timeRecord->employee->department)->name ?? 'Unassigned')
            ->map(function ($group) {
                return $group->groupBy(fn($line) => optional($line->timeRecord->employee->user)->name)
                    ->map(function ($records) {
                        return [
                            'late_minutes' => $records->sum('late_minutes'),
                            'undertime_minutes' => $records->sum('undertime_minutes'),
                        ];
                    });
            });

        return Excel::download(
            new LateUndertimeExport($grouped, $request->date_from, $request->date_to, $company),
            'late-undertime-report.xlsx'
        );
    }
    protected function restrictToDepartmentHead($query)
    {
        $user = auth()->user();

        if ($user->hasRole('department head') && !$user->hasAnyRole(['admin', 'hr supervisor'])) {
            $dept = \App\Models\Department::where('head_id', $user->id)->first();
            if ($dept) {
                $query->whereHas('timeRecord.employee', function ($q) use ($dept) {
                    $q->where('department_id', $dept->id);
                });
            } else {
                $query->whereRaw('0 = 1'); // Return no data if user is not a head of any department
            }
        }

        return $query;
    }
}
