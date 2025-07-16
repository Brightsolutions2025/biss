<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('employee.browse')) {
            abort(403, 'Unauthorized to browse employees.');
        }

        $companyId = auth()->user()->preference->company_id;

        $query = Employee::with(['user', 'department', 'team', 'approver'])
            ->where('company_id', $companyId);

        if ($request->filled('name')) {
            $searchTerms = preg_split('/\s+/', $request->input('name'), -1, PREG_SPLIT_NO_EMPTY);

            $query->where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->where(function ($subQ) use ($term) {
                        $subQ->where('first_name', 'like', '%' . $term . '%')
                            ->orWhere('last_name', 'like', '%' . $term . '%');
                    });
                }
            });
        }

        $employees = $query->paginate(10)->appends($request->query());

        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('employee.create')) {
            abort(403, 'Unauthorized to create employees.');
        }

        $companyId   = auth()->user()->preference->company_id;
        $departments = Department::where('company_id', $companyId)->get();
        $teams       = Team::whereIn('department_id', $departments->pluck('id'))->get();
        $users = User::whereDoesntHave('employee', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->get();
        $approvers   = User::with('employee')->whereHas('employee', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->get();

        return view('employees.create', compact('departments', 'teams', 'users', 'approvers'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('employee.create')) {
            abort(403, 'Unauthorized to create employees.');
        }

        $companyId = auth()->user()->preference->company_id;

        $validated = $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('employees')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                }),
            ],
            'approver_id'       => 'nullable|exists:users,id',
            'employee_number'   => [
                'required',
                'string',
                'max:255',
                Rule::unique('employees')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                }),
            ],
            'first_name'        => 'required|string|max:255',
            'last_name'         => 'required|string|max:255',
            'middle_name'       => 'nullable|string|max:255',
            'gender'            => 'nullable|string|max:10',
            'birth_date'        => 'nullable|date',
            'civil_status'      => 'nullable|string|max:50',
            'nationality'       => 'nullable|string|max:100',
            'position'          => 'nullable|string|max:255',
            'department_id'     => 'nullable|exists:departments,id',
            'team_id'           => 'nullable|exists:teams,id',
            'employment_type'   => 'nullable|string|max:100',
            'flexible_time'     => 'boolean',
            'hire_date'         => 'nullable|date',
            'termination_date'  => 'nullable|date',
            'basic_salary'      => 'nullable|numeric|min:0',
            'sss_number'        => 'nullable|string|max:50',
            'philhealth_number' => 'nullable|string|max:50',
            'pagibig_number'    => 'nullable|string|max:50',
            'tin_number'        => 'nullable|string|max:50',
            'address'           => 'nullable|string',
            'contact_number'    => 'nullable|string|max:20',
            'emergency_contact' => 'nullable|string|max:255',
            'notes'             => 'nullable|string',
            'ot_not_convertible_to_offset' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            $validated['flexible_time'] = $request->has('flexible_time'); // checkbox handling
            $validated['ot_not_convertible_to_offset'] = $request->has('ot_not_convertible_to_offset');

            $employee             = new Employee($validated);
            $employee->company_id = $companyId;
            $employee->save();

            DB::commit();

            Log::info('Employee created', ['employee_id' => $employee->id, 'user_id' => auth()->id()]);

            return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create employee', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while creating the employee.')->withInput();
        }
    }

    public function show(Employee $employee)
    {
        $this->authorizeCompany($employee->company_id);

        if (!auth()->user()->hasPermission('employee.read')) {
            abort(403, 'Unauthorized to view employee.');
        }

        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $this->authorizeCompany($employee->company_id);

        if (!auth()->user()->hasPermission('employee.update')) {
            abort(403, 'Unauthorized to edit employee.');
        }

        $companyId   = auth()->user()->preference->company_id;
        $departments = Department::where('company_id', $companyId)->get();
        $teams       = Team::whereIn('department_id', $departments->pluck('id'))->get();
        $company     = Company::findOrFail($companyId);
        $users       = $company->users()->orderBy('name')->get();
        $approvers   = $users; // Approver can be any employee within the company

        return view('employees.edit', compact('employee', 'departments', 'teams', 'users', 'approvers'));
    }

    public function update(Request $request, Employee $employee)
    {
        $companyId = auth()->user()->preference->company_id;

        if (!auth()->user()->hasPermission('employee.update')) {
            abort(403, 'Unauthorized to edit employee.');
        }

        $this->authorizeCompany($employee->company_id);

        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'user_id' => [
                    'required',
                    'exists:users,id',
                    Rule::unique('employees')
                        ->where(function ($query) use ($companyId) {
                            return $query->where('company_id', $companyId);
                        })
                        ->ignore($employee->id),
                ],
                'approver_id'       => 'nullable|exists:users,id',
                'employee_number'   => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('employees')
                        ->where(function ($query) use ($companyId) {
                            return $query->where('company_id', $companyId);
                        })
                        ->ignore($employee->id),
                ],
                'first_name'        => 'required|string|max:255',
                'last_name'         => 'required|string|max:255',
                'middle_name'       => 'nullable|string|max:255',
                'gender'            => 'nullable|string|max:10',
                'birth_date'        => 'nullable|date',
                'civil_status'      => 'nullable|string|max:50',
                'nationality'       => 'nullable|string|max:100',
                'position'          => 'nullable|string|max:255',
                'department_id'     => 'nullable|exists:departments,id',
                'team_id'           => 'nullable|exists:teams,id',
                'employment_type'   => 'nullable|string|max:100',
                'flexible_time'     => 'boolean',
                'hire_date'         => 'nullable|date',
                'termination_date'  => 'nullable|date',
                'basic_salary'      => 'nullable|numeric|min:0',
                'sss_number'        => 'nullable|string|max:50',
                'philhealth_number' => 'nullable|string|max:50',
                'pagibig_number'    => 'nullable|string|max:50',
                'tin_number'        => 'nullable|string|max:50',
                'address'           => 'nullable|string',
                'contact_number'    => 'nullable|string|max:20',
                'emergency_contact' => 'nullable|string|max:255',
                'notes'             => 'nullable|string',
                'ot_not_convertible_to_offset' => 'boolean',
            ]);

            $validated['flexible_time'] = $request->has('flexible_time'); // checkbox handling
            $validated['ot_not_convertible_to_offset'] = $request->has('ot_not_convertible_to_offset');

            $employee->fill($validated);
            $employee->company_id = $companyId;
            $employee->save();

            DB::commit();

            Log::info('Employee updated', ['employee_id' => $employee->id, 'user_id' => auth()->id()]);

            return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update employee', [
                'error'       => $e->getMessage(),
                'employee_id' => $employee->id,
                'user_id'     => auth()->id(),
            ]);

            return back()->withErrors($e->getMessage())->withInput();
        }
    }

    public function destroy(Employee $employee)
    {
        $this->authorizeCompany($employee->company_id);

        if (!auth()->user()->hasPermission('employee.delete')) {
            abort(403, 'Unauthorized to delete employee.');
        }

        DB::beginTransaction();

        try {
            $employee->delete();

            DB::commit();

            Log::info('Employee deleted', ['employee_id' => $employee->id, 'user_id' => auth()->id()]);

            return redirect()->route('employees.index')->with('success', 'Employee removed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete employee', [
                'error'       => $e->getMessage(),
                'employee_id' => $employee->id,
                'user_id'     => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while deleting the employee.');
        }
    }

    protected function authorizeCompany($companyId)
    {
        if (auth()->user()->preference->company_id != $companyId) {
            abort(403, 'Unauthorized');
        }
    }
}
