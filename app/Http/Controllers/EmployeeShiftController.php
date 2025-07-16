<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class EmployeeShiftController extends Controller
{
    /**
     * Display a listing of the employee shifts for the active company.
     */
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('employee_shift.browse')) {
            abort(403, 'Unauthorized to browse employee shifts.');
        }

        $companyId = auth()->user()->preference->company_id;

        $query = EmployeeShift::with(['employee', 'shift'])
            ->where('company_id', $companyId);

        if ($request->filled('employee_name')) {
            $searchTerms = preg_split('/\s+/', $request->input('employee_name'), -1, PREG_SPLIT_NO_EMPTY);

            $query->whereHas('employee', function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->where(function ($sub) use ($term) {
                        $sub->where('first_name', 'like', '%' . $term . '%')
                            ->orWhere('last_name', 'like', '%' . $term . '%');
                    });
                }
            });
        }

        $employeeShifts = $query->paginate(10)->appends($request->query());

        return view('employee_shifts.index', compact('employeeShifts'));
    }

    /**
     * Show the form for creating a new employee shift.
     */
    public function create()
    {
        if (!auth()->user()->hasPermission('employee_shift.create')) {
            abort(403, 'Unauthorized to create employee shift.');
        }

        $companyId = auth()->user()->preference->company_id;

        $employees = Employee::where('company_id', $companyId)->get();
        $shifts    = Shift::where('company_id', $companyId)->get();

        return view('employee_shifts.create', compact('employees', 'shifts'));
    }

    /**
     * Store a newly created employee shift in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('employee_shift.create')) {
            abort(403, 'Unauthorized to create employee shift.');
        }

        $companyId = auth()->user()->preference->company_id;

        $validated = $request->validate([
            'employee_id' => [
                'required',
                'exists:employees,id',
                Rule::unique('employee_shifts', 'employee_id')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                }),
            ],
            'shift_id' => 'required|exists:shifts,id',
        ], [
            'employee_id.unique' => 'This employee has already been assigned a shift. Only one shift is allowed per employee.',
        ]);

        DB::beginTransaction();

        try {
            $employeeShift = EmployeeShift::create([
                'company_id'  => $companyId,
                'employee_id' => $validated['employee_id'],
                'shift_id'    => $validated['shift_id'],
            ]);

            Log::info('Employee shift assigned', [
                'employee_shift_id' => $employeeShift->id,
                'user_id'           => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('employee_shifts.index')->with('success', 'Shift assigned to employee.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to assign shift to employee', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while assigning the shift.');
        }
    }

    /**
     * Display the specified employee shift.
     */
    public function show(EmployeeShift $employeeShift)
    {
        $this->authorizeCompany($employeeShift->company_id);

        if (!auth()->user()->hasPermission('employee_shift.read')) {
            abort(403, 'Unauthorized to view employee shift.');
        }

        $employeeShift->load(['employee', 'shift']);

        return view('employee_shifts.show', compact('employeeShift'));
    }

    /**
     * Show the form for editing the specified employee shift.
     */
    public function edit(EmployeeShift $employeeShift)
    {
        $this->authorizeCompany($employeeShift->company_id);

        if (!auth()->user()->hasPermission('employee_shift.update')) {
            abort(403, 'Unauthorized to edit employee shift.');
        }

        $companyId = auth()->user()->preference->company_id;
        $employees = Employee::where('company_id', $companyId)->get();
        $shifts    = Shift::where('company_id', $companyId)->get();

        return view('employee_shifts.edit', compact('employeeShift', 'employees', 'shifts'));
    }

    /**
     * Update the specified employee shift in storage.
     */
    public function update(Request $request, EmployeeShift $employeeShift)
    {
        $this->authorizeCompany($employeeShift->company_id);

        if (!auth()->user()->hasPermission('employee_shift.update')) {
            abort(403, 'Unauthorized to update employee shift.');
        }

        $companyId = auth()->user()->preference->company_id;

        $validated = $request->validate([
            'employee_id' => [
                'required',
                'exists:employees,id',
                Rule::unique('employee_shifts', 'employee_id')
                    ->where(function ($query) use ($companyId) {
                        return $query->where('company_id', $companyId);
                    })
                    ->ignore($employeeShift->id),
            ],
            'shift_id' => 'required|exists:shifts,id',
        ], [
            'employee_id.unique' => 'This employee has already been assigned a shift. Only one shift is allowed per employee.',
        ]);

        DB::beginTransaction();

        try {
            $employeeShift->update([
                'employee_id' => $validated['employee_id'],
                'shift_id'    => $validated['shift_id'],
            ]);

            DB::commit();

            Log::info('Employee shift updated', [
                'employee_shift_id' => $employeeShift->id,
                'user_id'           => auth()->id(),
            ]);

            return redirect()->route('employee_shifts.index')->with('success', 'Employee shift updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update employee shift', [
                'error'             => $e->getMessage(),
                'employee_shift_id' => $employeeShift->id,
                'user_id'           => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while updating the employee shift.');
        }
    }

    /**
     * Remove the specified employee shift from storage.
     */
    public function destroy(EmployeeShift $employeeShift)
    {
        $this->authorizeCompany($employeeShift->company_id);

        if (!auth()->user()->hasPermission('employee_shift.delete')) {
            abort(403, 'Unauthorized to delete employee shift.');
        }

        DB::beginTransaction();

        try {
            $employeeShift->delete();

            DB::commit();

            Log::info('Employee shift deleted', [
                'employee_shift_id' => $employeeShift->id,
                'user_id'           => auth()->id(),
            ]);

            return redirect()->route('employee_shifts.index')->with('success', 'Employee shift deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete employee shift', [
                'error'             => $e->getMessage(),
                'employee_shift_id' => $employeeShift->id,
                'user_id'           => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while deleting the employee shift.');
        }
    }

    /**
     * Ensure the authenticated user belongs to the given company ID.
     */
    protected function authorizeCompany($companyId)
    {
        if (!auth()->user()->companies->contains('id', $companyId)) {
            abort(403, 'Unauthorized');
        }
    }
}
