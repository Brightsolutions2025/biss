<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class LeaveRequestController extends Controller
{
    /**
     * Display a listing of leave requests for the active company.
     */
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('leave_request.browse')) {
            abort(403, 'Unauthorized to browse leave requests.');
        }

        $user = auth()->user();
        $companyId = $user->preference->company_id;

        $query = LeaveRequest::with(['employee.user'])
            ->where('company_id', $companyId);

        if (!$user->hasPermission('leave_request.browse_all')) {
            $employeeId = $user->employee?->id;

            if (!$employeeId) {
                abort(403, 'No employee record linked to this user.');
            }

            // Get IDs of employees where the current user is the approver
            $subordinateIds = Employee::where('approver_id', $employeeId)
                ->pluck('id')
                ->toArray();

            // Include leave requests of the user and their subordinates
            $query->where(function ($q) use ($employeeId, $subordinateIds) {
                $q->where('employee_id', $employeeId)
                ->orWhereIn('employee_id', $subordinateIds);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('start_date', [$request->date_from, $request->date_to]);
        }

        $leaveRequests = $query->orderByDesc('start_date')->paginate(20)->appends($request->query());

        $employeeList = Employee::where('company_id', $companyId)
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get();

        return view('leave_requests.index', compact('leaveRequests', 'employeeList'));
    }

    /**
     * Show the form for creating a new leave request.
     */
    public function create()
    {
        if (!auth()->user()->hasPermission('leave_request.create')) {
            abort(403, 'Unauthorized to create leave requests.');
        }

        $companyId = auth()->user()->preference->company_id;
        $employee  = Employee::where('user_id', auth()->id())
            ->where('company_id', $companyId)
            ->firstOrFail();

        return view('leave_requests.create', compact('employee'));
    }

    /**
     * Store a newly created leave request in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('leave_request.create')) {
            abort(403, 'Unauthorized to create leave requests.');
        }

        $companyId = auth()->user()->preference->company_id;

        $employee = Employee::where('user_id', auth()->id())
            ->where('company_id', $companyId)
            ->firstOrFail();

        DB::beginTransaction();

        try {
            $validated = $this->validateLeave($request);

            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate   = Carbon::parse($request->end_date)->endOfDay();

            $hasOverlap = LeaveRequest::where('employee_id', $employee->id)
                ->where('company_id', $employee->company_id)
                ->whereIn('status', ['pending', 'approved'])
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $endDate)
                        ->where('end_date', '>=', $startDate);
                    });
                })
                ->exists();

            if ($hasOverlap) {
                return back()
                    ->withErrors(['start_date' => 'You already have a leave request that overlaps with these dates.'])
                    ->withInput();
            }

            $leaveRequest = LeaveRequest::create([
                'company_id'     => $companyId,
                'employee_id'    => $employee->id,
                'start_date'     => $validated['start_date'],
                'end_date'       => $validated['end_date'],
                'number_of_days' => $validated['number_of_days'],
                'reason'         => $validated['reason'],
                'status'         => 'pending',
                'approver_id'    => $employee->approver_id,
            ]);

            $approver = $leaveRequest->employee->approver;
            if ($approver && $approver->email) {
                $approver->notify(new \App\Notifications\LeaveRequestSubmitted($leaveRequest));
            }

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $uploadedFile) {
                    $path = $uploadedFile->store('uploads/leave_request_files');

                    $leaveRequest->files()->create([
                        'file_path' => $path,
                        'file_name' => $uploadedFile->getClientOriginalName(),
                    ]);
                }
            }

            DB::commit();

            Log::info('Leave request submitted', [
                'leave_request_id' => $leaveRequest->id,
                'employee_id'      => $employee->id,
            ]);

            return redirect()->route('leave_requests.index')->with('success', 'Leave request submitted.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to submit leave request', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors($e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified leave request.
     */
    public function show(LeaveRequest $leaveRequest)
    {
        $user = auth()->user();

        $this->authorizeLeaveRequest($leaveRequest);

        if (!auth()->user()->hasPermission('leave_request.read')) {
            abort(403, 'Unauthorized to read leave requests.');
        }

        $employeeId = $user->employee?->id;

        // If user lacks 'browse_all' permission, allow if:
        // - they own the request
        // - OR they are an assigned approver
        if (!$user->hasPermission('leave_request.browse_all')) {
            $isOwner = $leaveRequest->employee_id === $employeeId;
            $isApprover = $leaveRequest->approver_id === $employeeId; // or use relationship

            if (!$isOwner && !$isApprover) {
                abort(403, 'You are not allowed to view this leave request.');
            }
        }

        return view('leave_requests.show', compact('leaveRequest'));
    }

    /**
     * Show the form for editing the specified leave request.
     */
    public function edit(LeaveRequest $leaveRequest)
    {
        $user = auth()->user();

        $this->authorizeLeaveRequest($leaveRequest);

        if (!auth()->user()->hasPermission('leave_request.update')) {
            abort(403, 'Unauthorized to update leave requests.');
        }

        if (!$this->canEditLeaveRequest($leaveRequest)) {
            abort(403, 'You are not allowed to edit this leave request.');
        }

        // If user lacks browse_all, ensure they are owner or approver
        if (!$user->hasPermission('leave_request.browse_all')) {
            $employeeId = $user->employee?->id;
            $isOwner = $employeeId && $leaveRequest->employee_id === $employeeId;
            $isApprover = $user->id === $leaveRequest->employee->approver_id;

            if (!$isOwner && !$isApprover) {
                abort(403, 'You are not allowed to edit this leave request.');
            }
        }

        $leaveRequest->load('employee', 'files');

        return view('leave_requests.edit', compact('leaveRequest'));
    }

    /**
     * Update the specified leave request in storage.
     */
    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorizeLeaveRequest($leaveRequest);

        if (!auth()->user()->hasPermission('leave_request.update')) {
            abort(403, 'Unauthorized to update leave requests.');
        }

        if (!$this->canEditLeaveRequest($leaveRequest)) {
            abort(403, 'You are not allowed to edit this leave request.');
        }

        $companyId = auth()->user()->preference->company_id;

        DB::beginTransaction();

        try {
            $validated = $this->validateLeave($request, $leaveRequest->id);

            // Remove 'files' from the validated data to avoid SQL error
            unset($validated['files']);

            $leaveRequest->update($validated);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $uploadedFile) {
                    $path = $uploadedFile->store('uploads/leave_request_files');

                    $leaveRequest->files()->create([
                        'file_path' => $path,
                        'file_name' => $uploadedFile->getClientOriginalName(),
                    ]);
                }
            }

            $approver = $leaveRequest->employee->approver;
            if ($approver && $approver->email) {
                $approver->notify(new \App\Notifications\LeaveRequestSubmitted($leaveRequest));
            }

            DB::commit();

            Log::info('Leave request updated', [
                'leave_request_id' => $leaveRequest->id,
                'user_id'          => auth()->id(),
            ]);

            return redirect()->route('leave_requests.index')->with('success', 'Leave request updated.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update leave request', [
                'error'            => $e->getMessage(),
                'leave_request_id' => $leaveRequest->id,
                'user_id'          => auth()->id(),
            ]);

            return back()->withErrors($e->getMessage());
        }
    }

    protected function canEditLeaveRequest(LeaveRequest $leaveRequest): bool
    {
        $user = auth()->user();
        $isApprover = $user->id === $leaveRequest->employee->approver_id;
        $isOwner = $user->employee?->id === $leaveRequest->employee_id;

        // Approver can always edit; employee can edit only if not final
        return $isApprover || ($isOwner && !in_array($leaveRequest->status, ['approved', 'rejected']));
    }

    /**
     * Remove the specified leave request from storage.
     */
    public function destroy(LeaveRequest $leaveRequest)
    {
        $user = auth()->user();

        $this->authorizeLeaveRequest($leaveRequest);

        if (!auth()->user()->hasPermission('leave_request.delete')) {
            abort(403, 'Unauthorized to delete leave requests.');
        }

        // Ownership check if user lacks global permission
        if (!$user->hasPermission('leave_request.browse_all')) {
            $employeeId = $user->employee?->id;

            if (!$employeeId || $leaveRequest->employee_id !== $employeeId) {
                abort(403, 'You are not allowed to delete this leave request.');
            }

            // Prevent deletion if the leave request is approved or rejected
            if (in_array($leaveRequest->status, ['approved', 'rejected'])) {
                abort(403, 'You cannot delete a leave request that has already been approved or rejected.');
            }
        }

        DB::beginTransaction();

        try {
            // Delete associated files
            foreach ($leaveRequest->files as $file) {
                if (\Storage::exists($file->file_path)) {
                    \Storage::delete($file->file_path);
                }

                $file->delete(); // delete record from files table
            }

            $leaveRequest->delete();

            DB::commit();

            Log::info('Leave request deleted', [
                'leave_request_id' => $leaveRequest->id,
                'user_id'          => auth()->id(),
            ]);

            return redirect()->route('leave_requests.index')->with('success', 'Leave request deleted.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete leave request', [
                'error'            => $e->getMessage(),
                'leave_request_id' => $leaveRequest->id,
                'user_id'          => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while deleting the leave request.');
        }
    }

    /**
     * Private helper to ensure user belongs to the leave request's company.
     */
    protected function authorizeLeaveRequest(LeaveRequest $leaveRequest)
    {
        $companyId = auth()->user()->preference->company_id;

        if ($leaveRequest->company_id !== $companyId) {
            abort(403, 'Unauthorized');
        }
    }
    private function validateLeave(Request $request, $excludeId = null)
    {
        $companyId = auth()->user()->preference->company_id;
        $employee = Employee::where('user_id', auth()->id())
            ->where('company_id', $companyId)
            ->firstOrFail();
        return $request->validate([
            'start_date' => [
                'required',
                'date',
                function ($attribute, $value, $fail) use ($request, $employee, $excludeId) {
                    if (!$request->start_date || !$request->end_date) {
                        return;
                    }

                    $start = Carbon::parse($request->start_date);
                    $end = Carbon::parse($request->end_date);

                    $hasOverlap = LeaveRequest::where('employee_id', $employee->id)
                        ->whereIn('status', ['pending', 'approved'])
                        ->when($excludeId, function ($query) use ($excludeId) {
                            $query->where('id', '!=', $excludeId);
                        })
                        ->where(function ($query) use ($start, $end) {
                            $query->whereBetween('start_date', [$start, $end])
                                ->orWhereBetween('end_date', [$start, $end])
                                ->orWhere(function ($q) use ($start, $end) {
                                    $q->where('start_date', '<=', $start)
                                        ->where('end_date', '>=', $end);
                                });
                        })
                        ->exists();

                    if ($hasOverlap) {
                        $fail('This leave request overlaps with an existing approved or pending request.');
                    }
                },
            ],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
                function ($attribute, $value, $fail) use ($request) {
                    if (!$request->start_date || !$request->end_date) {
                        return;
                    }

                    $start = Carbon::parse($request->start_date);
                    $end = Carbon::parse($request->end_date);

                    if ($start->year !== $end->year) {
                        $fail('Start date and end date must be within the same calendar year.');
                    }
                },
            ],
            'number_of_days' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) use ($request) {
                    if (!$request->start_date || !$request->end_date) {
                        return;
                    }

                    $start = Carbon::parse($request->start_date);
                    $end   = Carbon::parse($request->end_date);

                    $calculatedDays = $start->diffInDaysFiltered(function ($date) {
                        return true; // Include all days
                    }, $end) + 1; // +1 to include start date

                    // Validation: must be 0.5 or whole number
                    if ($value != 0.5 && intval($value) != $value) {
                        $fail('The number of days must be 0.5 or a whole number.');
                    }

                    if ($value != 0.5 && $value < 1) {
                        $fail('The number of days must be 0.5 or at least 1.');
                    }

                    // Validation: number_of_days must match calculatedDays
                    if ($value == 0.5 && $calculatedDays != 1) {
                        $fail('0.5 day leave is only allowed for a 1-day leave period.');
                    }

                    if ($value != 0.5 && $value != $calculatedDays) {
                        $fail("The number of days ($value) does not match the number of days between the selected dates ($calculatedDays).");
                    }
                },
            ],
            'reason' => 'required|string|max:255',
            'files' => 'array|max:5', // Max 5 files total
            'files.*' => 'file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx,xlsx', // 5MB per file
        ]);
    }
    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorizeCompany($leaveRequest->company_id);

        $approverId = $leaveRequest->employee->approver_id;

        if (auth()->id() !== $approverId) {
            abort(403, 'Unauthorized');
        }

        DB::beginTransaction();

        try {
            $leaveRequest->status      = 'approved';
            $leaveRequest->approver_id = auth()->id();
            $leaveRequest->approval_date = Carbon::now('Asia/Manila');
            $leaveRequest->save();

            DB::commit();

            $employeeUser = $leaveRequest->employee->user;
            if ($employeeUser && $employeeUser->email) {
                $employeeUser->notify(new \App\Notifications\LeaveRequestStatusChanged($leaveRequest, 'approved'));
            }

            Log::info('Leave request approved', [
                'leave_request_id' => $leaveRequest->id,
                'approver_id'      => auth()->id(),
            ]);

            return redirect()->route('leave_requests.show', $leaveRequest->id)
                            ->with('success', 'Leave request approved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to approve leave request', [
                'error'            => $e->getMessage(),
                'leave_request_id' => $leaveRequest->id,
                'approver_id'      => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while approving the request.');
        }
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorizeCompany($leaveRequest->company_id);

        $approverId = $leaveRequest->employee->approver_id;

        if (auth()->id() !== $approverId) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $leaveRequest->status           = 'rejected';
            $leaveRequest->approver_id      = auth()->id();
            $leaveRequest->rejection_reason = $request->input('reason');
            $leaveRequest->save();

            $employeeUser = $leaveRequest->employee->user;
            if ($employeeUser && $employeeUser->email) {
                $employeeUser->notify(new \App\Notifications\LeaveRequestStatusChanged($leaveRequest, 'rejected'));
            }

            DB::commit();

            Log::info('Leave request rejected', [
                'leave_request_id' => $leaveRequest->id,
                'approver_id'      => auth()->id(),
                'reason'           => $request->input('reason'),
            ]);

            return redirect()->route('leave_requests.show', $leaveRequest->id)
                            ->with('success', 'Leave request rejected successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to reject leave request', [
                'error'            => $e->getMessage(),
                'leave_request_id' => $leaveRequest->id,
                'approver_id'      => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while rejecting the request.');
        }
    }
    protected function authorizeCompany($companyId)
    {
        $userCompanyId = auth()->user()->preference->company_id;

        if ($userCompanyId != $companyId || !auth()->user()->companies->contains($companyId)) {
            abort(403, 'Unauthorized');
        }
    }
    public function fetchApprovedByDate($employeeId, $start, $end)
    {
        $employee = \App\Models\Employee::findOrFail($employeeId);
        $companyId = $employee->company_id;
        $year = \Carbon\Carbon::parse($start)->year;

        // Get leave balance for the year
        $leaveBalance = \App\Models\LeaveBalance::where('employee_id', $employeeId)
            ->where('company_id', $companyId)
            ->where('year', $year)
            ->first();

        $availableCredits = $leaveBalance?->beginning_balance ?? 0;

        // Get all approved leaves for the year
        $leaveRequests = \App\Models\LeaveRequest::where('employee_id', $employeeId)
            ->where('company_id', $companyId)
            ->where('status', 'approved')
            ->where(function ($query) use ($year) {
                $query->whereYear('start_date', $year)
                    ->orWhereYear('end_date', $year);
            })
            ->get();

        // Step 1: Flatten all leaves into daily leave values
        $dailyLeaves = [];

        foreach ($leaveRequests as $leave) {
            $period = \Carbon\CarbonPeriod::create($leave->start_date, $leave->end_date);
            $daysCount = $period->count();

            // ✅ Skip zero-day leave ranges (for integrity and safety)
            if ($daysCount === 0) {
                continue;
            }

            $dailyValue = $daysCount > 0
                ? round($leave->number_of_days / $daysCount, 4)
                : $leave->number_of_days;

            foreach ($period as $date) {
                $dateStr = $date->toDateString();

                if (\Carbon\Carbon::parse($dateStr)->year == $year) {
                    $dailyLeaves[$dateStr] = isset($dailyLeaves[$dateStr])
                        ? $dailyLeaves[$dateStr] + $dailyValue
                        : $dailyValue;
                }
            }
        }

        // Step 2: Deduct leaves that happened BEFORE the $start date
        $priorDates = array_filter(array_keys($dailyLeaves), fn($date) => \Carbon\Carbon::parse($date)->lessThan($start));
        $preUsedCredits = 0;

        foreach ($priorDates as $date) {
            $preUsedCredits += $dailyLeaves[$date];
        }

        $remaining = max(0, $availableCredits - $preUsedCredits);

        // Step 3: Loop through each date in the requested range and compute running balance
        $period = \Carbon\CarbonPeriod::create($start, $end);
        $remainingCreditsByDate = [];
        $result = [];

        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            $leaveValue = $dailyLeaves[$dateStr] ?? 0;

            $withPay = $remaining >= $leaveValue;

            if ($leaveValue > 0 && $withPay) {
                $remaining -= $leaveValue;
            }

            $remainingCreditsByDate[$dateStr] = round($remaining, 2);

            if ($leaveValue > 0) {
                $result[$dateStr] = [
                    'days'     => round($leaveValue, 2),
                    'with_pay' => $withPay,
                ];
            }
        }

        return response()->json([
            'dates'                      => $result,
            'remaining_credits'          => round($remaining, 2),
            'remaining_credits_by_date' => $remainingCreditsByDate,
            'original_balance'           => $availableCredits,
            'pre_used_credits'           => round($preUsedCredits, 2),
        ]);
    }
}
