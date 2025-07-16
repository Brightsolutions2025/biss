<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->preference->company_id;

        $query = Role::where('company_id', $companyId);

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $roles = $query->paginate(10)->appends($request->query());

        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $companyId   = auth()->user()->preference->company_id;
        $permissions = Permission::where('company_id', $companyId)->get();

        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->preference->company_id;

        $validated = $request->validate([
            'name'          => 'required|string|max:255|unique:roles,name,NULL,id,company_id,' . $companyId,
            'description'   => 'nullable|string|max:1000',
            'permissions'   => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        // ✅ Safe parsing of permissionsInput (comma-separated string)
        $permissionsInputRaw = $request->input('permissionsInput');
        $permissionIds = $permissionsInputRaw
            ? array_filter(array_map('intval', explode(',', $permissionsInputRaw)))
            : [];

        DB::beginTransaction();

        try {
            $role = Role::create([
                'company_id'  => $companyId,
                'name'        => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            $idArray     = explode(',', $request->input('permissionsInput'));
            $idArray     = array_map('intval', $idArray);
            $permissions = Permission::whereIn('id', $idArray)->get();

            if (isset($permissions)) {
                foreach ($permissions as $permission) {
                    $role->allowTo($permission, $companyId); // Assuming `allowTo` works with Permission model
                }
            }

            DB::commit();

            Log::info('Role created', ['role_id' => $role->id, 'user_id' => auth()->id()]);

            return redirect()->route('roles.index')->with('success', 'Role created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create role', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while creating the role.');
        }
    }

    public function show(Role $role)
    {
        $this->authorizeCompany($role->company_id);

        return view('roles.show', compact('role'));
    }

    public function edit(Role $role)
    {
        $this->authorizeCompany($role->company_id);

        $companyId   = auth()->user()->preference->company_id;
        $permissions = Permission::where('company_id', $companyId)->get();

        return view('roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $companyId = auth()->user()->preference->company_id;

        $this->authorizeCompany($role->company_id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id . ',id,company_id,' . $companyId,
            'description'   => 'nullable|string|max:1000',
            'permissions'   => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        // ✅ Safe parsing of permissionsInput (comma-separated string)
        $permissionsInputRaw = $request->input('permissionsInput');
        $permissionIds = $permissionsInputRaw
            ? array_filter(array_map('intval', explode(',', $permissionsInputRaw)))
            : [];

        DB::beginTransaction();

        try {
            $role->update([
                'name'        => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            $idArray     = explode(',', $request->input('permissionsInput'));
            $idArray     = array_map('intval', $idArray);
            $permissions = Permission::whereIn('id', $idArray)->get();
            $role->permissions()->detach();
            if (isset($permissions)) {
                foreach ($permissions as $permission) {
                    $role->allowTo($permission, $companyId); // Assuming `allowTo` works with Permission model
                }
            }

            DB::commit();

            Log::info('Role updated', ['role_id' => $role->id, 'user_id' => auth()->id()]);

            return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update role', [
                'error'   => $e->getMessage(),
                'role_id' => $role->id,
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while updating the role.');
        }
    }

    public function destroy(Role $role)
    {
        $this->authorizeCompany($role->company_id);

        DB::beginTransaction();

        try {
            $role->permissions()->detach();
            $role->users()->detach();
            $role->delete();

            DB::commit();

            Log::info('Role deleted', ['role_id' => $role->id, 'user_id' => auth()->id()]);

            return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete role', [
                'error'   => $e->getMessage(),
                'role_id' => $role->id,
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while deleting the role.');
        }
    }

    protected function authorizeCompany($companyId)
    {
        if (!auth()->user()->companies->contains('id', $companyId)) {
            abort(403, 'Unauthorized');
        }
    }
}
