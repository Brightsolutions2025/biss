<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Exports\LeaveTimelineExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class LeaveTimelineReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $company = $user->preference->company;

        if (!$user->hasPermission('view leave report')) {
            abort(403, 'Unauthorized to view leave reports.');
        }

        if (!$user->employee) {
            abort(403, 'No employee profile found for this user.');
        }

        $employeeId = $user->employee->id;

        // Default to current month's first and last day
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : now()->copy()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : now()->copy()->endOfMonth();

        $query = LeaveRequest::with(['employee.user', 'employee.department'])
            ->where('company_id', $company->id)
            ->where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereDate('end_date', '>=', $startDate)
            ->whereDate('start_date', '<=', $endDate);

        $leaveRequests = $query->orderBy('start_date')->get();

        $calendarEvents = $leaveRequests->map(function ($leave) {
            return [
                'title' => $leave->reason,
                'start' => Carbon::parse($leave->start_date)->toDateString(),
                'end'   => Carbon::parse($leave->end_date)->addDay()->toDateString(),
                'allDay' => true,
                'color' => '#198754',
            ];
        });

        return view('reports.leave_timeline', [
            'leaveRequests'   => $leaveRequests,
            'calendarEvents'  => $calendarEvents,
            'startDate'       => $startDate->toDateString(),
            'endDate'         => $endDate->toDateString(),
        ]);
    }
    public function leaveTimelineExcel(Request $request)
    {
        $user = auth()->user();
        $company = $user->preference->company;

        $startDate = $request->filled('start_date')
            ? \Carbon\Carbon::parse($request->start_date)
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? \Carbon\Carbon::parse($request->end_date)
            : now()->endOfMonth();

        $employeeId = $request->input('employee_id') ?? optional($user->employee)->id;

        if (!$employeeId) {
            abort(403, 'No employee ID provided and user has no employee profile.');
        }

        $leaveRequests = LeaveRequest::with(['employee.user'])
            ->where('company_id', $company->id)
            ->where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereDate('end_date', '>=', $startDate)
            ->whereDate('start_date', '<=', $endDate)
            ->orderBy('start_date')
            ->get();

        $employeeName = optional(optional($leaveRequests->first())->employee->user)->name ?? 'Employee';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new LeaveTimelineExport($leaveRequests, $startDate, $endDate, $employeeName),
            'leave_timeline_calendar.xlsx'
        );
    }

    public function leaveTimelinePdf(Request $request)
    {
        $user = auth()->user();
        $company = $user->preference->company;

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : now()->endOfMonth();

        // Get the employee ID from the request or default to the current user's employee ID
        $employeeId = $request->input('employee_id') ?? optional($user->employee)->id;

        if (!$employeeId) {
            abort(403, 'No employee ID provided and user has no employee profile.');
        }

        $leaveRequests = LeaveRequest::with(['employee.user'])
            ->where('company_id', $company->id)
            ->where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereDate('end_date', '>=', $startDate)
            ->whereDate('start_date', '<=', $endDate)
            ->orderBy('start_date')
            ->get();

        $pdf = Pdf::loadView('reports.leave_timeline_pdf', [
            'leaveRequests' => $leaveRequests,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ])->setPaper('A4', 'portrait');

        return $pdf->download('leave_timeline_calendar.pdf');
    }

}
