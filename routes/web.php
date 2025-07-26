<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeShiftController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FiledOvertimeReportController;
use App\Http\Controllers\LateUndertimeReportController;
use App\Http\Controllers\LeaveBalanceController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveStatusOverviewController;
use App\Http\Controllers\LeaveSummaryReportController;
use App\Http\Controllers\LeaveTimelineReportController;
use App\Http\Controllers\OffsetRequestController;
use App\Http\Controllers\OffsetSummaryReportController;
use App\Http\Controllers\OffsetTrackerController;
use App\Http\Controllers\OutbaseReportController;
use App\Http\Controllers\OutbaseRequestController;
use App\Http\Controllers\OutbaseSummaryController;
use App\Http\Controllers\OvertimeRequestController;
use App\Http\Controllers\PayrollPeriodController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TicketTypeController;
use App\Http\Controllers\TimeLogController;
use App\Http\Controllers\TimeRecordController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPreferenceController;
use App\Http\Controllers\ClientContactController;
use App\Http\Controllers\AuditLogController;
use App\Http\Middleware\EnsureUserHasCompany;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified', EnsureUserHasCompany::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('companies', CompanyController::class);

    // User Preference routes
    Route::get('/preferences', [UserPreferenceController::class, 'index'])->name('preferences.index');
    Route::get('/preferences/edit', [UserPreferenceController::class, 'edit'])->name('preferences.edit');
    Route::put('/preferences', [UserPreferenceController::class, 'update'])->name('preferences.update');
    Route::post('/preferences/switch-company', [UserPreferenceController::class, 'switchCompany'])->name('preferences.switchCompany');
});

Route::middleware('auth', EnsureUserHasCompany::class)->group(function () {
    Route::resource('client_contacts', ClientContactController::class);
    Route::resource('clients', ClientController::class);

    Route::delete('/files/{file}', [FileController::class, 'destroy'])->name('files.destroy');
    Route::get('/files/{file}', [FileController::class, 'download'])->name('files.download');
    // 1. Employee DTR Status by Department & Team
    Route::get('/reports/dtr_status_by_team', [ReportController::class, 'dtrStatusByTeam'])
        ->name('reports.dtr_status_by_team');
    Route::get('/reports/dtr-status-by-team/pdf', [ReportController::class, 'downloadPdf'])
        ->name('reports.dtr-status-by-team.pdf');
    Route::get('/reports/dtr-status-by-team/excel', [ReportController::class, 'downloadExcel'])
        ->name('reports.dtr-status-by-team.excel');

    // 2. Leave Utilization Summary
    Route::get('/reports/leave-utilization', [ReportController::class, 'leaveUtilization'])->name('reports.leave_utilization');
    Route::get('/reports/leave-utilization/pdf', [ReportController::class, 'leaveUtilizationPdf'])->name('reports.leave_utilization.pdf');
    Route::get('/reports/leave-utilization/excel', [ReportController::class, 'leaveUtilizationExcel'])->name('reports.leave_utilization.excel');

    // 3. Overtime vs Offset Report
    Route::get('/reports/overtime-offset-comparison', [ReportController::class, 'overtimeOffsetComparison'])->name('reports.overtime_offset_comparison');
    Route::get('/reports/overtime-offset-comparison/pdf', [ReportController::class, 'overtimeOffsetComparisonPdf'])->name('reports.overtime_offset_comparison.pdf');
    Route::get('/reports/overtime-offset-comparison/excel', [ReportController::class, 'overtimeOffsetComparisonExcel'])->name('reports.overtime_offset_comparison.excel');

    // 4. Attendance Summary by Date Range
    Route::get('/reports/attendance-summary', [ReportController::class, 'attendanceSummary'])->name('reports.attendance_summary');
    Route::get('/reports/attendance-summary/pdf', [ReportController::class, 'attendanceSummaryPdf'])->name('reports.attendance_summary.pdf');
    Route::get('/reports/attendance-summary/excel', [ReportController::class, 'attendanceSummaryExcel'])->name('reports.attendance_summary.excel');

    // 5. Employee Shift Assignments
    Route::get('/reports/shift-assignments', [ReportController::class, 'shiftAssignments'])->name('reports.shift_assignments');
    Route::get('/reports/shift-assignments/pdf', [ReportController::class, 'shiftAssignmentsPdf'])->name('reports.shift_assignments.pdf');
    Route::get('/reports/shift-assignments/excel', [ReportController::class, 'shiftAssignmentsExcel'])->name('reports.shift_assignments.excel');

    // 6. Time Log Exceptions
    Route::get('/reports/time-log-exceptions', [ReportController::class, 'timeLogExceptions'])->name('reports.time_log_exceptions');
    Route::get('/reports/time-log-exceptions/pdf', [ReportController::class, 'timeLogExceptionsPdf'])->name('reports.time_log_exceptions.pdf');
    Route::get('/reports/time-log-exceptions/excel', [ReportController::class, 'timeLogExceptionsExcel'])->name('reports.time_log_exceptions.excel');

    // 7. Late and Undertime Report
    Route::get('/reports/late-undertime', [LateUndertimeReportController::class, 'index'])->name('reports.late_undertime');
    Route::get('/reports/late-undertime/pdf', [LateUndertimeReportController::class, 'exportPdf'])->name('reports.late_undertime.pdf');
    Route::get('/reports/late-undertime/excel', [LateUndertimeReportController::class, 'exportExcel'])->name('reports.late_undertime.excel');

    // 8. Leave Requests by Status
    Route::get('/reports/leave-status-overview', [LeaveStatusOverviewController::class, 'index'])->name('reports.leave_status_overview');
    Route::get('/reports/leave-status-overview/pdf', [LeaveStatusOverviewController::class, 'exportPdf'])->name('reports.leave_status_overview.pdf');
    Route::get('/reports/leave-status-overview/excel', [LeaveStatusOverviewController::class, 'exportExcel'])->name('reports.leave_status_overview.excel');

    // 9. Outbase Request Summary
    Route::get('/reports/outbase-summary', [OutbaseSummaryController::class, 'index'])->name('reports.outbase_summary');
    Route::get('/reports/outbase-summary/pdf', [OutbaseSummaryController::class, 'exportPdf'])->name('reports.outbase_summary.pdf');
    Route::get('/reports/outbase-summary/excel', [OutbaseSummaryController::class, 'exportExcel'])->name('reports.outbase_summary.excel');

    // 10. Offset Usage and Expiry Tracker
    Route::get('/reports/offset-tracker', [OffsetTrackerController::class, 'index'])->name('reports.offset_tracker');
    Route::get('/reports/offset-tracker/pdf', [OffsetTrackerController::class, 'offsetTrackerPdf'])->name('reports.offset_tracker.pdf');
    Route::get('/reports/offset-tracker/excel', [OffsetTrackerController::class, 'offsetTrackerExcel'])->name('reports.offset_tracker.excel');

    // 11. Leave Summary Report
    Route::get('/reports/leave-summary', [LeaveSummaryReportController::class, 'index'])->name('reports.leave_summary');
    Route::get('/reports/leave-summary/pdf', [LeaveSummaryReportController::class, 'leaveSummaryPdf'])->name('reports.leave_summary.pdf');
    Route::get('/reports/leave-summary/excel', [LeaveSummaryReportController::class, 'leaveSummaryExcel'])->name('reports.leave_summary.excel');

    // 12. Filed Overtime Report
    Route::get('/reports/overtime-history', [FiledOvertimeReportController::class, 'index'])->name('reports.overtime_history');
    Route::get('/reports/overtime-history/pdf', [FiledOvertimeReportController::class, 'overtimeHistoryPdf'])->name('reports.overtime_history.pdf');
    Route::get('/reports/overtime-history/excel', [FiledOvertimeReportController::class, 'overtimeHistoryExcel'])->name('reports.overtime_history.excel');

    // 13. Approved Leaves Timeline
    Route::get('/reports/leave-timeline', [LeaveTimelineReportController::class, 'index'])->name('reports.leave_timeline');
    Route::get('/reports/leave-timeline/pdf', [LeaveTimelineReportController::class, 'leaveTimelinePdf'])->name('reports.leave_timeline.pdf');
    Route::get('/reports/leave-timeline/excel', [LeaveTimelineReportController::class, 'leaveTimelineExcel'])->name('reports.leave_timeline.excel');

    // 14. Field Work (Outbase) Report
    Route::get('/reports/outbase-history', [OutbaseReportController::class, 'index'])->name('reports.outbase_history');
    Route::get('/reports/outbase-history/pdf', [OutbaseReportController::class, 'outbaseHistoryPdf'])->name('reports.outbase_history.pdf');
    Route::get('/reports/outbase-history/excel', [OutbaseReportController::class, 'outbaseHistoryExcel'])->name('reports.outbase_history.excel');

    // 15. Offset Request Usage Summary
    Route::get('/reports/offset-summary', [OffsetSummaryReportController::class, 'index'])->name('reports.offset_summary');
    Route::get('/reports/offset-summary/pdf', [OffsetSummaryReportController::class, 'exportPdf'])->name('reports.offset_summary.pdf');
    Route::get('/reports/offset-summary/excel', [OffsetSummaryReportController::class, 'exportExcel'])->name('reports.offset_summary.excel');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/time_records/{id}/export/pdf', [TimeRecordController::class, 'exportPdf'])->name('time_records.export.pdf');
    Route::get('/time_records/{id}/export/excel', [TimeRecordController::class, 'exportExcel'])->name('time_records.export.excel');
    Route::patch('time_records/{time_record}/approve', [TimeRecordController::class, 'approve'])
        ->name('time_records.approve');
    Route::patch('time_records/{time_record}/reject', [TimeRecordController::class, 'reject'])
        ->name('time_records.reject');
    Route::get('/leave_requests/{employee}/{start}/{end}', [LeaveRequestController::class, 'fetchApprovedByDate']);
    Route::get('/outbase_requests/{employee}/{start}/{end}', [OutbaseRequestController::class, 'fetchApprovedByDate']);
    Route::get('/offset_requests/{employee}/{start}/{end}', [OffsetRequestController::class, 'fetchApprovedByDate']);
    Route::get('/overtime_requests/{employee}/{start}/{end}', [OvertimeRequestController::class, 'fetchApprovedByDate']);
    Route::get('/time_records/{employeeId}/{startDate}/{endDate}', [TimeRecordController::class, 'getTimeLogs'])->name('time_logs.fetch');
    Route::resource('time_records', TimeRecordController::class);
    Route::patch('/offset_requests/{offset_request}/approve', [OffsetRequestController::class, 'approve'])
        ->name('offset_requests.approve');
    Route::patch('/offset_requests/{offset_request}/reject', [OffsetRequestController::class, 'reject'])
        ->name('offset_requests.reject');
    Route::resource('offset_requests', OffsetRequestController::class);
    Route::patch('/outbase_requests/{outbase_request}/approve', [OutbaseRequestController::class, 'approve'])
        ->name('outbase_requests.approve');
    Route::patch('/outbase_requests/{outbase_request}/reject', [OutbaseRequestController::class, 'reject'])
        ->name('outbase_requests.reject');
    Route::resource('outbase_requests', OutbaseRequestController::class);
    Route::patch('/leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])
        ->name('leave_requests.approve');
    Route::patch('/leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])
        ->name('leave_requests.reject');
    Route::resource('leave_requests', LeaveRequestController::class);
    Route::resource('leave_balances', LeaveBalanceController::class);
    Route::patch('/overtime_requests/{overtimeRequest}/approve', [OvertimeRequestController::class, 'approve'])
        ->name('overtime_requests.approve');
    Route::patch('/overtime_requests/{overtimeRequest}/reject', [OvertimeRequestController::class, 'reject'])
        ->name('overtime_requests.reject');
    Route::resource('overtime_requests', OvertimeRequestController::class);
    Route::delete('/time_logs/batch-delete', [TimeLogController::class, 'batchDelete'])->name('time_logs.batch-delete');
    Route::post('/time_logs/import', [TimeLogController::class, 'import'])->name('time_logs.import');
    Route::resource('time_logs', TimeLogController::class);
    Route::resource('payroll_periods', PayrollPeriodController::class);
    Route::resource('employee_shifts', EmployeeShiftController::class);
    Route::resource('shifts', ShiftController::class);
    Route::resource('employees', EmployeeController::class);
    Route::resource('teams', TeamController::class);
    Route::resource('departments', DepartmentController::class);
});

Route::middleware('auth', EnsureUserIsAdmin::class, EnsureUserHasCompany::class)->group(function () {
    Route::resource('audit_logs', AuditLogController::class);
    Route::resource('ticket_types', TicketTypeController::class);
    Route::resource('permissions', PermissionController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('company_users', CompanyUserController::class);
    Route::resource('users', UserController::class);
});

require __DIR__ . '/auth.php';
