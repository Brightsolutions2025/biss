<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions for the active company.
     */
    public function index(Request $request)
    {
        $company = auth()->user()->preference->company;

        $query = Permission::where('company_id', $company->id);

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        $permissions = $query->paginate(10)->appends($request->query());

        return view('permissions.index', compact('permissions', 'company'));
    }

    /**
     * Show the form for creating a new permission.
     */
    public function create()
    {
        $company = $this->getActiveCompanyOrAbort();

        return view('permissions.create', compact('company'));
    }

    /**
     * Store a newly created permission in storage.
     */
    public function store(Request $request)
    {
        $company = $this->getActiveCompanyOrAbort();

        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:permissions,name,NULL,id,company_id,' . $company->id,
            'description' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $permission             = new Permission($validated);
            $permission->company_id = $company->id;
            $permission->save();

            DB::commit();

            Log::info('Permission created', [
                'permission_id' => $permission->id,
                'company_id'    => $company->id,
                'user_id'       => auth()->id(),
            ]);

            return redirect()->route('permissions.index')->with('success', 'Permission created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create permission', [
                'error'      => $e->getMessage(),
                'company_id' => $company->id,
                'user_id'    => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while creating the permission.');
        }
    }

    /**
     * Display the specified permission.
     */
    public function show(Permission $permission)
    {
        $this->authorizePermission($permission);

        return view('permissions.show', compact('permission'));
    }

    /**
     * Show the form for editing the specified permission.
     */
    public function edit(Permission $permission)
    {
        $this->authorizePermission($permission);

        return view('permissions.edit', compact('permission'));
    }

    /**
     * Update the specified permission in storage.
     */
    public function update(Request $request, Permission $permission)
    {
        $this->authorizePermission($permission);

        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:permissions,name,' .
                             $permission->id . ',id,company_id,' . $permission->company_id,
            'description' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $permission->update($validated);

            DB::commit();

            Log::info('Permission updated', [
                'permission_id' => $permission->id,
                'company_id'    => $permission->company_id,
                'user_id'       => auth()->id(),
            ]);

            return redirect()->route('permissions.index')->with('success', 'Permission updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update permission', [
                'error'         => $e->getMessage(),
                'permission_id' => $permission->id,
                'user_id'       => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while updating the permission.');
        }
    }

    /**
     * Remove the specified permission from storage.
     */
    public function destroy(Permission $permission)
    {
        $this->authorizePermission($permission);

        if ($permission->roles()->exists()) {
            return back()->withErrors('Cannot delete a permission assigned to roles.');
        }

        DB::beginTransaction();

        try {
            $permission->delete();

            DB::commit();

            Log::info('Permission deleted', [
                'permission_id' => $permission->id,
                'company_id'    => $permission->company_id,
                'user_id'       => auth()->id(),
            ]);

            return redirect()->route('permissions.index')->with('success', 'Permission deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete permission', [
                'error'         => $e->getMessage(),
                'permission_id' => $permission->id,
                'user_id'       => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while deleting the permission.');
        }
    }

    /**
     * Ensure the permission belongs to user's active company.
     */
    protected function authorizePermission(Permission $permission)
    {
        $company = $this->getActiveCompanyOrAbort();

        if ($permission->company_id !== $company->id) {
            abort(403, 'Unauthorized access to this permission.');
        }
    }

    /**
     * Get the user's active company from session or abort.
     */
    protected function getActiveCompanyOrAbort(): Company
    {
        $user = auth()->user();

        $companyId = auth()->user()->preference->company_id;

        if (!$companyId) {
            abort(403, 'No active company selected.');
        }

        $company = $user->companies()->find($companyId);

        if (!$company) {
            abort(403, 'Unauthorized for this company.');
        }

        return $company;
    }
}
