<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\OffsetRequest;
use App\Models\OvertimeRequest;
use App\Notifications\OffsetRequestStatusChanged;
use App\Notifications\OffsetRequestSubmitted;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OffsetRequestController extends Controller
{
    public const OT_OFFSET_VALID_AFTER_DAYS  = 'offset_valid_after_days';
    public const OT_OFFSET_VALID_BEFORE_DAYS = 'offset_valid_before_days';
    protected array $offsetValidity          = [];

    public function __construct()
    {
        $user = auth()->user();

        if ($user && $user->preference && $user->preference->company) {
            $company = $user->preference->company;

            $this->offsetValidity = [
                self::OT_OFFSET_VALID_AFTER_DAYS  => $company->offset_valid_after_days  ?? 90,
                self::OT_OFFSET_VALID_BEFORE_DAYS => $company->offset_valid_before_days ?? 26,
            ];
        } else {
            $this->offsetValidity = [
                self::OT_OFFSET_VALID_AFTER_DAYS  => 90,
                self::OT_OFFSET_VALID_BEFORE_DAYS => 26,
            ];
        }
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user->hasPermission('offset_request.browse')) {
            abort(403, 'Unauthorized to browse offset requests.');
        }

        $companyId = $user->preference->company_id;

        $query = OffsetRequest::with('employee.user')
            ->where('company_id', $companyId);

        if (!$user->hasPermission('offset_request.browse_all')) {
            $employeeId = $user->employee?->id;

            if (!$employeeId) {
                Log::warning('User without linked employee tried to browse offset requests.', [
                    'user_id' => $user->id,
                ]);
                abort(403, 'No employee record linked to this user.');
            }

            // Get IDs of subordinates where user is the approver
            $subordinateIds = Employee::where('approver_id', $employeeId)
                ->pluck('id')
                ->toArray();

            // Restrict to own and subordinates' offset requests
            $query->where(function ($q) use ($employeeId, $subordinateIds) {
                $q->where('employee_id', $employeeId)
                ->orWhereIn('employee_id', $subordinateIds);
            });
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('date', [$request->date_from, $request->date_to]);
        }

        $offsetRequests = $query->orderByDesc('date')->paginate(20)->appends($request->query());

        $employeeList = Employee::where('company_id', $companyId)
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get();

        return view('offset_requests.index', compact('offsetRequests', 'employeeList'));
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('offset_request.create')) {
            abort(403, 'Unauthorized to create offset requests.');
        }

        $companyId = auth()->user()->preference->company_id;
        $employee  = Employee::where('company_id', $companyId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $overtimeRequests = OvertimeRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($query) {
                $query
                    // Not yet expired
                    ->whereRaw('DATE_ADD(date, INTERVAL ' . $this->offsetValidity[self::OT_OFFSET_VALID_AFTER_DAYS] . ' DAY) >= ?', [now()->toDateString()])
                    ->orWhereRaw('DATE_ADD(date, INTERVAL ' . $this->offsetValidity[self::OT_OFFSET_VALID_AFTER_DAYS] . ' DAY) BETWEEN ? AND ?', [
                        now()->subDays($this->offsetValidity[self::OT_OFFSET_VALID_BEFORE_DAYS])->toDateString(),
                        now()->toDateString(),
                    ]);
            })
            ->get(['id', 'date', 'time_start', 'time_end', 'number_of_hours'])
            ->filter(function ($ot) {
                $used = DB::table('offset_overtime')
                    ->where('overtime_request_id', $ot->id)
                    ->sum('used_hours');
                $ot->used_hours = $used;
                return $used < $ot->number_of_hours;
            })
            ->sortBy('date');

        return view('offset_requests.create', compact('employee', 'overtimeRequests'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('offset_request.create')) {
            abort(403, 'Unauthorized to create offset requests.');
        }

        $companyId = auth()->user()->preference->company_id;

        // Decode only if JSON string is sent
        $overtimeInput = $request->input('overtime_requests');
        if (is_string($overtimeInput)) {
            $decoded = json_decode($overtimeInput, true);

            if (!is_array($decoded)) {
                return back()->withErrors(['overtime_requests' => 'Invalid overtime request data.'])->withInput();
            }

            $request->merge(['overtime_requests' => $decoded]);
        }

        $validated = $request->validate([
            'employee_id'                  => 'required|exists:employees,id',
            'date'                         => 'required|date',
            'project_or_event_description' => 'required|string',
            'time_start'                   => 'required|date_format:H:i',
            'time_end'                     => 'required|date_format:H:i|after:time_start',
            'number_of_hours'              => 'required|numeric|min:0.25',
            'reason'                       => 'nullable|string',

            'overtime_requests'              => 'required|array|min:1',
            'overtime_requests.*.id'         => 'required|exists:overtime_requests,id',
            'overtime_requests.*.used_hours' => 'required|numeric|min:0.5',
            'files'                          => 'array|max:5',
            'files.*'                        => 'file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx,xlsx',
        ]);

        // === Custom Validation Logic ===

        // 1. Compute time difference in hours
        $start       = \Carbon\Carbon::createFromFormat('H:i', $validated['time_start']);
        $end         = \Carbon\Carbon::createFromFormat('H:i', $validated['time_end']);
        $diffInHours = $start->floatDiffInRealHours($end); // float, like 1.75

        if (round($diffInHours, 2) !== round($validated['number_of_hours'], 2)) {
            return back()->withErrors([
                'number_of_hours' => 'The number of hours must equal the difference between Time Start and Time End.'
            ])->withInput();
        }

        // 2. Sum used_hours from overtime_requests
        $totalUsedHours = collect($validated['overtime_requests'])->sum('used_hours');

        if (round($totalUsedHours, 2) !== round($validated['number_of_hours'], 2)) {
            return back()->withErrors([
                'overtime_requests' => 'The total used overtime hours must equal the number of hours being offset.'
            ])->withInput();
        }

        // 3. Ensure used_hours does not exceed available hours on each overtime request
        foreach ($validated['overtime_requests'] as $ot) {
            $otRequest = \App\Models\OvertimeRequest::find($ot['id']);

            if (!$otRequest) {
                return back()->withErrors([
                    'overtime_requests' => 'One or more overtime requests are invalid.',
                ])->withInput();
            }

            // Calculate total already used for this overtime request from other offset requests
            $alreadyUsed = DB::table('offset_overtime')
                ->where('overtime_request_id', $ot['id'])
                ->sum('used_hours');

            // Add current used_hours from this submission (before insert)
            $proposedTotal = $alreadyUsed + $ot['used_hours'];

            if (round($proposedTotal, 2) > round($otRequest->number_of_hours, 2)) {
                return back()->withErrors([
                    'overtime_requests' => "The overtime request on {$otRequest->date} only has " .
                        number_format($otRequest->number_of_hours - $alreadyUsed, 2) .
                        " hour(s) available, but you're trying to use " . number_format($ot['used_hours'], 2) . ' hour(s).'
                ])->withInput();
            }

            // Check if offset request date is within 90 days of the overtime request date
            $offsetDate   = \Carbon\Carbon::parse($validated['date']);
            $overtimeDate = \Carbon\Carbon::parse($otRequest->date);

            // 🆕 Added validation: Overtime date must be strictly before offset date
            if (!$overtimeDate->lt($offsetDate)) {
                return back()->withErrors([
                    'overtime_requests' => "The overtime date ({$overtimeDate->toDateString()}) must be before the offset date ({$offsetDate->toDateString()})."
                ])->withInput();
            }

            // 🆕 Enforce filing grace period (today must be within OT date + 90 + 26 days)
            $latestFilingDate = $overtimeDate
                ->copy()
                ->addDays($this->offsetValidity[self::OT_OFFSET_VALID_AFTER_DAYS])
                ->addDays($this->offsetValidity[self::OT_OFFSET_VALID_BEFORE_DAYS]);

            if (now()->gt($latestFilingDate)) {
                return back()->withErrors([
                    'overtime_requests' => "Offset filing deadline for OT on {$overtimeDate->format('F j, Y')} was {$latestFilingDate->format('F j, Y')}. You can no longer use it for offset."
                ])->withInput();
            }

            if (
                $offsetDate->isAfter($overtimeDate->copy()->addDays($this->offsetValidity[self::OT_OFFSET_VALID_AFTER_DAYS]))
            ) {
                return back()->withErrors([
                    'overtime_requests' => 'The offset date must be within ' . $this->offsetValidity[self::OT_OFFSET_VALID_AFTER_DAYS] . " days *after* the overtime request dated {$overtimeDate->toDateString()}."
                ])->withInput();
            }
        }

        DB::beginTransaction();

        try {
            $offsetRequest = OffsetRequest::create([
                'company_id'                   => $companyId,
                'employee_id'                  => $validated['employee_id'],
                'date'                         => $validated['date'],
                'project_or_event_description' => $validated['project_or_event_description'],
                'time_start'                   => $validated['time_start'],
                'time_end'                     => $validated['time_end'],
                'number_of_hours'              => $validated['number_of_hours'],
                'reason'                       => $validated['reason'],
                'status'                       => 'pending',
                'approver_id'                  => optional(Employee::find($validated['employee_id']))->approver_id,
            ]);

            foreach ($validated['overtime_requests'] as $ot) {
                DB::table('offset_overtime')->insert([
                    'company_id'          => $companyId,
                    'offset_request_id'   => $offsetRequest->id,
                    'overtime_request_id' => $ot['id'],
                    'used_hours'          => $ot['used_hours'],
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            $approver = $offsetRequest->employee->approver;

            if ($approver && $approver->email) {
                $approver->notify(new OffsetRequestSubmitted($offsetRequest));
            }

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $uploadedFile) {
                    $path = $uploadedFile->store('uploads/offset_request_files');

                    $offsetRequest->files()->create([
                        'file_path' => $path,
                        'file_name' => $uploadedFile->getClientOriginalName(),
                    ]);
                }
            }

            DB::commit();

            Log::info('Offset request created', [
                'offset_request_id' => $offsetRequest->id,
                'user_id'           => auth()->id(),
                'employee_id'       => $validated['employee_id'],
                'date'              => $validated['date'],
                'hours'             => $validated['number_of_hours'],
            ]);

            return redirect()->route('offset_requests.index')->with('success', 'Offset request submitted.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create offset request', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors($e->getMessage())->withInput();
        }
    }

    public function show(OffsetRequest $offsetRequest)
    {
        $user = auth()->user();

        // Ensure user is accessing a request from their active company
        $this->authorizeCompany($offsetRequest->company_id);

        // Must have permission to read
        if (!$user->hasPermission('offset_request.read')) {
            abort(403, 'Unauthorized to view offset requests.');
        }

        $employeeId = $user->employee?->id;

        if (!$user->hasPermission('offset_request.browse_all')) {
            $isOwner    = $offsetRequest->employee_id           === $employeeId;
            $isApprover = $offsetRequest->employee->approver_id === $employeeId;

            if (!$isOwner && !$isApprover) {
                abort(403, 'You are not allowed to view this offset request.');
            }
        }

        // Always load the employee relation
        $employee = $offsetRequest->employee;

        $overtimeRequests = $offsetRequest->overtimeRequests;

        return view('offset_requests.show', compact('offsetRequest', 'employee', 'overtimeRequests'));
    }

    public function edit(OffsetRequest $offsetRequest)
    {
        $user = auth()->user();

        $this->authorizeCompany($offsetRequest->company_id);

        if (!auth()->user()->hasPermission('offset_request.update')) {
            abort(403, 'Unauthorized to edit offset requests.');
        }

        if (! $this->canEditOffsetRequest($offsetRequest)) {
            abort(403, 'You are not allowed to edit this offset request.');
        }

        $employee = $offsetRequest->employee;

        $currentOTIds = $offsetRequest->overtimeRequests->pluck('id')->toArray();

        $overtimeRequests = OvertimeRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($query) {
                $query
                    ->whereRaw('DATE_ADD(date, INTERVAL ' . $this->offsetValidity[self::OT_OFFSET_VALID_AFTER_DAYS] . ' DAY) >= ?', [now()->toDateString()])
                    ->orWhereRaw('DATE_ADD(date, INTERVAL ' . $this->offsetValidity[self::OT_OFFSET_VALID_AFTER_DAYS] . ' DAY) BETWEEN ? AND ?', [
                        now()->subDays($this->offsetValidity[self::OT_OFFSET_VALID_BEFORE_DAYS])->toDateString(),
                        now()->toDateString(),
                    ]);
            })
            ->get(['id', 'date', 'time_start', 'time_end', 'number_of_hours'])
            ->filter(function ($ot) use ($currentOTIds) {
                $used = DB::table('offset_overtime')
                    ->where('overtime_request_id', $ot->id)
                    ->sum('used_hours');

                $ot->used_hours = $used;

                // Allow if not fully used, OR it is already part of this offset request
                return $used < $ot->number_of_hours || in_array($ot->id, $currentOTIds);
            });

        return view('offset_requests.edit', compact('offsetRequest', 'employee', 'overtimeRequests'));
    }

    protected function canEditOffsetRequest(OffsetRequest $offsetRequest): bool
    {
        $employeeId = auth()->user()->employee?->id;
        $isOwner    = $offsetRequest->employee_id           === $employeeId;
        $isApprover = $offsetRequest->employee->approver_id === $employeeId;

        if ($isApprover) {
            return true;
        }

        return $isOwner && !in_array($offsetRequest->status, ['approved', 'rejected']);
    }

    public function update(Request $request, OffsetRequest $offsetRequest)
    {
        $companyId = auth()->user()->preference->company_id;

        if (!auth()->user()->hasPermission('offset_request.update')) {
            abort(403, 'Unauthorized to edit offset requests.');
        }

        $this->authorizeCompany($offsetRequest->company_id);

        if (! $this->canEditOffsetRequest($offsetRequest)) {
            abort(403, 'You are not allowed to edit this offset request.');
        }

        $decoded = json_decode($request->input('overtime_requests'), true);
        if (!is_array($decoded)) {
            return back()->withErrors(['overtime_requests' => 'Invalid overtime request data.'])->withInput();
        }
        $request->merge(['overtime_requests' => $decoded]);

        $validated = $request->validate([
            'employee_id'                  => 'required|exists:employees,id',
            'date'                         => 'required|date',
            'project_or_event_description' => 'required|string',
            'time_start'                   => 'required|date_format:H:i',
            'time_end'                     => 'required|date_format:H:i|after:time_start',
            'number_of_hours'              => 'required|numeric|min:0.25',
            'reason'                       => 'nullable|string',

            'overtime_requests'              => 'required|array|min:1',
            'overtime_requests.*.id'         => 'required|exists:overtime_requests,id',
            'overtime_requests.*.used_hours' => 'required|numeric|min:0.5',
            'files'                          => 'array|max:5',
            'files.*'                        => 'file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx,xlsx',
        ]);

        // === Custom Validation Logic ===

        // 1. Validate time diff
        $start       = \Carbon\Carbon::createFromFormat('H:i', $validated['time_start']);
        $end         = \Carbon\Carbon::createFromFormat('H:i', $validated['time_end']);
        $diffInHours = $start->floatDiffInRealHours($end);

        if (round($diffInHours, 2) !== round($validated['number_of_hours'], 2)) {
            return back()->withErrors([
                'number_of_hours' => 'The number of hours must equal the difference between Time Start and Time End.'
            ])->withInput();
        }

        // 2. Validate total used hours
        $totalUsedHours = collect($validated['overtime_requests'])->sum('used_hours');

        if (round($totalUsedHours, 2) !== round($validated['number_of_hours'], 2)) {
            return back()->withErrors([
                'overtime_requests' => 'The total used overtime hours must equal the number of hours being offset.'
            ])->withInput();
        }

        // 3. Validate each overtime request entry
        $offsetDate = \Carbon\Carbon::parse($validated['date']);

        foreach ($validated['overtime_requests'] as $ot) {
            $otModel = OvertimeRequest::findOrFail($ot['id']);

            $alreadyUsed = $otModel->offsetRequests()
                ->where('offset_request_id', '!=', $offsetRequest->id)
                ->sum('offset_overtime.used_hours');

            $available = round($otModel->number_of_hours - $alreadyUsed, 2);
            $usedHours = round(floatval($ot['used_hours']), 2);

            if ($usedHours > $available) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "overtime_requests.{$ot['id']}.used_hours" =>
                        "Cannot use {$usedHours} hrs. Only {$available} hrs available from OT on {$otModel->date}.",
                ]);
            }

            // 4. Check that offset date is within valid range relative to OT date
            $overtimeDate = \Carbon\Carbon::parse($otModel->date);

            // 🆕 Added validation: Overtime date must be strictly before offset date
            if (!$overtimeDate->lt($offsetDate)) {
                return back()->withErrors([
                    'overtime_requests' => "The overtime date ({$overtimeDate->toDateString()}) must be before the offset date ({$offsetDate->toDateString()})."
                ])->withInput();
            }

            // 🆕 Enforce filing grace period (today must be within OT date + 90 + 26 days)
            $latestFilingDate = $overtimeDate
                ->copy()
                ->addDays($this->offsetValidity[self::OT_OFFSET_VALID_AFTER_DAYS])
                ->addDays($this->offsetValidity[self::OT_OFFSET_VALID_BEFORE_DAYS]);

            if (now()->gt($latestFilingDate)) {
                return back()->withErrors([
                    'overtime_requests' => "Offset filing deadline for OT on {$overtimeDate->format('F j, Y')} was {$latestFilingDate->format('F j, Y')}. You can no longer use it for offset."
                ])->withInput();
            }

            if (
                $offsetDate->isAfter($overtimeDate->copy()->addDays($this->offsetValidity[self::OT_OFFSET_VALID_AFTER_DAYS]))
            ) {
                return back()->withErrors([
                    'overtime_requests' => 'The offset date must be within ' . $this->offsetValidity[self::OT_OFFSET_VALID_AFTER_DAYS] . " days *after* the overtime request dated {$overtimeDate->toDateString()}."
                ])->withInput();
            }
        }

        // === Proceed with update ===
        DB::beginTransaction();

        try {
            // Remove files from validated data to prevent SQL issues
            unset($validated['files']);

            // Update main offset request
            $offsetRequest->update([
                'employee_id'                  => $validated['employee_id'],
                'date'                         => $validated['date'],
                'project_or_event_description' => $validated['project_or_event_description'],
                'time_start'                   => $validated['time_start'],
                'time_end'                     => $validated['time_end'],
                'number_of_hours'              => $validated['number_of_hours'],
                'reason'                       => $validated['reason'],
            ]);

            // Re-sync pivot table
            DB::table('offset_overtime')->where('offset_request_id', $offsetRequest->id)->delete();

            foreach ($validated['overtime_requests'] as $ot) {
                $otModel     = OvertimeRequest::findOrFail($ot['id']);
                $alreadyUsed = $otModel->offsetRequests()
                    ->where('offset_request_id', '!=', $offsetRequest->id)
                    ->sum('offset_overtime.used_hours');

                $available = $otModel->number_of_hours - $alreadyUsed;
                $usedHours = floatval($ot['used_hours']);

                if ($usedHours > $available) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "overtime_requests.{$ot['id']}.used_hours" =>
                            "Cannot use {$usedHours} hrs. Only {$available} hrs available from OT on {$otModel->date}.",
                    ]);
                }

                DB::table('offset_overtime')->insert([
                    'company_id'          => $companyId,
                    'offset_request_id'   => $offsetRequest->id,
                    'overtime_request_id' => $ot['id'],
                    'used_hours'          => $usedHours,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            $approver = $offsetRequest->employee->approver;

            if ($approver && $approver->email) {
                $approver->notify(new OffsetRequestSubmitted($offsetRequest));
            }

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $uploadedFile) {
                    $path = $uploadedFile->store('uploads/offset_request_files');

                    $offsetRequest->files()->create([
                        'file_path' => $path,
                        'file_name' => $uploadedFile->getClientOriginalName(),
                    ]);
                }
            }

            DB::commit();

            Log::info('Offset request updated', [
                'id'            => $offsetRequest->id,
                'user_id'       => auth()->id(),
                'employee_id'   => $validated['employee_id'],
                'overtime_ids'  => collect($validated['overtime_requests'])->pluck('id')->all(),
            ]);

            return redirect()->route('offset_requests.index')->with('success', 'Offset request updated.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update offset request', [
                'error'   => $e->getMessage(),
                'id'      => $offsetRequest->id,
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors($e->getMessage())->withInput();
        }
    }

    public function destroy(OffsetRequest $offsetRequest)
    {
        $user = auth()->user();

        $this->authorizeCompany($offsetRequest->company_id);

        if (!auth()->user()->hasPermission('offset_request.delete')) {
            abort(403, 'Unauthorized to delete offset requests.');
        }

        $employeeId = $user->employee?->id;

        // Restrict non-global users to their own or subordinate records
        if (!$user->hasPermission('offset_request.browse_all')) {
            $isOwner    = $offsetRequest->employee_id           === $employeeId;
            $isApprover = $offsetRequest->employee->approver_id === $employeeId;

            if (! $isOwner && ! $isApprover) {
                abort(403, 'You are not allowed to delete this offset request.');
            }

            if (in_array($offsetRequest->status, ['approved', 'rejected'])) {
                abort(403, 'You cannot delete an offset request that has already been approved or rejected.');
            }
        }

        DB::beginTransaction();

        try {
            // Delete associated files
            foreach ($offsetRequest->files as $file) {
                if (\Storage::exists($file->file_path)) {
                    \Storage::delete($file->file_path);
                }

                $file->delete(); // remove from DB
            }

            $offsetRequest->delete();

            DB::commit();

            Log::info('Offset request deleted', ['id' => $offsetRequest->id, 'user_id' => auth()->id()]);

            return redirect()->route('offset_requests.index')->with('success', 'Offset request deleted.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete offset request', [
                'error'   => $e->getMessage(),
                'id'      => $offsetRequest->id,
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while deleting the offset request.');
        }
    }

    protected function authorizeCompany($companyId)
    {
        $userCompanyId = auth()->user()->preference->company_id;

        if ($userCompanyId != $companyId || !auth()->user()->companies->contains('id', $companyId)) {
            abort(403, 'Unauthorized');
        }
    }
    public function approve(Request $request, OffsetRequest $offsetRequest)
    {
        $this->authorizeCompany($offsetRequest->company_id);

        $approverId = $offsetRequest->employee->approver_id;

        if (auth()->id() !== $approverId) {
            abort(403, 'Unauthorized');
        }

        DB::beginTransaction();

        try {
            $offsetRequest->status           = 'approved';
            $offsetRequest->approver_id      = auth()->id();
            $offsetRequest->rejection_reason = null; // clear rejection if previously set
            $offsetRequest->approval_date    = Carbon::now('Asia/Manila');
            $offsetRequest->save();

            $employeeUser = $offsetRequest->employee->user;

            if ($employeeUser && $employeeUser->email) {
                $employeeUser->notify(new OffsetRequestStatusChanged($offsetRequest, 'approved'));
            }

            DB::commit();

            Log::info('Offset request approved', [
                'offset_request_id' => $offsetRequest->id,
                'approver_id'       => auth()->id(),
            ]);

            return redirect()->route('offset_requests.show', $offsetRequest->id)
                            ->with('success', 'Offset request approved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to approve offset request', [
                'error'             => $e->getMessage(),
                'offset_request_id' => $offsetRequest->id,
                'approver_id'       => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while approving the request.');
        }
    }

    public function reject(Request $request, OffsetRequest $offsetRequest)
    {
        $this->authorizeCompany($offsetRequest->company_id);

        $approverId = $offsetRequest->employee->approver_id;

        if (auth()->id() !== $approverId) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $offsetRequest->status           = 'rejected';
            $offsetRequest->approver_id      = auth()->id();
            $offsetRequest->rejection_reason = $request->input('reason');
            $offsetRequest->save();

            $employeeUser = $offsetRequest->employee->user;

            if ($employeeUser && $employeeUser->email) {
                $employeeUser->notify(new OffsetRequestStatusChanged($offsetRequest, 'rejected'));
            }

            DB::commit();

            Log::info('Offset request rejected', [
                'offset_request_id' => $offsetRequest->id,
                'approver_id'       => auth()->id(),
                'reason'            => $request->input('reason'),
            ]);

            return redirect()->route('offset_requests.show', $offsetRequest->id)
                            ->with('success', 'Offset request rejected successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to reject offset request', [
                'error'             => $e->getMessage(),
                'offset_request_id' => $offsetRequest->id,
                'approver_id'       => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while rejecting the request.');
        }
    }
    public function fetchApprovedByDate($employeeId, $start, $end)
    {
        $requests = OffsetRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereBetween('date', [$start, $end])
            ->get()
            ->groupBy('date')
            ->mapWithKeys(function ($group, $date) {
                $earliestStart = $group->min('time_start');
                $latestEnd     = $group->max('time_end');
                $totalHours    = $group->sum('number_of_hours');

                return [$date => [
                    'hours' => $totalHours,
                    'start' => $earliestStart ? \Carbon\Carbon::parse($earliestStart)->format('H:i') : null,
                    'end'   => $latestEnd ? \Carbon\Carbon::parse($latestEnd)->format('H:i') : null,
                ]];
            });

        return response()->json($requests);
    }
}
