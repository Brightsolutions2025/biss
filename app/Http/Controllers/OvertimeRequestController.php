<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\OvertimeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class OvertimeRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!auth()->user()->hasPermission('overtime_request.browse')) {
            abort(403, 'Unauthorized to browse overtime requests.');
        }

        $companyId = $user->preference->company_id;

        $query = OvertimeRequest::with('employee')
            ->where('company_id', $companyId);

        if (!$user->hasPermission('overtime_request.browse_all')) {
            $employeeId = $user->employee?->id;

            if (!$employeeId) {
                abort(403, 'No employee record linked to this user.');
            }

            // Allow own and subordinates' overtime requests
            $subordinateIds = Employee::where('approver_id', $employeeId)
                ->pluck('id')
                ->toArray();

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
            $query->whereBetween('date', [$request->date_from, $request->date_to]);
        }

        $overtimeRequests = $query->orderByDesc('date')->paginate(20)->appends($request->query());

        $employeeList = Employee::where('company_id', $companyId)
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get();

        return view('overtime_requests.index', compact('overtimeRequests', 'employeeList'));
    }


    public function create()
    {
        if (!auth()->user()->hasPermission('overtime_request.create')) {
            abort(403, 'Unauthorized to create overtime requests.');
        }

        $companyId = auth()->user()->preference->company_id;
        $employees = Employee::where('company_id', $companyId)->get();

        return view('overtime_requests.create', compact('employees'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('overtime_request.create')) {
            abort(403, 'Unauthorized to create overtime requests.');
        }

        $companyId = auth()->user()->preference->company_id;

        $validated = $request->validate([
            'employee_id'     => 'required|exists:employees,id',
            'date'            => 'required|date',
            'time_start'      => 'required|date_format:H:i',
            'time_end'        => 'required|date_format:H:i|after:time_start',
            'number_of_hours' => 'required|numeric|min:0.25',
            'reason'          => 'required|string',
            'files' => 'array|max:5',
            'files.*' => 'file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx,xlsx',
        ]);

        // Custom validation: number_of_hours must not exceed actual duration
        $start = \Carbon\Carbon::createFromFormat('H:i', $validated['time_start']);
        $end = \Carbon\Carbon::createFromFormat('H:i', $validated['time_end']);
        $maxHours = $start->diffInMinutes($end) / 60;

        if ($validated['number_of_hours'] > $maxHours) {
            return back()->withErrors(['number_of_hours' => 'Number of hours exceeds the actual time duration.'])
                        ->withInput();
        }

        DB::beginTransaction();

        try {
            $overtimeRequest = OvertimeRequest::create([
                'company_id'      => $companyId,
                'employee_id'     => $validated['employee_id'],
                'date'            => $validated['date'],
                'time_start'      => $validated['time_start'],
                'time_end'        => $validated['time_end'],
                'number_of_hours' => $validated['number_of_hours'],
                'reason'          => $validated['reason'],
                'status'          => 'pending',
            ]);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $uploadedFile) {
                    $path = $uploadedFile->store('uploads/overtime_request_files');

                    $overtimeRequest->files()->create([
                        'file_path' => $path,
                        'file_name' => $uploadedFile->getClientOriginalName(),
                    ]);
                }
            }

            $approver = $overtimeRequest->employee->approver;

            if ($approver && $approver->email) {
                $approver->notify(new \App\Notifications\OvertimeRequestSubmitted($overtimeRequest));
            }

            DB::commit();

            Log::info('Overtime request created', [
                'overtime_request_id' => $overtimeRequest->id,
                'user_id'             => auth()->id(),
            ]);

            return redirect()->route('overtime_requests.index')->with('success', 'Overtime request submitted.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create overtime request', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors($e->getMessage());
        }
    }

    public function show(OvertimeRequest $overtimeRequest)
    {
        $user = auth()->user();

        // Ensure the user is in the same company
        $this->authorizeCompany($overtimeRequest->company_id);

        // Must have read permission
        if (!auth()->user()->hasPermission('overtime_request.read')) {
            abort(403, 'Unauthorized to view overtime requests.');
        }

        $employeeId = $user->employee?->id;

        // If user lacks global permission, allow if:
        // - they own the request
        // - OR they are the approver of the employee
        if (!$user->hasPermission('overtime_request.browse_all')) {
            $isOwner = $overtimeRequest->employee_id === $employeeId;
            $isApprover = $overtimeRequest->employee->approver_id === $employeeId;

            if (!$isOwner && !$isApprover) {
                abort(403, 'You are not allowed to view this overtime request.');
            }
        }

        return view('overtime_requests.show', compact('overtimeRequest'));
    }

    public function edit(OvertimeRequest $overtimeRequest)
    {
        $user = auth()->user();

        $this->authorizeCompany($overtimeRequest->company_id);

        if (!auth()->user()->hasPermission('overtime_request.update')) {
            abort(403, 'Unauthorized to edit overtime requests.');
        }

        if (!$this->canEditOvertimeRequest($overtimeRequest)) {
            abort(403, 'You are not allowed to edit this overtime request.');
        }

        // If user doesn't have 'browse_all', enforce ownership or approver rights
        if (!$user->hasPermission('overtime_request.browse_all')) {
            $employeeId = $user->employee?->id;
            $isOwner = $employeeId && $overtimeRequest->employee_id === $employeeId;
            $isApprover = $user->id === $overtimeRequest->employee->approver_id;

            if (!$isOwner && !$isApprover) {
                abort(403, 'You are not allowed to edit this overtime request.');
            }
        }

        $companyId = auth()->user()->preference->company_id;

        $employees = Employee::where('company_id', $companyId)->get();

        return view('overtime_requests.edit', compact('overtimeRequest', 'employees'));
    }

    protected function canEditOvertimeRequest(OvertimeRequest $overtimeRequest): bool
    {
        $user = auth()->user();
        $employeeId = $user->employee?->id;

        $isOwner = $employeeId === $overtimeRequest->employee_id;
        $isApprover = $user->id === $overtimeRequest->employee->approver_id;

        // Approver can always edit, employee only if not yet approved/rejected
        if ($isApprover) {
            return true;
        }

        return $isOwner && !in_array($overtimeRequest->status, ['approved', 'rejected']);
    }

    public function update(Request $request, OvertimeRequest $overtimeRequest)
    {
        $this->authorizeCompany($overtimeRequest->company_id);

        if (!auth()->user()->hasPermission('overtime_request.update')) {
            abort(403, 'Unauthorized to edit overtime requests.');
        }

        if (!$this->canEditOvertimeRequest($overtimeRequest)) {
            abort(403, 'You are not allowed to edit this overtime request.');
        }

        $validated = $request->validate([
            'employee_id'     => 'required|exists:employees,id',
            'date'            => 'required|date',
            'time_start'      => 'required|date_format:H:i',
            'time_end'        => 'required|date_format:H:i|after:time_start',
            'number_of_hours' => 'required|numeric|min:0.25',
            'reason'          => 'required|string',
            'files' => 'array|max:5',
            'files.*' => 'file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx,xlsx',
        ]);

        // Custom check: number_of_hours <= time difference
        $start = \Carbon\Carbon::createFromFormat('H:i', $validated['time_start']);
        $end = \Carbon\Carbon::createFromFormat('H:i', $validated['time_end']);
        $maxHours = $start->diffInMinutes($end) / 60;

        if ($validated['number_of_hours'] > $maxHours) {
            return back()->withErrors(['number_of_hours' => 'Number of hours exceeds the actual time duration.'])
                        ->withInput();
        }

        DB::beginTransaction();

        try {
            unset($validated['files']);

            $overtimeRequest->update($validated);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $uploadedFile) {
                    $path = $uploadedFile->store('uploads/overtime_request_files');

                    $overtimeRequest->files()->create([
                        'file_path' => $path,
                        'file_name' => $uploadedFile->getClientOriginalName(),
                    ]);
                }
            }

            $approver = $overtimeRequest->employee->approver;

            if ($approver && $approver->email) {
                $approver->notify(new \App\Notifications\OvertimeRequestSubmitted($overtimeRequest));
            }

            DB::commit();

            Log::info('Overtime request updated', [
                'overtime_request_id' => $overtimeRequest->id,
                'user_id'             => auth()->id(),
            ]);

            return redirect()->route('overtime_requests.index')->with('success', 'Overtime request updated.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update overtime request', [
                'error'               => $e->getMessage(),
                'overtime_request_id' => $overtimeRequest->id,
                'user_id'             => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while updating the request.');
        }
    }

    public function destroy(OvertimeRequest $overtimeRequest)
    {
        $user = auth()->user();

        $this->authorizeCompany($overtimeRequest->company_id);

        if (!auth()->user()->hasPermission('overtime_request.delete')) {
            abort(403, 'Unauthorized to delete overtime requests.');
        }

        // Ownership or approver check if user lacks global permission
        if (!$user->hasPermission('overtime_request.browse_all')) {
            $employeeId = $user->employee?->id;

            if (!$employeeId || $overtimeRequest->employee_id !== $employeeId) {
                abort(403, 'You are not allowed to delete this overtime request.');
            }

            // Block deletion if request has already been processed
            if (in_array($overtimeRequest->status, ['approved', 'rejected'])) {
                abort(403, 'You cannot delete an overtime request that has already been approved or rejected.');
            }
        }

        DB::beginTransaction();

        try {
            foreach ($overtimeRequest->files as $file) {
                if (\Storage::exists($file->file_path)) {
                    \Storage::delete($file->file_path);
                }

                $file->delete();
            }
            
            $overtimeRequest->delete();

            DB::commit();

            Log::info('Overtime request deleted', [
                'overtime_request_id' => $overtimeRequest->id,
                'user_id'             => auth()->id(),
            ]);

            return redirect()->route('overtime_requests.index')->with('success', 'Overtime request deleted.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete overtime request', [
                'error'               => $e->getMessage(),
                'overtime_request_id' => $overtimeRequest->id,
                'user_id'             => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while deleting the request.');
        }
    }
    public function approve(Request $request, OvertimeRequest $overtimeRequest)
    {
        $this->authorizeCompany($overtimeRequest->company_id);

        $approverId = $overtimeRequest->employee->approver_id;

        if (is_null($approverId)) {
            abort(403, 'No approver is assigned to this employee.');
        }

        if (auth()->id() !== $approverId) {
            abort(403, 'Unauthorized: You are not the assigned approver.');
        }

        DB::beginTransaction();

        try {
            $overtimeRequest->status      = 'approved';
            $overtimeRequest->approver_id = auth()->id();
            $overtimeRequest->approval_date = Carbon::now('Asia/Manila');
            $overtimeRequest->save();

            $employeeUser = $overtimeRequest->employee->user;

            if ($employeeUser && $employeeUser->email) {
                $employeeUser->notify(new \App\Notifications\OvertimeRequestStatusChanged($overtimeRequest, 'approved'));
            }

            DB::commit();

            Log::info('Overtime request approved', [
                'overtime_request_id' => $overtimeRequest->id,
                'approver_id'         => auth()->id(),
            ]);

            return redirect()->route('overtime_requests.show', $overtimeRequest->id)
                            ->with('success', 'Overtime request approved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to approve overtime request', [
                'error'               => $e->getMessage(),
                'overtime_request_id' => $overtimeRequest->id,
                'approver_id'         => auth()->id(),
            ]);

            return back()->withErrors($e->getMessage())->withInput();
        }
    }

    public function reject(Request $request, OvertimeRequest $overtimeRequest)
    {
        $this->authorizeCompany($overtimeRequest->company_id);

        $approverId = $overtimeRequest->employee->approver_id;

        if (is_null($approverId)) {
            abort(403, 'No approver is assigned to this employee.');
        }

        if (auth()->id() !== $approverId) {
            abort(403, 'Unauthorized: You are not the assigned approver.');
        }

        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $overtimeRequest->status           = 'rejected';
            $overtimeRequest->approver_id      = auth()->id();
            $overtimeRequest->rejection_reason = $request->input('reason');
            $overtimeRequest->save();

            $employeeUser = $overtimeRequest->employee->user;

            if ($employeeUser && $employeeUser->email) {
                $employeeUser->notify(new \App\Notifications\OvertimeRequestStatusChanged($overtimeRequest, 'rejected'));
            }

            DB::commit();

            Log::info('Overtime request rejected', [
                'overtime_request_id' => $overtimeRequest->id,
                'approver_id'         => auth()->id(),
                'reason'              => $request->input('reason'),
            ]);

            return redirect()->route('overtime_requests.show', $overtimeRequest->id)
                            ->with('success', 'Overtime request rejected successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to reject overtime request', [
                'error'               => $e->getMessage(),
                'overtime_request_id' => $overtimeRequest->id,
                'approver_id'         => auth()->id(),
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
    // OvertimeRequestController
    public function fetchApprovedByDate($employeeId, $start, $end)
    {
        $requests = OvertimeRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereBetween('date', [$start, $end])
            ->get()
            ->groupBy('date')
            ->mapWithKeys(function ($group, $date) {
                return [$date => [
                    'hours' => $group->sum('number_of_hours'),
                    'start' => $group->min('time_start'),
                    'end' => $group->max('time_end'),
                ]];
            });

        return response()->json($requests);
    }
}
