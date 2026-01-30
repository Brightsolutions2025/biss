<?php

namespace App\Http\Controllers;

use App\Models\DayOffChangeRequest;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class DayOffChangeRequestController extends Controller
{
    /**
     * Display a listing of the day off change requests.
     */
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('day_off_change_request.browse')) {
            abort(403, 'Unauthorized to browse day off change requests.');
        }

        $user      = auth()->user();
        $companyId = $user->preference->company_id;

        $query = DayOffChangeRequest::with('employee.user')
            ->where('company_id', $companyId);

        $employee = Employee::where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        if (! $user->hasPermission('day_off_change_request.browse_all')) {
            $allowedEmployeeIds = Employee::where('company_id', $companyId)
                ->where(function ($q) use ($employee, $user) {
                    $q->where('id', $employee->id)
                      ->orWhere('approver_id', $user->id);
                })
                ->pluck('id')
                ->toArray();

            $query->whereIn('employee_id', $allowedEmployeeIds);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('extension_date', [
                $request->date_from,
                $request->date_to,
            ]);
        }

        $requests = $query
            ->orderByDesc('extension_date')
            ->paginate(20)
            ->appends($request->query());

        $employeeList = Employee::where('company_id', $companyId)
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get();

        return view('day_off_change_requests.index', compact('requests', 'employeeList'));
    }

    /**
     * Show the form for creating a new request.
     */
    public function create()
    {
        if (!auth()->user()->hasPermission('day_off_change_request.create')) {
            abort(403, 'Unauthorized to create request.');
        }

        $companyId = auth()->user()->preference->company_id;

        $employee = Employee::where('company_id', $companyId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return view('day_off_change_requests.create', compact('employee'));
    }

    /**
     * Store a newly created request.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('day_off_change_request.create')) {
            abort(403, 'Unauthorized.');
        }

        $companyId = auth()->user()->preference->company_id;

        // Use authenticated user's employee record
        $employee = Employee::where('company_id', $companyId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Normalize time inputs
        $request->merge([
            'time_start' => $request->filled('time_start') ? Carbon::parse($request->time_start)->format('H:i') : null,
            'time_end'   => $request->filled('time_end')   ? Carbon::parse($request->time_end)->format('H:i')   : null,
        ]);

        $validated = $request->validate([
            'reason_type'     => 'required|string',
            'extension_date'  => 'required|date',
            'time_start'      => 'required|date_format:H:i',
            'time_end'        => 'required|date_format:H:i',
            'number_of_hours' => 'required|numeric|min:0.25',
            'old_date'        => 'required|date',
            'new_date'        => 'required|date|after_or_equal:extension_date',
            'reason'          => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $requestData = array_merge($validated, [
                'company_id'  => $companyId,
                'employee_id' => $employee->id,
                'status'      => 'pending',
                'approver_id' => $employee->approver_id,
            ]);

            $dayOffRequest = DayOffChangeRequest::create($requestData);

            DB::commit();

            // Optionally notify approver
            if ($employee->approver && $employee->approver->email) {
                $employee->approver->notify(new \App\Notifications\DayOffChangeRequestSubmitted($dayOffRequest));
            }

            return redirect()
                ->route('day_off_change_requests.index')
                ->with('success', 'Day off change request submitted.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create day off change request', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors($e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified request.
     */
    public function show(DayOffChangeRequest $dayOffChangeRequest)
    {
        $this->authorizeCompany($dayOffChangeRequest->company_id);

        if (!auth()->user()->hasPermission('day_off_change_request.read')) {
            abort(403);
        }

        $dayOffChangeRequest->load('employee.user');

        return view('day_off_change_requests.show', compact('dayOffChangeRequest'));
    }

    public function edit(DayOffChangeRequest $dayOffChangeRequest)
    {
        $this->authorizeCompany($dayOffChangeRequest->company_id);

        if (!auth()->user()->hasPermission('day_off_change_request.update')) abort(403);

        $employee = Employee::where('company_id', $dayOffChangeRequest->company_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $isOwner = $employee->id === $dayOffChangeRequest->employee_id;
        $isApprover = auth()->id() === $dayOffChangeRequest->employee->approver_id;

        if (!$isOwner && !$isApprover) abort(403, 'Unauthorized to edit request');

        return view('day_off_change_requests.edit', [
            'dayOffChangeRequest' => $dayOffChangeRequest
        ]);
    }

    public function update(Request $request, DayOffChangeRequest $dayOffChangeRequest)
    {
        $this->authorizeCompany($dayOffChangeRequest->company_id);

        if (!auth()->user()->hasPermission('day_off_change_request.update')) {
            abort(403, 'Unauthorized to edit day off change requests.');
        }

        $companyId = $dayOffChangeRequest->company_id;

        $employee = Employee::where('company_id', $companyId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        $employeeId = $employee->id;

        $isOwner    = $employeeId === $dayOffChangeRequest->employee_id;
        $isApprover = auth()->id() === $dayOffChangeRequest->employee->approver_id;

        if (!$isOwner && !$isApprover) {
            abort(403, 'Unauthorized to update this request.');
        }

        $request->merge([
            'time_start' => $request->filled('time_start') ? Carbon::parse($request->input('time_start'))->format('H:i') : null,
            'time_end'   => $request->filled('time_end')   ? Carbon::parse($request->input('time_end'))->format('H:i') : null,
        ]);

        $validated = $request->validate([
            'reason_type'     => 'required|string',
            'extension_date'  => 'required|date',
            'time_start'      => 'required|date_format:H:i',
            'time_end'        => 'required|date_format:H:i',
            'number_of_hours' => 'required|numeric|min:0.25',
            'old_date'        => 'required|date',
            'new_date'        => 'required|date|after_or_equal:extension_date',
            'reason'          => 'required|string',
        ]);

        // Force the employee_id to the logged-in employee
        $validated['employee_id'] = $employeeId;

        DB::beginTransaction();
        try {
            $dayOffChangeRequest->update($validated);

            // Optional: notify approver if status changes (if you have that feature)
            if ($isApprover && $request->filled('status')) {
                $dayOffChangeRequest->status = $request->input('status');
                $dayOffChangeRequest->approval_date = $request->input('status') === 'approved' ? now() : null;
                $dayOffChangeRequest->rejection_reason = $request->input('status') === 'rejected' ? $request->input('rejection_reason') : null;
                $dayOffChangeRequest->save();
            }

            DB::commit();

            return redirect()->route('day_off_change_requests.show', $dayOffChangeRequest)
                ->with('success', 'Request updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors($e->getMessage())->withInput();
        }
    }

    public function destroy(DayOffChangeRequest $dayOffChangeRequest)
    {
        $this->authorizeCompany($dayOffChangeRequest->company_id);

        $employee = Employee::where('company_id', $dayOffChangeRequest->company_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $isOwner = $employee->id === $dayOffChangeRequest->employee_id;
        $isApprover = auth()->id() === $dayOffChangeRequest->employee->approver_id;

        if (!$isOwner && !$isApprover) abort(403, 'Unauthorized to delete request');
        if ($isOwner && $dayOffChangeRequest->status !== 'pending') abort(403, 'Cannot delete non-pending request');

        DB::transaction(function () use ($dayOffChangeRequest) {
            $dayOffChangeRequest->delete();
        });

        return redirect()->route('day_off_change_requests.index')
            ->with('success', 'Request deleted successfully.');
    }

    /**
     * Approve request.
     */
    public function approve(DayOffChangeRequest $dayOffChangeRequest)
    {
        $this->authorizeCompany($dayOffChangeRequest->company_id);

        if (auth()->id() !== $dayOffChangeRequest->employee->approver_id) {
            abort(403, 'Unauthorized approver.');
        }

        DB::transaction(function () use ($dayOffChangeRequest) {
            $dayOffChangeRequest->update([
                'status'        => 'approved',
                'approver_id'   => auth()->id(),
                'approval_date' => Carbon::now('Asia/Manila'),
            ]);
        });

        $employeeUser = $dayOffChangeRequest->employee->user;

        if ($employeeUser && $employeeUser->email) {
            $employeeUser->notify(
                new \App\Notifications\DayOffChangeRequestStatusChanged(
                    $dayOffChangeRequest, 
                    $dayOffChangeRequest->status
                )
            );
        }

        return redirect()
            ->route('day_off_change_requests.show', $dayOffChangeRequest)
            ->with('success', 'Request approved.');
    }

    /**
     * Reject request.
     */
    public function reject(Request $request, DayOffChangeRequest $dayOffChangeRequest)
    {
        $this->authorizeCompany($dayOffChangeRequest->company_id);

        if (auth()->id() !== $dayOffChangeRequest->employee->approver_id) {
            abort(403, 'Unauthorized approver.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($dayOffChangeRequest, $request) {
            $dayOffChangeRequest->update([
                'status'           => 'rejected',
                'approver_id'      => auth()->id(),
                'rejection_reason' => $request->rejection_reason,
            ]);
        });

        $employeeUser = $dayOffChangeRequest->employee->user;

        if ($employeeUser && $employeeUser->email) {
            $employeeUser->notify(
                new \App\Notifications\DayOffChangeRequestStatusChanged(
                    $dayOffChangeRequest, 
                    $dayOffChangeRequest->status
                )
            );
        }

        return redirect()
            ->route('day_off_change_requests.show', $dayOffChangeRequest)
            ->with('success', 'Request rejected.');
    }

    /**
     * Ensure user belongs to company.
     */
    protected function authorizeCompany($companyId)
    {
        if (!auth()->user()->companies->contains('id', $companyId)) {
            abort(403, 'Unauthorized company access.');
        }
    }

    public function byDateRange($employeeId, $start, $end)
    {
        $requests = DayOffChangeRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('old_date', [$start, $end])
                ->orWhereBetween('new_date', [$start, $end]);
            })
            ->get();

        $map = [];

        foreach ($requests as $req) {
            $map[$req->old_date->toDateString()][] =
                "Rest day moved to {$req->new_date->format('M d')}";

            $map[$req->new_date->toDateString()][] =
                "Offset rest day (from {$req->old_date->format('M d')})";
        }

        return response()->json(
            collect($map)->map(fn ($v) => implode(', ', $v))
        );
    }
}
