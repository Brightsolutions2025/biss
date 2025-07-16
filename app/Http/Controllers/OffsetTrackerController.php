<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OvertimeRequest;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OffsetTrackerExport;

class OffsetTrackerController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $company = $user->preference->company;

        if (!$user->hasPermission('view offset report')) {
            abort(403, 'Unauthorized to view offset report.');
        }

        $employee = $user->employee;
        if (!$employee || $employee->company_id !== $company->id) {
            abort(403, 'Employee record not found or unauthorized.');
        }

        // Default date range: start of last year to today
        $from = $request->input('from') ?? now()->subYear()->startOfYear()->format('Y-m-d');
        $to = $request->input('to') ?? now()->format('Y-m-d');

        $employeeModel = Employee::with([
            'user',
            'department',
            'overtimeRequests.offsetRequests' => function ($query) use ($from, $to) {
                $query->whereBetween('date', [$from, $to]);
            }
        ])
        ->where('id', $employee->id)
        ->where('company_id', $company->id)
        ->first();

        $query = OvertimeRequest::with(['employee.user', 'offsetRequests'])
            ->where('company_id', $company->id)
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to);

        $offsetData = $query->get()->map(function ($ot) {
            $used = $ot->offsetRequests->sum(fn($offset) => $offset->pivot->used_hours);
            return [
                'employee_name'    => $ot->employee->user->name,
                'date'             => $ot->date,
                'approved_hours'   => $ot->number_of_hours,
                'used_hours'       => $used,
                'remaining_hours'  => max(0, $ot->number_of_hours - $used),
                'expires_at'       => $ot->expires_at,
                'expired'          => $ot->expires_at && now()->gt($ot->expires_at),
            ];
        });

        return view('reports.offset_tracker', [
            'offsetData' => $offsetData,
            'from' => $from,
            'to' => $to,
            'employeeModel' => $employeeModel,
        ]);
    }

    public function offsetTrackerPdf(Request $request)
    {
        $offsetData = $this->getOffsetData($request);

        $user = Auth::user();
        $company = $user->preference->company;

        $periodText = 'All Dates';
        if ($request->filled('from') || $request->filled('to')) {
            $from = $request->filled('from') ? date('M d, Y', strtotime($request->from)) : '...';
            $to = $request->filled('to') ? date('M d, Y', strtotime($request->to)) : '...';
            $periodText = "{$from} to {$to}";
        }

        return Pdf::loadView('reports.offset_tracker_pdf', [
            'offsetData' => $offsetData,
            'periodText' => $periodText,
            'companyName' => $company->name,
        ])->download('offset_tracker.pdf');
    }

    public function offsetTrackerExcel(Request $request)
    {
        $user = Auth::user();
        $company = $user->preference->company;

        $from = $request->filled('from') ? $request->from : null;
        $to = $request->filled('to') ? $request->to : null;

        $periodText = 'All Dates';
        if ($from || $to) {
            $fromFormatted = $from ? date('M d, Y', strtotime($from)) : '...';
            $toFormatted = $to ? date('M d, Y', strtotime($to)) : '...';
            $periodText = "$fromFormatted to $toFormatted";
        }

        return Excel::download(
            new OffsetTrackerExport($request, $company->name, $periodText),
            'offset_tracker.xlsx'
        );
    }

    public function getOffsetData(Request $request)
    {
        $user = Auth::user();
        $company = $user->preference->company;

        if (!$user->hasPermission('view offset report')) {
            abort(403, 'Unauthorized to view offset report.');
        }

        $employee = $user->employee;
        if (!$employee || $employee->company_id !== $company->id) {
            abort(403, 'Employee record not found or unauthorized.');
        }

        $query = OvertimeRequest::with(['employee.user', 'offsetRequests'])
            ->where('company_id', $company->id)
            ->where('employee_id', $employee->id)
            ->where('status', 'approved');

        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->to);
        }

        return $query->get()->map(function ($ot) {
            $used = $ot->offsetRequests->sum(fn($offset) => $offset->pivot->used_hours);
            return [
                'employee_name'    => $ot->employee->user->name,
                'date'             => $ot->date,
                'approved_hours'   => $ot->number_of_hours,
                'used_hours'       => $used,
                'remaining_hours'  => max(0, $ot->number_of_hours - $used),
                'expires_at'       => $ot->expires_at,
                'expired'          => $ot->expires_at && now()->gt($ot->expires_at),
            ];
        });
    }
}
