<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\File;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        $user      = auth()->user();
        $companyId = $user->preference->company_id;

        $query = LeaveRequest::with(['employee.user'])
            ->where('company_id', $companyId);

        $employee = Employee::where('user_id', auth()->id())
            ->where('company_id', $companyId)
            ->firstOrFail();

        if (!$user->hasPermission('leave_request.browse_all')) {
            $employeeId = $employee->id;

            if (!$employeeId) {
                abort(403, 'No employee record linked to this user.');
            }

            /// Get subordinates directly reporting to the user (approver)
            $subordinateIds = Employee::where('approver_id', $user->id)
                ->where('company_id', $companyId)
                ->pluck('id')
                ->toArray();

            // Get departments headed by the user
            $departmentIds = \App\Models\Department::where('head_id', $user->id)
                ->where('company_id', $companyId)
                ->pluck('id')
                ->toArray();

            // Get employees under those departments
            $departmentEmployeeIds = Employee::whereIn('department_id', $departmentIds)
                ->where('company_id', $companyId)
                ->pluck('id')
                ->toArray();

            // Combine all allowed employee IDs
            $allowedEmployeeIds = array_unique(array_merge([$employeeId], $subordinateIds, $departmentEmployeeIds));

            $query->whereIn('employee_id', $allowedEmployeeIds);
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
                'leave_with_pay' => (bool) $request->input('leave_with_pay', false),
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
                        'company_id'     => $companyId,
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

        $companyId = auth()->user()->preference->company_id;
        $employee = Employee::where('user_id', auth()->id())
            ->where('company_id', $companyId)
            ->firstOrFail();
        $employeeId = $employee->id;

        // If user lacks 'browse_all' permission, allow if:
        // - they own the request
        // - OR they are an assigned approver
        if (!$user->hasPermission('leave_request.browse_all')) {
            $isOwner    = $leaveRequest->employee_id === $employeeId;
            $isApprover = $leaveRequest->approver_id === $user->id; // or use relationship

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

        $companyId = auth()->user()->preference->company_id;
        $employee = Employee::where('user_id', auth()->id())
            ->where('company_id', $companyId)
            ->firstOrFail();
        $employeeId = $employee->id;

        // If user lacks browse_all, ensure they are owner or approver
        if (!$user->hasPermission('leave_request.browse_all')) {
            $isOwner    = $employeeId && $leaveRequest->employee_id === $employeeId;
            $isApprover = $user->id                                 === $leaveRequest->employee->approver_id;

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

            $validated['leave_with_pay'] = (bool) $request->input('leave_with_pay', false);

            $leaveRequest->update($validated);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $uploadedFile) {
                    $path = $uploadedFile->store('uploads/leave_request_files');

                    $leaveRequest->files()->create([
                        'company_id'     => $companyId,
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
        $user       = auth()->user();

        $companyId = $user->preference->company_id;

        $employee = Employee::where('user_id', auth()->id())
            ->where('company_id', $companyId)
            ->firstOrFail();

        $employeeId = $employee->id;

        $isApprover = $user->id            === $leaveRequest->employee->approver_id;
        $isOwner    = $employeeId === $leaveRequest->employee_id;

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

        $companyId = $user->preference->company_id;

        $employee = Employee::where('user_id', auth()->id())
            ->where('company_id', $companyId)
            ->firstOrFail();

        $employeeId = $employee->id;

        // --- Permission rules ---
        if ($user->hasRole('admin') && $leaveRequest->employee->company_id === $companyId) {
            // Admin in same company can delete regardless of status
        } elseif ($leaveRequest->employee_id === $employeeId) {
            // Employee can delete only if status is pending
            if ($leaveRequest->status !== 'pending') {
                abort(403, 'You can only delete leave requests that are still pending.');
            }
        } else {
            abort(403, 'You are not allowed to delete this leave request.');
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
        $employee  = Employee::where('user_id', auth()->id())
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
                    $end   = Carbon::parse($request->end_date);

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
                    $end   = Carbon::parse($request->end_date);

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
            'reason'  => 'required|string|max:255',
            'leave_with_pay' => 'required|boolean',
            'files'   => 'array|max:5', // Max 5 files total
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
            $leaveRequest->status        = 'approved';
            $leaveRequest->approver_id   = auth()->id();
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
        $employee  = \App\Models\Employee::findOrFail($employeeId);
        $companyId = $employee->company_id;
        $year      = \Carbon\Carbon::parse($start)->year;

        // 1️⃣ Fetch leave balance for the year
        $leaveBalance = \App\Models\LeaveBalance::where('employee_id', $employeeId)
            ->where('company_id', $companyId)
            ->where('year', $year)
            ->first();

        $availableCredits = $leaveBalance?->beginning_balance ?? 0;

        // 2️⃣ Fetch all approved leaves (both paid and unpaid)
        $leaveRequests = \App\Models\LeaveRequest::where('employee_id', $employeeId)
            ->where('company_id', $companyId)
            ->where('status', 'approved')
            ->where(function ($query) use ($year) {
                $query->whereYear('start_date', $year)
                    ->orWhereYear('end_date', $year);
            })
            ->get();

        // 3️⃣ Flatten approved leaves into daily records
        $dailyLeaves = [];

        foreach ($leaveRequests as $leave) {
            $period = \Carbon\CarbonPeriod::create($leave->start_date, $leave->end_date);
            $daysCount = $period->count();

            if ($daysCount === 0) {
                continue;
            }

            $dailyValue = $daysCount > 0
                ? round($leave->number_of_days / $daysCount, 4)
                : $leave->number_of_days;

            foreach ($period as $date) {
                $dateStr = $date->toDateString();
                if (\Carbon\Carbon::parse($dateStr)->year !== $year) {
                    continue;
                }

                // keep only the largest leave value per date
                $dailyLeaves[$dateStr] = max(
                    $dailyLeaves[$dateStr] ?? 0,
                    $dailyValue
                );

                // mark if it's paid or unpaid
                $dailyLeaves[$dateStr . '_with_pay'] = (bool) $leave->leave_with_pay;
            }
        }

        // 4️⃣ Deduct *only paid leaves* that occurred before the requested start date
        $preUsedCredits = 0;
        foreach ($dailyLeaves as $key => $value) {
            if (str_ends_with($key, '_with_pay')) continue;
            $date = \Carbon\Carbon::parse($key);
            if ($date->lessThan($start) && ($dailyLeaves[$key . '_with_pay'] ?? false)) {
                $preUsedCredits += $value;
            }
        }

        $remaining = max(0, $availableCredits - $preUsedCredits);

        // 5️⃣ Loop through requested period and build result
        $period = \Carbon\CarbonPeriod::create($start, $end);
        $remainingCreditsByDate = [];
        $result = [];

        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            $leaveValue = $dailyLeaves[$dateStr] ?? 0;
            $isWithPay  = $dailyLeaves[$dateStr . '_with_pay'] ?? false;

            $deductible = $isWithPay && $leaveValue > 0;
            if ($deductible) {
                $remaining -= $leaveValue;
            }

            $remaining = max(0, $remaining);
            $remainingCreditsByDate[$dateStr] = round($remaining, 2);

            if ($leaveValue > 0) {
                $result[$dateStr] = [
                    'days'     => round($leaveValue, 2),
                    'with_pay' => $isWithPay,
                ];
            }
        }

        return response()->json([
            'dates'                     => $result,
            'remaining_credits'         => round($remaining, 2),
            'remaining_credits_by_date' => $remainingCreditsByDate,
            'original_balance'          => $availableCredits,
            'pre_used_credits'          => round($preUsedCredits, 2),
        ]);
    }

}
