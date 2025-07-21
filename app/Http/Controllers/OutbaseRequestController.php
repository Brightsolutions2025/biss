<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\OutbaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OutbaseRequestController extends Controller
{
    /**
     * Display a listing of the outbase requests for the active company.
     */
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('outbase_request.browse')) {
            abort(403, 'Unauthorized to browse outbase requests.');
        }

        $user      = auth()->user();
        $companyId = $user->preference->company_id;

        $query = OutbaseRequest::with('employee.user')
            ->where('company_id', $companyId);

        if (!$user->hasPermission('outbase_request.browse_all')) {
            $employeeId = $user->employee?->id;

            if (!$employeeId) {
                abort(403, 'No employee record linked to this user.');
            }

            // Get IDs of subordinates
            $subordinateIds = Employee::where('approver_id', $employeeId)
                ->pluck('id')
                ->toArray();

            // Limit to own or subordinates' outbase requests
            $query->where(function ($q) use ($employeeId, $subordinateIds) {
                $q->where('employee_id', $employeeId)
                ->orWhereIn('employee_id', $subordinateIds);
            });
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('date', [$request->input('date_from'), $request->input('date_to')]);
        }

        $outbaseRequests = $query->orderByDesc('date')->paginate(20)->appends($request->query());

        $employeeList = Employee::where('company_id', $companyId)
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get();

        return view('outbase_requests.index', compact('outbaseRequests', 'employeeList'));
    }

    /**
     * Show the form for creating a new outbase request.
     */
    public function create()
    {
        if (!auth()->user()->hasPermission('outbase_request.create')) {
            abort(403, 'Unauthorized to create outbase requests.');
        }

        $companyId = auth()->user()->preference->company_id;

        $employee = Employee::where('company_id', $companyId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return view('outbase_requests.create', compact('employee'));
    }

    /**
     * Store a newly created outbase request in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('outbase_request.create')) {
            abort(403, 'Unauthorized to create outbase requests.');
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date'        => 'required|date',
            'time_start'  => 'required|date_format:H:i',
            'time_end'    => 'required|date_format:H:i|after:time_start',
            'location'    => 'required|string|max:255',
            'reason'      => 'required|string',
            'files'       => 'array|max:5',
            'files.*'     => 'file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx,xlsx',
        ]);

        DB::beginTransaction();

        try {
            $companyId = auth()->user()->preference->company_id;
            $employee  = Employee::findOrFail($validated['employee_id']);

            $outbase = OutbaseRequest::create([
                'company_id'  => $companyId,
                'employee_id' => $validated['employee_id'],
                'date'        => $validated['date'],
                'time_start'  => $validated['time_start'],
                'time_end'    => $validated['time_end'],
                'location'    => $validated['location'],
                'reason'      => $validated['reason'],
                'status'      => 'pending',
                'approver_id' => $employee->approver_id,
            ]);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $uploadedFile) {
                    $path = $uploadedFile->store('uploads/outbase_request_files');

                    $outbase->files()->create([
                        'file_path' => $path,
                        'file_name' => $uploadedFile->getClientOriginalName(),
                    ]);
                }
            }

            $approver = $outbase->employee->approver;
            if ($approver && $approver->email) {
                $approver->notify(new \App\Notifications\OutbaseRequestSubmitted($outbase));
            }

            DB::commit();

            Log::info('Outbase request created', [
                'outbase_request_id' => $outbase->id,
                'user_id'            => auth()->id()
            ]);

            return redirect()->route('outbase_requests.index')->with('success', 'Outbase request submitted.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create outbase request', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()->withErrors($e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified outbase request.
     */
    public function show(OutbaseRequest $outbaseRequest)
    {
        $user = auth()->user();

        $this->authorizeCompany($outbaseRequest->company_id);

        if (!auth()->user()->hasPermission('outbase_request.read')) {
            abort(403, 'Unauthorized to view outbase request.');
        }

        $employeeId = $user->employee?->id;

        if (!$user->hasPermission('outbase_request.browse_all')) {
            $isOwner = $outbaseRequest->employee_id === $employeeId;

            // Check if current user is the approver of the employee in this request
            $isApprover = $outbaseRequest->employee->approver_id === $employeeId;

            if (!$isOwner && !$isApprover) {
                abort(403, 'You are not allowed to view this outbase request.');
            }
        }

        $outbaseRequest->load('employee.user');

        return view('outbase_requests.show', compact('outbaseRequest'));
    }

    /**
     * Show the form for editing the specified outbase request.
     */
    public function edit(OutbaseRequest $outbaseRequest)
    {
        $user = auth()->user();

        $this->authorizeCompany($outbaseRequest->company_id);

        if (!$user->hasPermission('outbase_request.update')) {
            abort(403, 'Unauthorized to edit outbase request.');
        }

        if (!$this->canEditOutbaseRequest($outbaseRequest)) {
            abort(403, 'You are not allowed to edit this outbase request.');
        }

        // If user doesn't have 'browse_all', enforce ownership or approver rights
        if (!$user->hasPermission('outbase_request.browse_all')) {
            $employeeId = $user->employee?->id;
            $isOwner    = $employeeId && $outbaseRequest->employee_id === $employeeId;
            $isApprover = auth()->id()                                === $outbaseRequest->employee->approver_id;

            if (!$isOwner && !$isApprover) {
                abort(403, 'You are not allowed to edit this outbase request.');
            }
        }

        return view('outbase_requests.edit', compact('outbaseRequest'));
    }

    protected function canEditOutbaseRequest(OutbaseRequest $outbaseRequest): bool
    {
        $user       = auth()->user();
        $employeeId = $user->employee?->id;
        $isOwner    = $employeeId  === $outbaseRequest->employee_id;
        $isApprover = auth()->id() === $outbaseRequest->employee->approver_id;

        // Allow approver to edit at any status; employee only if pending
        if ($isApprover) {
            return true;
        }

        return $isOwner && !in_array($outbaseRequest->status, ['approved', 'rejected']);
    }

    /**
     * Update the specified outbase request in storage.
     */
    public function update(Request $request, OutbaseRequest $outbaseRequest)
    {
        $this->authorizeCompany($outbaseRequest->company_id);

        if (!auth()->user()->hasPermission('outbase_request.update')) {
            abort(403, 'Unauthorized to edit outbase request.');
        }

        if (! $this->canEditOutbaseRequest($outbaseRequest)) {
            abort(403, 'You are not allowed to edit this outbase request.');
        }

        DB::beginTransaction();

        try {
            $companyId = auth()->user()->preference->company_id;

            $validated = $request->validate([
                'employee_id'      => 'required|exists:employees,id',
                'date'             => 'required|date',
                'time_start'       => 'required|date_format:H:i',
                'time_end'         => 'required|date_format:H:i|after:time_start',
                'location'         => 'required|string|max:255',
                'reason'           => 'required|string',
                'files'            => 'array|max:5',
                'files.*'          => 'file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx,xlsx',
            ]);

            $employee = Employee::findOrFail($validated['employee_id']);

            unset($validated['files']);

            $outbaseRequest->update(array_merge($validated, [
                'approver_id' => $employee->approver_id,
            ]));

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $uploadedFile) {
                    $path = $uploadedFile->store('uploads/outbase_request_files');

                    $outbaseRequest->files()->create([
                        'file_path' => $path,
                        'file_name' => $uploadedFile->getClientOriginalName(),
                    ]);
                }
            }

            $approver = $outbaseRequest->employee->approver;
            if ($approver && $approver->email) {
                $approver->notify(new \App\Notifications\OutbaseRequestSubmitted($outbaseRequest));
            }

            DB::commit();

            Log::info('Outbase request updated', [
                'outbase_request_id' => $outbaseRequest->id,
                'user_id'            => auth()->id()
            ]);

            return redirect()->route('outbase_requests.index')->with('success', 'Outbase request updated.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update outbase request', [
                'error'              => $e->getMessage(),
                'outbase_request_id' => $outbaseRequest->id,
                'user_id'            => auth()->id()
            ]);

            return back()->withErrors($e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified outbase request from storage.
     */
    public function destroy(OutbaseRequest $outbaseRequest)
    {
        $user = auth()->user();

        $this->authorizeCompany($outbaseRequest->company_id);

        if (!auth()->user()->hasPermission('outbase_request.delete')) {
            abort(403, 'Unauthorized to delete outbase request.');
        }

        if (!$user->hasPermission('outbase_request.browse_all')) {
            $employeeId = $user->employee?->id;

            // Check if user is the owner
            if (!$employeeId || $outbaseRequest->employee_id !== $employeeId) {
                abort(403, 'You are not allowed to delete this outbase request.');
            }

            // Prevent deletion if the request is already approved or rejected
            if (in_array($outbaseRequest->status, ['approved', 'rejected'])) {
                abort(403, 'You cannot delete an outbase request that has already been approved or rejected.');
            }
        }


        DB::beginTransaction();

        try {
            // Delete associated files
            foreach ($outbaseRequest->files as $file) {
                if (\Storage::exists($file->file_path)) {
                    \Storage::delete($file->file_path);
                }

                $file->delete(); // delete record from `files` table
            }

            $outbaseRequest->delete();

            DB::commit();

            Log::info('Outbase request deleted', [
                'outbase_request_id' => $outbaseRequest->id,
                'user_id'            => auth()->id()
            ]);

            return redirect()->route('outbase_requests.index')->with('success', 'Outbase request deleted.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete outbase request', [
                'error'              => $e->getMessage(),
                'outbase_request_id' => $outbaseRequest->id,
                'user_id'            => auth()->id()
            ]);

            return back()->withErrors('An error occurred while deleting the request.');
        }
    }

    /**
     * Private helper to ensure user belongs to the company.
     */
    protected function authorizeCompany($companyId)
    {
        if (!auth()->user()->companies->contains('id', $companyId)) {
            abort(403, 'Unauthorized');
        }
    }
    public function approve(Request $request, OutbaseRequest $outbaseRequest)
    {
        $this->authorizeCompany($outbaseRequest->company_id);

        $approverId = $outbaseRequest->employee->approver_id;

        if (is_null($approverId)) {
            abort(403, 'No approver is assigned to this employee.');
        }

        if (auth()->id() !== $approverId) {
            abort(403, 'Unauthorized: You are not the assigned approver.');
        }

        DB::beginTransaction();

        try {
            $outbaseRequest->status        = 'approved';
            $outbaseRequest->approver_id   = auth()->id();
            $outbaseRequest->approval_date = Carbon::now('Asia/Manila');
            $outbaseRequest->save();

            $employeeUser = $outbaseRequest->employee->user;
            if ($employeeUser && $employeeUser->email) {
                $employeeUser->notify(new \App\Notifications\OutbaseRequestStatusChanged($outbaseRequest, 'approved'));
            }

            DB::commit();

            Log::info('Outbase request approved', [
                'outbase_request_id' => $outbaseRequest->id,
                'approver_id'        => auth()->id(),
            ]);

            return redirect()->route('outbase_requests.show', $outbaseRequest->id)
                            ->with('success', 'Outbase request approved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to approve outbase request', [
                'error'              => $e->getMessage(),
                'outbase_request_id' => $outbaseRequest->id,
                'approver_id'        => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while approving the request.');
        }
    }
    public function reject(Request $request, OutbaseRequest $outbaseRequest)
    {
        $this->authorizeCompany($outbaseRequest->company_id);

        $approverId = $outbaseRequest->employee->approver_id;

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
            $outbaseRequest->status           = 'rejected';
            $outbaseRequest->approver_id      = auth()->id();
            $outbaseRequest->rejection_reason = $request->input('reason');
            $outbaseRequest->save();

            $employeeUser = $outbaseRequest->employee->user;
            if ($employeeUser && $employeeUser->email) {
                $employeeUser->notify(new \App\Notifications\OutbaseRequestStatusChanged($outbaseRequest, 'rejected'));
            }

            DB::commit();

            Log::info('Outbase request rejected', [
                'outbase_request_id' => $outbaseRequest->id,
                'approver_id'        => auth()->id(),
                'reason'             => $request->input('reason'),
            ]);

            return redirect()->route('outbase_requests.show', $outbaseRequest->id)
                            ->with('success', 'Outbase request rejected successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to reject outbase request', [
                'error'              => $e->getMessage(),
                'outbase_request_id' => $outbaseRequest->id,
                'approver_id'        => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while rejecting the request.');
        }
    }
    // OutbaseRequestController
    public function fetchApprovedByDate($employeeId, $start, $end)
    {
        $requests = OutbaseRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereBetween('date', [$start, $end])
            ->get()
            ->groupBy('date')
            ->mapWithKeys(function ($group, $date) {
                $earliestStart = $group->min('time_start');
                $latestEnd     = $group->max('time_end');

                return [$date => [
                    'start' => $earliestStart ? Carbon::parse($earliestStart)->format('H:i') : null,
                    'end'   => $latestEnd ? Carbon::parse($latestEnd)->format('H:i') : null,
                ]];
            });

        return response()->json($requests);
    }
}
