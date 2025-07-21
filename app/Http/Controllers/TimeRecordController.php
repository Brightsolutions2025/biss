<?php

namespace App\Http\Controllers;

use App\Exports\TimeRecordExport;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OffsetRequest;
use App\Models\OutbaseRequest;
use App\Models\OvertimeRequest;
use App\Models\PayrollPeriod;
use App\Models\TimeLog;
use App\Models\TimeRecord;
use App\Notifications\TimeRecordStatusChanged;
use App\Notifications\TimeRecordSubmitted;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class TimeRecordController extends Controller
{
    /**
     * Display a listing of time records for the active company.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user->hasPermission('time_record.browse')) {
            abort(403, 'Unauthorized to browse time records.');
        }

        $companyId = $user->preference->company_id;

        $query = TimeRecord::with(['employee.user', 'payrollPeriod'])
            ->where('company_id', $companyId);

        if (!$user->hasPermission('time_record.browse_all')) {
            $employeeId = $user->employee?->id;

            if (!$employeeId) {
                abort(403, 'No employee record linked to this user.');
            }

            // Get subordinate employee IDs where current user is the approver
            $subordinateIds = Employee::where('approver_id', $employeeId)
                ->pluck('id')
                ->toArray();

            // Restrict to own or subordinates' records
            $query->where(function ($q) use ($employeeId, $subordinateIds) {
                $q->where('employee_id', $employeeId)
                ->orWhereIn('employee_id', $subordinateIds);
            });
        }

        // Filter by employee_id
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payroll_period_id
        if ($request->filled('payroll_period_id')) {
            $query->where('payroll_period_id', $request->payroll_period_id);
        }

        $timeRecords = $query->orderByDesc('created_at')->paginate(20)->appends($request->query());

        $employeeList = Employee::where('company_id', $companyId)
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get();

        $payrollPeriods = PayrollPeriod::where('company_id', $companyId)
            ->orderByDesc('start_date')
            ->get();

        return view('time_records.index', compact('timeRecords', 'employeeList', 'payrollPeriods'));
    }


    /**
     * Show the form for creating a new time record.
     */
    public function create()
    {
        if (!auth()->user()->hasPermission('time_record.create')) {
            abort(403, 'Unauthorized to create time records.');
        }

        $employee = auth()->user()->employee;

        if (!$employee) {
            abort(403, 'You are not authorized to access this page.');
        }

        $companyId = auth()->user()->preference->company_id;

        $employees      = Employee::where('company_id', $companyId)->get();
        $payrollPeriods = PayrollPeriod::where('company_id', $companyId)->get();

        return view('time_records.create', compact('employee', 'payrollPeriods'));
    }

    public function getTimeLogs($employeeId, $startDate, $endDate)
    {
        $logs = TimeLog::where('employee_name', auth()->user()->name)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('attendance_time')
            ->get()
            ->groupBy('date')
            ->mapWithKeys(function ($logsForDay, $date) {
                return [
                    $date => [
                        'Clock In'  => optional($logsForDay->first())->attendance_time,
                        'Clock Out' => optional($logsForDay->count() > 1 ? $logsForDay->last() : null)->attendance_time,
                    ]
                ];
            });

        return response()->json($logs->toArray()); // force conversion to array
    }

    /**
     * Store a newly created time record in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('time_record.create')) {
            abort(403, 'Unauthorized to create time records.');
        }

        $validated = $request->validate([
            'employee_id'                                 => 'required|exists:employees,id',
            'payroll_period_id'                           => 'required|exists:payroll_periods,id',
            'time_record_lines'                           => 'required|array|min:1',
            'time_record_lines.*.date'                    => 'required|date',
            'time_record_lines.*.clock_in'                => 'nullable|date_format:H:i',
            'time_record_lines.*.clock_out'               => 'nullable|date_format:H:i',
            'time_record_lines.*.late_minutes'            => 'nullable|numeric|min:0',
            'time_record_lines.*.undertime_minutes'       => 'nullable|numeric|min:0',
            'time_record_lines.*.overtime_time_start'     => 'nullable|date_format:H:i',
            'time_record_lines.*.overtime_time_end'       => 'nullable|date_format:H:i',
            'time_record_lines.*.overtime_hours'          => 'nullable|numeric|min:0',
            'time_record_lines.*.offset_time_start'       => 'nullable|date_format:H:i',
            'time_record_lines.*.offset_time_end'         => 'nullable|date_format:H:i',
            'time_record_lines.*.offset_hours'            => 'nullable|numeric|min:0',
            'time_record_lines.*.outbase_time_start'      => 'nullable|date_format:H:i',
            'time_record_lines.*.outbase_time_end'        => 'nullable|date_format:H:i',
            'time_record_lines.*.leave_days'              => 'nullable|numeric|min:0',
            'time_record_lines.*.remaining_leave_credits' => 'nullable|numeric|min:0',
            'time_record_lines.*.leave_with_pay'          => 'nullable|boolean',
            'time_record_lines.*.remarks'                 => 'nullable|string|max:255',
            'files'                                       => 'array|max:5',
            'files.*'                                     => 'file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx,xlsx',
        ]);

        $companyId = auth()->user()->preference->company_id;

        DB::beginTransaction();

        try {
            $timeRecord = TimeRecord::create([
                'company_id'        => $companyId,
                'employee_id'       => $validated['employee_id'],
                'payroll_period_id' => $validated['payroll_period_id'],
            ]);

            foreach ($validated['time_record_lines'] as $line) {
                $timeRecord->lines()->create([
                    'company_id'              => $companyId,
                    'date'                    => $line['date'],
                    'clock_in'                => $line['clock_in']                ?? null,
                    'clock_out'               => $line['clock_out']               ?? null,
                    'late_minutes'            => $line['late_minutes']            ?? 0,
                    'undertime_minutes'       => $line['undertime_minutes']       ?? 0,
                    'overtime_time_start'     => $line['overtime_time_start']     ?? null,
                    'overtime_time_end'       => $line['overtime_time_end']       ?? null,
                    'overtime_hours'          => $line['overtime_hours']          ?? 0,
                    'offset_time_start'       => $line['offset_time_start']       ?? null,
                    'offset_time_end'         => $line['offset_time_end']         ?? null,
                    'offset_hours'            => $line['offset_hours']            ?? 0,
                    'outbase_time_start'      => $line['outbase_time_start']      ?? null,
                    'outbase_time_end'        => $line['outbase_time_end']        ?? null,
                    'leave_days'              => $line['leave_days']              ?? 0,
                    'remaining_leave_credits' => $line['remaining_leave_credits'] ?? null,
                    'leave_with_pay'          => $line['leave_with_pay']          ?? false,
                    'remarks'                 => $line['remarks']                 ?? null,
                ]);
            }

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $uploadedFile) {
                    $path = $uploadedFile->store('uploads/time_record_files');

                    $timeRecord->files()->create([
                        'file_path' => $path,
                        'file_name' => $uploadedFile->getClientOriginalName(),
                    ]);
                }
            }

            $approver = $timeRecord->employee->approver;
            if ($approver && $approver->email) {
                $approver->notify(new TimeRecordSubmitted($timeRecord));
            }

            DB::commit();

            Log::info('Time record and lines created', [
                'time_record_id' => $timeRecord->id,
                'user_id'        => auth()->id()
            ]);

            return redirect()->route('time_records.index')->with('success', 'Time record created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create time record with lines', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors($e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified time record.
     */
    public function show(TimeRecord $timeRecord)
    {
        $user = auth()->user();

        $this->authorizeCompany($timeRecord->company_id);

        if (!auth()->user()->hasPermission('time_record.read')) {
            abort(403, 'Unauthorized to view time records.');
        }

        $employeeId = $user->employee?->id;

        if (!$user->hasPermission('time_record.browse_all')) {
            $isOwner = $timeRecord->employee_id === $employeeId;

            // Check if current user is approver of the employee linked to this time record
            $isApprover = $timeRecord->employee->approver_id === $employeeId;

            if (!$isOwner && !$isApprover) {
                abort(403, 'You are not allowed to view this time record.');
            }
        }

        $timeRecord->load(['employee', 'payrollPeriod', 'lines']);

        $employeeId = $timeRecord->employee_id;

        $dates     = $timeRecord->lines->pluck('date');
        $startDate = $dates->min();
        $endDate   = $dates->max();

        $overtimeRequests = OvertimeRequest::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $leaveRequests = LeaveRequest::where('employee_id', $employeeId)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate]);
            })
            ->get();

        $outbaseRequests = OutbaseRequest::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $offsetRequests = OffsetRequest::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        return view('time_records.show', compact(
            'timeRecord',
            'overtimeRequests',
            'leaveRequests',
            'outbaseRequests',
            'offsetRequests'
        ));
    }

    /**
     * Show the form for editing the specified time record.
     */
    public function edit(TimeRecord $timeRecord)
    {
        $user = auth()->user();

        $this->authorizeCompany($timeRecord->company_id);

        if (!auth()->user()->hasPermission('time_record.update')) {
            abort(403, 'Unauthorized to edit time records.');
        }

        if (! $this->canEditTimeRecord($timeRecord)) {
            abort(403, 'You are not allowed to edit this time record.');
        }

        // If user doesn't have 'browse_all', enforce ownership or approver rights
        if (!$user->hasPermission('time_record.browse_all')) {
            $employeeId = $user->employee?->id;
            $isOwner    = $employeeId && $timeRecord->employee_id === $employeeId;
            $isApprover = $user->id                               === $timeRecord->employee->approver_id;

            if (!$isOwner && !$isApprover) {
                abort(403, 'You are not allowed to edit this time record.');
            }
        }

        $companyId      = auth()->user()->preference->company_id;
        $employees      = Employee::where('company_id', $companyId)->get();
        $payrollPeriods = PayrollPeriod::where('company_id', $companyId)->get();

        // Load lines with the time record
        $timeRecord->load('lines');

        return view('time_records.edit', compact('timeRecord', 'employees', 'payrollPeriods'));
    }

    protected function canEditTimeRecord(TimeRecord $timeRecord): bool
    {
        $isApprover = auth()->id()                  === $timeRecord->employee->approver_id;
        $isEmployee = auth()->user()->employee?->id === $timeRecord->employee_id;

        if ($isApprover) {
            return true;
        }

        if ($isEmployee && !in_array($timeRecord->status, ['approved', 'rejected'])) {
            return true;
        }

        return false;
    }

    /**
     * Update the specified time record in storage.
     */
    public function update(Request $request, TimeRecord $timeRecord)
    {
        $this->authorizeCompany($timeRecord->company_id);

        if (!auth()->user()->hasPermission('time_record.update')) {
            abort(403, 'Unauthorized to edit time records.');
        }

        if (! $this->canEditTimeRecord($timeRecord)) {
            abort(403, 'You are not allowed to edit this time record.');
        }

        $validated = $request->validate([
            'time_record_lines'               => 'required|array|min:1',
            'time_record_lines.*.id'          => 'required|exists:time_record_lines,id',
            'time_record_lines.*.remarks'     => 'nullable|string|max:255',
            'files'                           => 'array|max:5',
            'files.*'                         => 'file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx,xlsx',
        ]);

        DB::beginTransaction();

        try {
            unset($validated['files']);

            foreach ($validated['time_record_lines'] as $lineData) {
                $line = $timeRecord->lines()->where('id', $lineData['id'])->first();

                if ($line) {
                    $line->update([
                        'remarks' => $lineData['remarks'] ?? null,
                    ]);
                }
            }

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $uploadedFile) {
                    $path = $uploadedFile->store('uploads/time_record_files');

                    $timeRecord->files()->create([
                        'file_path' => $path,
                        'file_name' => $uploadedFile->getClientOriginalName(),
                    ]);
                }
            }

            $approver = $timeRecord->employee->approver;
            if ($approver && $approver->email) {
                $approver->notify(new TimeRecordSubmitted($timeRecord));
            }

            DB::commit();

            Log::info('Time record and lines updated', [
                'time_record_id' => $timeRecord->id,
                'user_id'        => auth()->id(),
            ]);

            return redirect()->route('time_records.index')->with('success', 'Time record updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update time record with lines', [
                'error'          => $e->getMessage(),
                'time_record_id' => $timeRecord->id,
                'user_id'        => auth()->id(),
            ]);

            return back()->withErrors($e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified time record from storage.
     */
    public function destroy(TimeRecord $timeRecord)
    {
        $user = auth()->user();

        $this->authorizeCompany($timeRecord->company_id);

        if (!auth()->user()->hasPermission('time_record.delete')) {
            abort(403, 'Unauthorized to delete time records.');
        }

        // Ownership check if user lacks global permission
        if (!$user->hasPermission('time_record.browse_all')) {
            $employeeId = $user->employee?->id;

            if (!$employeeId || $timeRecord->employee_id !== $employeeId) {
                abort(403, 'You are not allowed to delete this time record.');
            }

            // Prevent deletion if the time record is approved or rejected
            if (in_array($timeRecord->status, ['approved', 'rejected'])) {
                abort(403, 'You cannot delete a time record that has already been approved or rejected.');
            }
        }

        DB::beginTransaction();

        try {
            foreach ($timeRecord->files as $file) {
                if (\Storage::exists($file->file_path)) {
                    \Storage::delete($file->file_path);
                }
                $file->delete();
            }

            $timeRecord->delete();

            DB::commit();

            Log::info('Time record deleted', ['time_record_id' => $timeRecord->id, 'user_id' => auth()->id()]);

            return redirect()->route('time_records.index')->with('success', 'Time record deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete time record', [
                'error'          => $e->getMessage(),
                'time_record_id' => $timeRecord->id,
                'user_id'        => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while deleting the time record.');
        }
    }

    /**
     * Private helper to ensure user belongs to the company.
     */
    protected function authorizeCompany($companyId)
    {
        $preferredCompanyId = auth()->user()->preference->company_id;

        if ($companyId != $preferredCompanyId) {
            abort(403, 'Unauthorized: This action is not allowed for your selected company.');
        }
    }
    public function approve(Request $request, TimeRecord $timeRecord)
    {
        $this->authorizeCompany($timeRecord->company_id);

        $approverId = $timeRecord->employee->approver_id;

        if (auth()->id() !== $approverId) {
            abort(403, 'Unauthorized');
        }

        DB::beginTransaction();

        try {
            $timeRecord->status           = 'approved';
            $timeRecord->approver_id      = auth()->id();
            $timeRecord->rejection_reason = null;
            $timeRecord->approval_date    = Carbon::now('Asia/Manila');
            $timeRecord->save();

            $employeeUser = $timeRecord->employee->user;
            if ($employeeUser && $employeeUser->email) {
                $employeeUser->notify(new TimeRecordStatusChanged($timeRecord, 'approved'));
            }

            DB::commit();

            Log::info('Time record approved', [
                'time_record_id' => $timeRecord->id,
                'approver_id'    => auth()->id(),
            ]);

            return redirect()->route('time_records.show', $timeRecord->id)
                            ->with('success', 'Time record approved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to approve time record', [
                'error'          => $e->getMessage(),
                'time_record_id' => $timeRecord->id,
                'approver_id'    => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while approving the time record.');
        }
    }

    public function reject(Request $request, TimeRecord $timeRecord)
    {
        $this->authorizeCompany($timeRecord->company_id);

        $approverId = $timeRecord->employee->approver_id;

        if (auth()->id() !== $approverId) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $timeRecord->status           = 'rejected';
            $timeRecord->approver_id      = auth()->id();
            $timeRecord->rejection_reason = $request->input('reason');
            $timeRecord->save();

            $employeeUser = $timeRecord->employee->user;
            if ($employeeUser && $employeeUser->email) {
                $employeeUser->notify(new TimeRecordStatusChanged($timeRecord, 'rejected'));
            }

            DB::commit();

            Log::info('Time record rejected', [
                'time_record_id' => $timeRecord->id,
                'approver_id'    => auth()->id(),
            ]);

            return redirect()->route('time_records.show', $timeRecord->id)
                            ->with('success', 'Time record rejected successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to reject time record', [
                'error'          => $e->getMessage(),
                'time_record_id' => $timeRecord->id,
                'approver_id'    => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while rejecting the time record.');
        }
    }
    public function exportPdf($id)
    {
        $timeRecord = TimeRecord::with(['employee.user', 'payrollPeriod', 'lines'])->findOrFail($id);

        $pdf = Pdf::loadView('exports.time_record_pdf', compact('timeRecord'))
                ->setPaper('a4', 'portrait'); // <-- Set landscape orientation

        return $pdf->download('time_record_' . $timeRecord->id . '.pdf');
    }
    public function exportExcel($id)
    {
        $timeRecord = TimeRecord::with(['employee.user', 'payrollPeriod', 'lines'])->findOrFail($id);
        return Excel::download(new TimeRecordExport($timeRecord), 'time_record_' . $timeRecord->id . '.xlsx');
    }
}
