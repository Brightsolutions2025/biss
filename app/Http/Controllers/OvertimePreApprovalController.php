<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\OvertimePreApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class OvertimePreApprovalController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user->hasPermission('overtime_pre_approval.browse')) {
            abort(403, 'Unauthorized to browse overtime pre-approvals.');
        }

        $companyId = $user->preference->company_id;

        $query = OvertimePreApproval::with('employee')
            ->where('company_id', $companyId);

        $employee = Employee::where('user_id', auth()->id())
            ->where('company_id', $companyId)
            ->firstOrFail();

        if (!$user->hasPermission('overtime_pre_approval.browse_all')) {
            $subordinateIds = Employee::where('approver_id', $user->id)
                ->where('company_id', $companyId)
                ->pluck('id')
                ->toArray();

            $departmentIds = \App\Models\Department::where('head_id', $user->id)
                ->where('company_id', $companyId)
                ->pluck('id')
                ->toArray();

            $departmentEmployeeIds = Employee::whereIn('department_id', $departmentIds)
                ->where('company_id', $companyId)
                ->pluck('id')
                ->toArray();

            $allowedEmployeeIds = array_unique(array_merge(
                [$employee->id],
                $subordinateIds,
                $departmentEmployeeIds
            ));

            $query->whereIn('employee_id', $allowedEmployeeIds);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('date', [$request->date_from, $request->date_to]);
        }

        $overtimePreApprovals = $query
            ->orderByDesc('date')
            ->paginate(20)
            ->appends($request->query());

        $employeeList = Employee::where('company_id', $companyId)
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get();

        return view('overtime_pre_approvals.index', compact(
            'overtimePreApprovals',
            'employeeList'
        ));
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('overtime_pre_approval.create')) {
            abort(403, 'Unauthorized to create overtime pre-approvals.');
        }

        $companyId = auth()->user()->preference->company_id;

        $employee = Employee::where('company_id', $companyId)
            ->where('user_id', auth()->id())
            ->first();

        return view('overtime_pre_approvals.create', compact('employee'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('overtime_pre_approval.create')) {
            abort(403, 'Unauthorized to create overtime pre-approvals.');
        }

        $companyId = auth()->user()->preference->company_id;

        $employee = Employee::where('company_id', $companyId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $request->merge([
            'planned_time_start' => $request->filled('planned_time_start')
                ? Carbon::parse($request->input('planned_time_start'))->format('H:i')
                : null,
            'planned_time_end' => $request->filled('planned_time_end')
                ? Carbon::parse($request->input('planned_time_end'))->format('H:i')
                : null,
        ]);

        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
            ],
            'date' => 'required|date',
            'planned_time_start' => 'required|date_format:H:i',
            'planned_time_end' => 'required|date_format:H:i',
            'estimated_number_of_hours' => 'required|numeric|min:0.25',
            'reason' => 'required|string',
            'planned_tasks' => 'nullable|string',
        ]);

        $validated['employee_id'] = $employee->id;

        $start = Carbon::createFromFormat('H:i', $validated['planned_time_start']);
        $end = Carbon::createFromFormat('H:i', $validated['planned_time_end']);

        if ($end <= $start) {
            $end->addDay();
        }

        $maxHours = $start->diffInMinutes($end) / 60;

        if ($validated['estimated_number_of_hours'] > $maxHours) {
            return back()
                ->withErrors([
                    'estimated_number_of_hours' => 'Estimated hours exceed the planned time duration.',
                ])
                ->withInput();
        }

        if ($this->hasOverlappingPreApproval(
            $employee->id,
            $validated['date'],
            $validated['planned_time_start'],
            $validated['planned_time_end']
        )) {
            return back()
                ->withErrors([
                    'planned_time_start' => 'This overtime pre-approval overlaps with an existing request.',
                ])
                ->withInput();
        }

        DB::beginTransaction();

        try {
            $overtimePreApproval = OvertimePreApproval::create([
                'company_id' => $companyId,
                'employee_id' => $employee->id,
                'date' => $validated['date'],
                'planned_time_start' => $validated['planned_time_start'],
                'planned_time_end' => $validated['planned_time_end'],
                'estimated_number_of_hours' => $validated['estimated_number_of_hours'],
                'reason' => $validated['reason'],
                'planned_tasks' => $validated['planned_tasks'] ?? null,
                'status' => 'pending',
            ]);

            $approver = $overtimePreApproval->employee->approver ?? null;

            if ($approver && $approver->email) {
                // Optional: create this notification later
                // $approver->notify(new \App\Notifications\OvertimePreApprovalSubmitted($overtimePreApproval));
            }

            DB::commit();

            Log::info('Overtime pre-approval created', [
                'overtime_pre_approval_id' => $overtimePreApproval->id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('overtime_pre_approvals.index')
                ->with('success', 'Overtime pre-approval submitted.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create overtime pre-approval', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors($e->getMessage())->withInput();
        }
    }

    public function show(OvertimePreApproval $overtimePreApproval)
    {
        $user = auth()->user();

        $this->authorizeCompany($overtimePreApproval->company_id);

        if (!$user->hasPermission('overtime_pre_approval.read')) {
            abort(403, 'Unauthorized to view overtime pre-approvals.');
        }

        $companyId = $user->preference->company_id;

        $employee = Employee::where('user_id', auth()->id())
            ->where('company_id', $companyId)
            ->firstOrFail();

        if (!$user->hasPermission('overtime_pre_approval.browse_all')) {
            $isOwner = $overtimePreApproval->employee_id === $employee->id;
            $isApprover = $overtimePreApproval->employee->approver_id === $user->id;

            if (!$isOwner && !$isApprover) {
                abort(403, 'You are not allowed to view this overtime pre-approval.');
            }
        }

        return view('overtime_pre_approvals.show', compact('overtimePreApproval'));
    }

    public function edit(OvertimePreApproval $overtimePreApproval)
    {
        $user = auth()->user();

        $this->authorizeCompany($overtimePreApproval->company_id);

        if (!$user->hasPermission('overtime_pre_approval.update')) {
            abort(403, 'Unauthorized to edit overtime pre-approvals.');
        }

        if (!$this->canEditPreApproval($overtimePreApproval)) {
            abort(403, 'You are not allowed to edit this overtime pre-approval.');
        }

        $companyId = $user->preference->company_id;

        $employees = Employee::where('company_id', $companyId)
            ->orderBy('first_name')
            ->get();

        return view('overtime_pre_approvals.edit', compact(
            'overtimePreApproval',
            'employees'
        ));
    }

    public function update(Request $request, OvertimePreApproval $overtimePreApproval)
    {
        $this->authorizeCompany($overtimePreApproval->company_id);

        if (!auth()->user()->hasPermission('overtime_pre_approval.update')) {
            abort(403, 'Unauthorized to edit overtime pre-approvals.');
        }

        if (!$this->canEditPreApproval($overtimePreApproval)) {
            abort(403, 'You are not allowed to edit this overtime pre-approval.');
        }

        $user = auth()->user();
        $companyId = $user->preference->company_id;

        $employee = Employee::where('user_id', auth()->id())
            ->where('company_id', $companyId)
            ->firstOrFail();

        $request->merge([
            'planned_time_start' => $request->filled('planned_time_start')
                ? Carbon::parse($request->input('planned_time_start'))->format('H:i')
                : null,
            'planned_time_end' => $request->filled('planned_time_end')
                ? Carbon::parse($request->input('planned_time_end'))->format('H:i')
                : null,
        ]);

        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
            ],
            'date' => 'required|date',
            'planned_time_start' => 'required|date_format:H:i',
            'planned_time_end' => 'required|date_format:H:i',
            'estimated_number_of_hours' => 'required|numeric|min:0.25',
            'reason' => 'required|string',
            'planned_tasks' => 'nullable|string',
        ]);

        if (!$user->hasPermission('overtime_pre_approval.browse_all')) {
            $validated['employee_id'] = $employee->id;
        }

        $start = Carbon::createFromFormat('H:i', $validated['planned_time_start']);
        $end = Carbon::createFromFormat('H:i', $validated['planned_time_end']);

        if ($end <= $start) {
            $end->addDay();
        }

        $maxHours = $start->diffInMinutes($end) / 60;

        if ($validated['estimated_number_of_hours'] > $maxHours) {
            return back()
                ->withErrors([
                    'estimated_number_of_hours' => 'Estimated hours exceed the planned time duration.',
                ])
                ->withInput();
        }

        if ($this->hasOverlappingPreApproval(
            $validated['employee_id'],
            $validated['date'],
            $validated['planned_time_start'],
            $validated['planned_time_end'],
            $overtimePreApproval->id
        )) {
            return back()
                ->withErrors([
                    'planned_time_start' => 'This overtime pre-approval overlaps with an existing request.',
                ])
                ->withInput();
        }

        DB::beginTransaction();

        try {
            $overtimePreApproval->update($validated);

            DB::commit();

            Log::info('Overtime pre-approval updated', [
                'overtime_pre_approval_id' => $overtimePreApproval->id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('overtime_pre_approvals.index')
                ->with('success', 'Overtime pre-approval updated.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update overtime pre-approval', [
                'error' => $e->getMessage(),
                'overtime_pre_approval_id' => $overtimePreApproval->id,
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while updating the request.');
        }
    }

    public function destroy(OvertimePreApproval $overtimePreApproval)
    {
        $user = auth()->user();

        $this->authorizeCompany($overtimePreApproval->company_id);

        if (!$user->hasPermission('overtime_pre_approval.delete')) {
            abort(403, 'Unauthorized to delete overtime pre-approvals.');
        }

        $companyId = $user->preference->company_id;

        $employee = Employee::where('user_id', auth()->id())
            ->where('company_id', $companyId)
            ->firstOrFail();

        if ($user->hasRole('admin') && $overtimePreApproval->company_id === $companyId) {
            // Admin can delete.
        } elseif ($overtimePreApproval->employee_id === $employee->id) {
            if ($overtimePreApproval->status !== 'pending') {
                abort(403, 'You can only delete pending overtime pre-approvals.');
            }
        } else {
            abort(403, 'You are not allowed to delete this overtime pre-approval.');
        }

        DB::beginTransaction();

        try {
            $overtimePreApproval->delete();

            DB::commit();

            Log::info('Overtime pre-approval deleted', [
                'overtime_pre_approval_id' => $overtimePreApproval->id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('overtime_pre_approvals.index')
                ->with('success', 'Overtime pre-approval deleted.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete overtime pre-approval', [
                'error' => $e->getMessage(),
                'overtime_pre_approval_id' => $overtimePreApproval->id,
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while deleting the request.');
        }
    }

    public function approve(Request $request, OvertimePreApproval $overtimePreApproval)
    {
        $this->authorizeCompany($overtimePreApproval->company_id);

        $approverId = $overtimePreApproval->employee->approver_id;

        if (is_null($approverId)) {
            abort(403, 'No approver is assigned to this employee.');
        }

        if (auth()->id() !== $approverId) {
            abort(403, 'Unauthorized: You are not the assigned approver.');
        }

        if ($overtimePreApproval->status !== 'pending') {
            return back()->withErrors('Only pending overtime pre-approvals can be approved.');
        }

        DB::beginTransaction();

        try {
            $overtimePreApproval->status = 'approved';
            $overtimePreApproval->approver_id = auth()->id();
            $overtimePreApproval->approval_date = Carbon::now('Asia/Manila')->toDateString();
            $overtimePreApproval->rejection_reason = null;
            $overtimePreApproval->save();

            $employeeUser = $overtimePreApproval->employee->user ?? null;

            if ($employeeUser && $employeeUser->email) {
                // Optional: create this notification later
                // $employeeUser->notify(new \App\Notifications\OvertimePreApprovalStatusChanged($overtimePreApproval, 'approved'));
            }

            DB::commit();

            Log::info('Overtime pre-approval approved', [
                'overtime_pre_approval_id' => $overtimePreApproval->id,
                'approver_id' => auth()->id(),
            ]);

            return redirect()
                ->route('overtime_pre_approvals.show', $overtimePreApproval->id)
                ->with('success', 'Overtime pre-approval approved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to approve overtime pre-approval', [
                'error' => $e->getMessage(),
                'overtime_pre_approval_id' => $overtimePreApproval->id,
                'approver_id' => auth()->id(),
            ]);

            return back()->withErrors($e->getMessage())->withInput();
        }
    }

    public function reject(Request $request, OvertimePreApproval $overtimePreApproval)
    {
        $this->authorizeCompany($overtimePreApproval->company_id);

        $approverId = $overtimePreApproval->employee->approver_id;

        if (is_null($approverId)) {
            abort(403, 'No approver is assigned to this employee.');
        }

        if (auth()->id() !== $approverId) {
            abort(403, 'Unauthorized: You are not the assigned approver.');
        }

        if ($overtimePreApproval->status !== 'pending') {
            return back()->withErrors('Only pending overtime pre-approvals can be rejected.');
        }

        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $overtimePreApproval->status = 'rejected';
            $overtimePreApproval->approver_id = auth()->id();
            $overtimePreApproval->approval_date = null;
            $overtimePreApproval->rejection_reason = $request->input('reason');
            $overtimePreApproval->save();

            $employeeUser = $overtimePreApproval->employee->user ?? null;

            if ($employeeUser && $employeeUser->email) {
                // Optional: create this notification later
                // $employeeUser->notify(new \App\Notifications\OvertimePreApprovalStatusChanged($overtimePreApproval, 'rejected'));
            }

            DB::commit();

            Log::info('Overtime pre-approval rejected', [
                'overtime_pre_approval_id' => $overtimePreApproval->id,
                'approver_id' => auth()->id(),
                'reason' => $request->input('reason'),
            ]);

            return redirect()
                ->route('overtime_pre_approvals.show', $overtimePreApproval->id)
                ->with('success', 'Overtime pre-approval rejected successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to reject overtime pre-approval', [
                'error' => $e->getMessage(),
                'overtime_pre_approval_id' => $overtimePreApproval->id,
                'approver_id' => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while rejecting the request.');
        }
    }

    public function cancel(OvertimePreApproval $overtimePreApproval)
    {
        $this->authorizeCompany($overtimePreApproval->company_id);

        if (!auth()->user()->hasPermission('overtime_pre_approval.update')) {
            abort(403, 'Unauthorized to cancel overtime pre-approvals.');
        }

        if (!$this->canEditPreApproval($overtimePreApproval)) {
            abort(403, 'You are not allowed to cancel this overtime pre-approval.');
        }

        if (!in_array($overtimePreApproval->status, ['pending', 'approved'])) {
            return back()->withErrors('Only pending or approved overtime pre-approvals can be cancelled.');
        }

        DB::beginTransaction();

        try {
            $overtimePreApproval->status = 'cancelled';
            $overtimePreApproval->save();

            DB::commit();

            Log::info('Overtime pre-approval cancelled', [
                'overtime_pre_approval_id' => $overtimePreApproval->id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('overtime_pre_approvals.show', $overtimePreApproval->id)
                ->with('success', 'Overtime pre-approval cancelled successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to cancel overtime pre-approval', [
                'error' => $e->getMessage(),
                'overtime_pre_approval_id' => $overtimePreApproval->id,
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while cancelling the request.');
        }
    }

    protected function canEditPreApproval(OvertimePreApproval $overtimePreApproval): bool
    {
        $user = auth()->user();
        $companyId = $user->preference->company_id;

        $employee = Employee::where('user_id', auth()->id())
            ->where('company_id', $companyId)
            ->firstOrFail();

        $isOwner = $employee->id === $overtimePreApproval->employee_id;
        $isApprover = $user->id === $overtimePreApproval->employee->approver_id;

        if ($isApprover) {
            return true;
        }

        return $isOwner && $overtimePreApproval->status === 'pending';
    }

    protected function authorizeCompany($companyId)
    {
        $userCompanyId = auth()->user()->preference->company_id;

        if ($userCompanyId != $companyId || !auth()->user()->companies->contains($companyId)) {
            abort(403, 'Unauthorized');
        }
    }

    protected function hasOverlappingPreApproval($employeeId, $date, $timeStart, $timeEnd, $excludeRequestId = null)
    {
        $query = OvertimePreApproval::where('employee_id', $employeeId)
            ->where('date', $date)
            ->whereIn('status', ['pending', 'approved']);

        if ($excludeRequestId) {
            $query->where('id', '<>', $excludeRequestId);
        }

        $timeStart = Carbon::createFromFormat('H:i', $timeStart);
        $timeEnd = Carbon::createFromFormat('H:i', $timeEnd);

        if ($timeEnd <= $timeStart) {
            $timeEnd->addDay();
        }

        $existingRequests = $query->get();

        foreach ($existingRequests as $request) {
            $existingStart = Carbon::parse($request->planned_time_start);
            $existingEnd = Carbon::parse($request->planned_time_end);

            if ($existingEnd <= $existingStart) {
                $existingEnd->addDay();
            }

            if ($timeStart < $existingEnd && $timeEnd > $existingStart) {
                return true;
            }
        }

        return false;
    }
}