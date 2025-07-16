<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PayrollPeriod;
use App\Models\TimeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TimeLogController extends Controller
{
    /**
     * Display a listing of the time logs for the active company.
     */
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('time_log.browse')) {
            abort(403, 'Unauthorized to browse time logs.');
        }
        
        $company = auth()->user()->preference->company;

        $query = TimeLog::where('company_id', $company->id);

        // Filter by employee_name in time_logs table
        if ($request->filled('employee_name')) {
            $query->where('employee_name', 'like', '%' . $request->employee_name . '%');
        }

        if ($request->filled('department_name')) {
            $query->where('department_name', $request->department_name);
        }

        if ($request->filled('payroll_period_id')) {
            $query->where('payroll_period_id', $request->payroll_period_id);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('date', [$request->date_from, $request->date_to]);
        }

        // Sorting
        switch ($request->get('sort')) {
            case 'date_asc':
                $query->orderBy('date', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('employee_name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('employee_name', 'desc');
                break;
            default:
                $query->orderBy('date', 'desc'); // Default: newest first
        }

        $timeLogs = $query->paginate(20)->appends($request->query());

        // Unique department names for the filter dropdown
        $departments = TimeLog::where('company_id', $company->id)
            ->select('department_name')
            ->distinct()
            ->pluck('department_name')
            ->filter()
            ->sort()
            ->values();

        $payrollPeriods = PayrollPeriod::where('company_id', $company->id)
            ->orderByDesc('start_date')
            ->get();

        $employeeNames = TimeLog::where('company_id', $company->id)
            ->select('employee_name')
            ->distinct()
            ->pluck('employee_name')
            ->filter()
            ->sort()
            ->values();

        return view('time_logs.index', compact('timeLogs', 'departments', 'payrollPeriods', 'employeeNames'));
    }

    /**
     * Show the form for creating a new time log.
     */
    public function create()
    {
        if (!auth()->user()->hasPermission('time_log.create')) {
            abort(403, 'Unauthorized to create time logs.');
        }

        $companyId = auth()->user()->preference->company_id;

        if (!$companyId) {
            return redirect()->route('companies.index')->withErrors('Please select a company first.');
        }

        // Get payroll periods for the active company for selection
        $payrollPeriods = PayrollPeriod::where('company_id', $companyId)->get();

        return view('time_logs.create', compact('payrollPeriods'));
    }

    /**
     * Store a newly created time log in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('time_log.create')) {
            abort(403, 'Unauthorized to create time logs.');
        }
        
        $companyId = auth()->user()->preference->company_id;

        if (!$companyId) {
            return redirect()->route('companies.index')->withErrors('Please select a company first.');
        }

        $validated = $request->validate([
            'payroll_period_id'  => 'required|exists:payroll_periods,id',
            'employee_name'      => 'required|string|max:255',
            'department_name'    => 'required|string|max:255',
            'employee_id'        => 'required|string|max:255',
            'employee_type'      => 'required|string|max:255',
            'attendance_group'   => 'required|string|max:255',
            'date'               => 'required|date',
            'weekday'            => 'required|string|max:255',
            'shift'              => 'required|string|max:255',
            'attendance_time'    => 'required|date_format:Y-m-d\TH:i',
            'about_the_record'   => 'required|string|max:255',
            'attendance_result'  => 'required|string|max:255',
            'attendance_address' => 'required|string|max:255',
            'note'               => 'nullable|string|max:255',
            'attendance_method'  => 'required|string|max:255',
            'attendance_photo'   => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $validated['company_id'] = $companyId;

            TimeLog::create($validated);

            DB::commit();

            Log::info('TimeLog created', ['company_id' => $companyId, 'user_id' => auth()->id()]);

            return redirect()->route('time_logs.index')->with('success', 'Time log created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create time log', [
                'error'      => $e->getMessage(),
                'company_id' => $companyId,
                'user_id'    => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while creating the time log.');
        }
    }

    /**
     * Display the specified time log.
     */
    public function show(TimeLog $timeLog)
    {
        $this->authorizeCompany($timeLog->company);

        if (!auth()->user()->hasPermission('time_log.read')) {
            abort(403, 'Unauthorized to view time logs.');
        }

        return view('time_logs.show', compact('timeLog'));
    }

    /**
     * Show the form for editing the specified time log.
     */
    public function edit(TimeLog $timeLog)
    {
        $this->authorizeCompany($timeLog->company);

        if (!auth()->user()->hasPermission('time_log.update')) {
            abort(403, 'Unauthorized to edit time logs.');
        }

        $payrollPeriods = PayrollPeriod::where('company_id', $timeLog->company_id)->get();

        return view('time_logs.edit', compact('timeLog', 'payrollPeriods'));
    }

    /**
     * Update the specified time log in storage.
     */
    public function update(Request $request, TimeLog $timeLog)
    {
        $this->authorizeCompany($timeLog->company);

        if (!auth()->user()->hasPermission('time_log.update')) {
            abort(403, 'Unauthorized to update time logs.');
        }
        
        $companyId = auth()->user()->preference->company_id;

        try {
            $attendanceTime = null;
            $attendanceTimeInput = $request->input('attendance_time');

            if (!empty($attendanceTimeInput) && is_string($attendanceTimeInput)) {
                try {
                    // Try strict format first: 2025-06-11T08:30
                    $attendanceTime = Carbon::createFromFormat('Y-m-d\TH:i', $attendanceTimeInput);
                } catch (\Exception $e) {
                    try {
                        // Fallback parsing
                        $attendanceTime = Carbon::parse($attendanceTimeInput);
                    } catch (\Exception $e2) {
                        Log::warning('Invalid attendance_time format during update', [
                            'value'   => $attendanceTimeInput,
                            'request' => $request->all(),
                            'message' => $e2->getMessage(),
                        ]);
                        $attendanceTime = null;
                    }
                }
            }

            $validated = $request->validate([
                'payroll_period_id'  => 'required|exists:payroll_periods,id',
                'employee_name'      => 'required|string|max:255',
                'department_name'    => 'required|string|max:255',
                'employee_id'        => 'required|string|max:255',
                'employee_type'      => 'required|string|max:255',
                'attendance_group'   => 'required|string|max:255',
                'date'               => 'required|date',
                'weekday'            => 'required|string|max:255',
                'shift'              => 'required|string|max:255',
                'attendance_time'    => 'required|date',
                'about_the_record'   => 'required|string|max:255',
                'attendance_result'  => 'required|string|max:255',
                'attendance_address' => 'required|string|max:255',
                'note'               => 'nullable|string|max:255',
                'attendance_method'  => 'required|string|max:255',
                'attendance_photo'   => 'required|string|max:255',
            ], [
                'payroll_period_id.required' => 'The payroll period is required.',
                'payroll_period_id.exists'   => 'The selected payroll period is invalid.',

                'employee_name.required'    => 'The employee name is required.',
                'department_name.required'  => 'The department name is required.',
                'employee_id.required'      => 'The employee ID is required.',
                'employee_type.required'    => 'The employee type is required.',
                'attendance_group.required' => 'The attendance group is required.',

                'date.required' => 'The date is required.',
                'date.date'     => 'The date must be a valid date.',

                'weekday.required' => 'The weekday is required.',
                'shift.required'   => 'The shift is required.',

                'attendance_time.required'    => 'The attendance time is required.',
                'attendance_time.date_format' => 'The attendance time must be in the format YYYY-MM-DDTHH:MM.',

                'about_the_record.required'   => 'The "about the record" field is required.',
                'attendance_result.required'  => 'The attendance result is required.',
                'attendance_address.required' => 'The attendance address is required.',

                'note.string' => 'The note must be a string.',
                'note.max'    => 'The note may not be greater than 255 characters.',

                'attendance_method.required' => 'The attendance method is required.',
                'attendance_photo.required'  => 'The attendance photo is required.',
            ]);

            DB::beginTransaction();

            $timeLog->fill([
                'company_id'         => $companyId,
                'payroll_period_id'  => $validated['payroll_period_id'],
                'employee_name'      => $validated['employee_name'],
                'department_name'    => $validated['department_name'],
                'employee_id'        => $validated['employee_id'],
                'employee_type'      => $validated['employee_type'],
                'attendance_group'   => $validated['attendance_group'],
                'date'               => $validated['date'],
                'weekday'            => $validated['weekday'],
                'shift'              => $validated['shift'],
                'about_the_record'   => $validated['about_the_record'],
                'attendance_result'  => $validated['attendance_result'],
                'attendance_address' => $validated['attendance_address'],
                'note'               => $validated['note'] ?? '',
                'attendance_method'  => $validated['attendance_method'] ?? '',
                'attendance_photo'   => $validated['attendance_photo'] ?? '',
            ]);

            $timeLog->attendance_time = $attendanceTime;

            $timeLog->save();

            DB::commit();

            Log::info('TimeLog updated', ['time_log_id' => $timeLog->id, 'user_id' => auth()->id()]);

            return redirect()->route('time_logs.index')->with('success', 'Time log updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update time log', [
                'error'       => $e->getMessage(),
                'time_log_id' => $timeLog->id,
                'user_id'     => auth()->id(),
            ]);

            return back()->withErrors($e->getMessage());
        }
    }

    /**
     * Remove the specified time log from storage.
     */
    public function destroy(TimeLog $timeLog)
    {
        $this->authorizeCompany($timeLog->company);

        if (!auth()->user()->hasPermission('time_log.delete')) {
            abort(403, 'Unauthorized to delete time logs.');
        }

        DB::beginTransaction();

        try {
            $timeLog->delete();

            DB::commit();

            Log::info('TimeLog deleted', ['time_log_id' => $timeLog->id, 'user_id' => auth()->id()]);

            return redirect()->route('time_logs.index')->with('success', 'Time log deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete time log', [
                'error'       => $e->getMessage(),
                'time_log_id' => $timeLog->id,
                'user_id'     => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while deleting the time log.');
        }
    }

    /**
     * Private helper to ensure user belongs to the company owning the time log.
     */
    protected function authorizeCompany(Company $company)
    {
        if (!auth()->user()->companies->contains($company->id)) {
            abort(403, 'Unauthorized');
        }
    }
    public function import(Request $request)
    {
        if (!auth()->user()->hasPermission('time_log.create')) {
            abort(403, 'Unauthorized to create time logs.');
        }

        $companyId = auth()->user()->preference->company_id;

        if (!$companyId) {
            return redirect()->route('companies.index')->withErrors('Please select a company first.');
        }

        $validated = $request->validate([
            'payroll_period_id' => 'required|exists:payroll_periods,id',
            'csv_file'          => 'required|file|mimes:csv,txt',
        ]);

        if (!$request->hasFile('csv_file') || !$request->file('csv_file')->isValid()) {
            return back()->withErrors('Invalid or missing CSV file.');
        }

        $payrollPeriod = PayrollPeriod::where('id', $validated['payroll_period_id'])
            ->where('company_id', $companyId)
            ->first();

        if (!$payrollPeriod) {
            return back()->withErrors('Unauthorized payroll period.');
        }

        // Save the uploaded CSV file to storage/app/public/time_logs
        $filePath = $request->file('csv_file')->store('time_logs', 'public');

        // Open the saved CSV file
        $file = fopen(storage_path("app/public/{$filePath}"), 'r');

        if (!$file) {
            return back()->withErrors('Failed to open the CSV file.');
        }

        // Read header row
        $header = fgetcsv($file);

        if (!$header) {
            fclose($file);
            return back()->withErrors('CSV file is empty or invalid.');
        }

        // Expected columns for validation
        $expectedColumns = [
            'employee_name',
            'department_name',
            'employee_id',
            'employee_type',
            'attendance_group',
            'date',
            'weekday',
            'shift',
            'attendance_time',
            'about_the_record',
            'attendance_result',
            'attendance_address',
            'note',
            'attendance_method',
            'attendance_photo',
        ];

        if (array_diff($expectedColumns, $header)) {
            fclose($file);
            return back()->withErrors('CSV file columns do not match expected columns.');
        }

        $validRows = [];

        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($header, $row);

            // Add company_id and payroll_period_id
            $data['company_id']        = $companyId;
            $data['payroll_period_id'] = $validated['payroll_period_id'];

            $validRows[] = $data;
        }

        fclose($file);

        DB::beginTransaction();

        try {
            foreach ($validRows as $row) {
                // You can optionally add row validation here (like in your commented-out code)
                // For brevity, skipping detailed per-row validation (but recommended)

                $attendanceTime = null;

                if (!empty($row['attendance_time']) && is_string($row['attendance_time'])) {
                    try {
                        // Try parsing expected ISO-like format: 2025-06-11T08:30
                        $attendanceTime = Carbon::createFromFormat('Y-m-d\TH:i', $row['attendance_time']);
                    } catch (\Exception $e) {
                        try {
                            // Try a more flexible format (if needed, e.g., fallback to Y-m-d H:i)
                            $attendanceTime = Carbon::parse($row['attendance_time']);
                        } catch (\Exception $e2) {
                            // Log and set to null if both parsing attempts fail
                            Log::warning('Invalid attendance_time format', [
                                'value'   => $row['attendance_time'],
                                'row'     => $row,
                                'message' => $e2->getMessage(),
                            ]);
                            $attendanceTime = null;
                        }
                    }
                }

                $timeLog = new TimeLog([
                    'company_id'         => $row['company_id'],
                    'payroll_period_id'  => $row['payroll_period_id'],
                    'employee_name'      => $row['employee_name'],
                    'department_name'    => $row['department_name'],
                    'employee_id'        => $row['employee_id'],
                    'employee_type'      => $row['employee_type'],
                    'attendance_group'   => $row['attendance_group'],
                    'date'               => $row['date'],
                    'weekday'            => $row['weekday'],
                    'shift'              => $row['shift'],
                    'attendance_time'    => $attendanceTime,
                    'about_the_record'   => $row['about_the_record'],
                    'attendance_result'  => $row['attendance_result'],
                    'attendance_address' => $row['attendance_address'],
                    'note'               => $row['note'],
                    'attendance_method'  => $row['attendance_method'],
                    'attendance_photo'   => $row['attendance_photo'],
                ]);
                $timeLog->save();
            }

            DB::commit();

            Log::info('TimeLogs imported', [
                'company_id' => $companyId,
                'user_id'    => auth()->id(),
                'count'      => count($validRows),
            ]);

            return redirect()->route('time_logs.index')->with('success', 'Time logs imported successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to import time logs', [
                'error'      => $e->getMessage(),
                'company_id' => $companyId,
                'user_id'    => auth()->id(),
            ]);

            return back()->withErrors($e->getMessage());
        }
    }
    public function batchDelete(Request $request)
    {
        if (!auth()->user()->hasPermission('time_log.delete')) {
            abort(403, 'Unauthorized to batch delete time logs.');
        }

        $companyId = auth()->user()->preference->company_id;

        $validPeriods = PayrollPeriod::whereIn('id', $request->payroll_period_ids)
            ->where('company_id', $companyId)
            ->pluck('id')
            ->toArray();

        if (count($validPeriods) !== count($request->payroll_period_ids)) {
            return back()->withErrors('One or more payroll periods do not belong to your company.');
        }

        $request->validate([
            'payroll_period_ids'   => 'required|array',
            'payroll_period_ids.*' => 'integer|exists:payroll_periods,id',
        ]);

        $payrollPeriods = PayrollPeriod::whereIn('id', $request->payroll_period_ids)->get();

        // Get distinct company IDs from the periods
        $companyIds = $payrollPeriods->pluck('company_id')->unique();

        if ($companyIds->count() !== 1) {
            return back()->withErrors('All selected payroll periods must belong to the same company.');
        }

        // Authorize the single company
        $company = Company::findOrFail($companyIds->first());
        $this->authorizeCompany($company);

        DB::beginTransaction();

        try {
            $deletedCount = TimeLog::whereIn('payroll_period_id', $request->payroll_period_ids)->delete();

            DB::commit();

            Log::info('TimeLogs batch deleted', [
                'user_id'             => auth()->id(),
                'deleted_count'       => $deletedCount,
                'payroll_period_ids'  => $request->payroll_period_ids,
            ]);

            return redirect()->route('time_logs.index')->with('success', 'Selected time logs deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Batch delete failed', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('time_logs.index')->withErrors([
                'error' => 'Failed to delete selected time logs: ' . $e->getMessage()
            ]);
        }
    }
}
