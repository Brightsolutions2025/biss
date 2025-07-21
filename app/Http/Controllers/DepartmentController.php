<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the departments for the active company.
     */
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('department.browse')) {
            abort(403, 'Unauthorized to browse departments.');
        }

        $company = auth()->user()->preference->company;

        $query = Department::where('company_id', $company->id);

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        if ($request->filled('description')) {
            $query->where('description', 'like', '%' . $request->input('description') . '%');
        }

        $departments = $query->paginate(10)->appends($request->query());

        return view('departments.index', compact('departments'));
    }

    /**
     * Show the form for creating a new department.
     */
    public function create()
    {
        if (!auth()->user()->hasPermission('department.create')) {
            abort(403, 'Unauthorized to create departments.');
        }

        $company = auth()->user()->preference->company;
        $users   = User::whereHas('employee', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })->get();

        return view('departments.create', compact('users'));
    }

    /**
     * Store a newly created department in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('department.create')) {
            abort(403, 'Unauthorized to create departments.');
        }

        $companyId = auth()->user()->preference->company_id;

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'head_id'     => 'nullable|exists:users,id',
        ]);

        DB::beginTransaction();

        try {
            $department = Department::create([
                'company_id'  => $companyId,
                'name'        => $validated['name'],
                'description' => $validated['description'] ?? null,
                'head_id'     => $validated['head_id']     ?? null,
            ]);

            DB::commit();

            Log::info('Department created', ['department_id' => $department->id, 'user_id' => auth()->id()]);

            return redirect()->route('departments.index')->with('success', 'Department created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create department', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while creating the department.');
        }
    }

    /**
     * Display the specified department.
     */
    public function show(Department $department)
    {
        $this->authorizeDepartment($department);

        if (!auth()->user()->hasPermission('department.read')) {
            abort(403, 'Unauthorized to view department.');
        }

        return view('departments.show', compact('department'));
    }

    /**
     * Show the form for editing the specified department.
     */
    public function edit(Department $department)
    {
        $this->authorizeDepartment($department);

        if (!auth()->user()->hasPermission('department.update')) {
            abort(403, 'Unauthorized to edit department.');
        }

        $company = auth()->user()->preference->company;
        $users   = User::whereHas('employee', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })->get();

        return view('departments.edit', compact('department', 'users'));
    }

    /**
     * Update the specified department in storage.
     */
    public function update(Request $request, Department $department)
    {
        $this->authorizeDepartment($department);

        if (!auth()->user()->hasPermission('department.update')) {
            abort(403, 'Unauthorized to update department.');
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'head_id'     => 'nullable|exists:users,id',
        ]);

        DB::beginTransaction();

        try {
            $department->update($validated);

            DB::commit();

            Log::info('Department updated', ['department_id' => $department->id, 'user_id' => auth()->id()]);

            return redirect()->route('departments.index')->with('success', 'Department updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update department', [
                'error'         => $e->getMessage(),
                'department_id' => $department->id,
                'user_id'       => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while updating the department.');
        }
    }

    /**
     * Remove the specified department from storage.
     */
    public function destroy(Department $department)
    {
        $this->authorizeDepartment($department);

        if (!auth()->user()->hasPermission('department.delete')) {
            abort(403, 'Unauthorized to delete department.');
        }

        DB::beginTransaction();

        try {
            $department->delete();

            DB::commit();

            Log::info('Department deleted', ['department_id' => $department->id, 'user_id' => auth()->id()]);

            return redirect()->route('departments.index')->with('success', 'Department deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete department', [
                'error'         => $e->getMessage(),
                'department_id' => $department->id,
                'user_id'       => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while deleting the department.');
        }
    }

    /**
     * Ensure the department belongs to the active company.
     */
    protected function authorizeDepartment(Department $department)
    {
        if ($department->company_id !== auth()->user()->preference->company_id) {
            abort(403, 'Unauthorized access to this department.');
        }
    }
}
