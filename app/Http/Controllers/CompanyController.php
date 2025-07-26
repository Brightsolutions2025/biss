<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
 */

class CompanyController extends Controller
{
    /**
     * Display a listing of the user's companies.
     */
    public function index(Request $request)
    {
        $query = auth()->user()->companies();

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . request('name') . '%');
        }

        $companies = $query->paginate(10)->appends($request->query());

        return view('companies.index', compact('companies'));
    }

    /**
     * Show the form for creating a new company.
     */
    public function create()
    {
        return view('companies.create');
    }

    /**
     * Store a newly created company in storage and attach the user.
     */

    public function store(Request $request)
    {
        $validated = $this->validateCompanyData($request);

        if ($this->adminRoleExists() && !$this->isCurrentUserAdmin()) {
            return redirect()->route('companies.index')
                ->withErrors(['error' => 'Only admin users can create new companies.']);
        }

        DB::beginTransaction();

        try {
            $company = Company::create($validated);
            $this->assignUserToCompany($company);

            $roles = $this->createDefaultRoles($company);
            $this->assignBasePermissions($company, $roles);
            $this->assignReportPermissions($company, $roles);
            $this->setUserPreferences($company);

            DB::commit();

            Log::info('Company created', ['company_id' => $company->id, 'user_id' => auth()->id()]);

            return redirect()->route('dashboard')->with('success', 'Company created and switched to.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create company', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors($e->getMessage());
        }
    }

    private function validateCompanyData(Request $request)
    {
        return $request->validate([
            'name'                     => 'required|string|max:255',
            'industry'                 => 'nullable|string|max:255',
            'address'                  => 'nullable|string|max:255',
            'phone'                    => 'nullable|string|max:20',
            'offset_valid_after_days'  => 'nullable|integer|min:0|max:365',
            'offset_valid_before_days' => 'nullable|integer|min:0|max:365',
        ]);
    }

    private function adminRoleExists(): bool
    {
        return Role::whereRaw('LOWER(name) = ?', ['admin'])->exists();
    }

    private function isCurrentUserAdmin(): bool
    {
        return auth()->user()
            ->roles()
            ->whereRaw('LOWER(name) = ?', ['admin'])
            ->exists();
    }

    private function assignUserToCompany(Company $company): void
    {
        $company->users()->attach(auth()->id());
    }

    private function createDefaultRoles(Company $company): array
    {
        $roles = [
            'admin' => Role::create([
                'name'        => 'Admin',
                'description' => 'Administrator role',
                'company_id'  => $company->id,
            ]),
            'hr supervisor' => Role::create([
                'name'        => 'HR Supervisor',
                'description' => 'Manages Departments',
                'company_id'  => $company->id,
            ]),
            'employee' => Role::create([
                'name'        => 'Employee',
                'description' => 'Basic employee access',
                'company_id'  => $company->id,
            ]),
            'department head' => Role::create([
                'name'        => 'Department Head',
                'description' => 'Manages employees within their department',
                'company_id'  => $company->id,
            ]),
            'account manager' => Role::create([
                'name'        => 'Account Manager',
                'description' => 'Manages clients and their details',
                'company_id'  => $company->id,
            ]),
        ];

        // Attach essential roles to current user
        foreach (['admin', 'hr supervisor', 'employee', 'account manager'] as $roleKey) {
            auth()->user()->roles()->attach($roles[$roleKey]->id, ['company_id' => $company->id]);
        }

        return $roles;
    }

    private function assignBasePermissions(Company $company, array $roles): void
    {
        $permissionsMap = [
            'hr supervisor' => ['modules' => [
                'department', 'team', 'employee', 'shift',
                'employee_shift', 'payroll_period', 'time_log', 'leave_balance',
            ]],
            'employee' => ['modules' => [
                'leave_request', 'overtime_request',
                'outbase_request', 'offset_request', 'time_record',
            ]],
            'account manager' => ['modules' => ['client', 'client_contact']],
        ];

        $actions = ['browse', 'create', 'read', 'update', 'delete'];

        foreach ($permissionsMap as $role => $config) {
            $permissionIds = collect();

            foreach ($config['modules'] as $module) {
                foreach ($actions as $action) {
                    $perm = Permission::create([
                        'name'        => "{$module}.{$action}",
                        'description' => ucfirst(str_replace('.', ' ', "{$module}.{$action}")),
                        'company_id'  => $company->id,
                    ]);
                    $permissionIds->push($perm->id);
                }
            }

            $roles[$role]->permissions()->attach(
                $permissionIds->mapWithKeys(fn ($id) => [$id => ['company_id' => $company->id]])->toArray()
            );
        }

        // Shared permissions (browse_all, read, etc.)
        $sharedPermissions = collect([
            ['overtime_request.browse_all', 'Can view all overtime requests for the company'],
            ['leave_request.browse_all', 'Can view all leave requests for the company'],
            ['outbase_request.browse_all', 'Can view all outbase requests for the company'],
            ['offset_request.browse_all', 'Can view all offset requests for the company'],
            ['time_record.browse_all', 'Can view all time records for the company'],
        ]);

        foreach ($sharedPermissions as [$name, $desc]) {
            $perm = $this->getOrCreatePermission($name, $desc, $company->id);
            foreach (['admin', 'hr supervisor'] as $roleKey) {
                $roles[$roleKey]->permissions()->syncWithoutDetaching([
                    $perm->id => ['company_id' => $company->id]
                ]);
            }
        }
    }

    private function assignReportPermissions(Company $company, array $roles): void
    {
        $reportPermissions = [
            ['reports.dtr_status_by_team', 'view time record report', 'Employee DTR Status by Department & Team', ['admin', 'hr supervisor', 'department head']],
            ['reports.leave_utilization', 'view leave report', 'Leave Utilization Summary', ['admin', 'hr supervisor', 'department head']],
            ['reports.overtime_offset_comparison', 'view overtime report', 'Overtime vs Offset Report', ['admin', 'hr supervisor', 'department head']],
            ['reports.late_undertime', 'view attendance report', 'Late and Undertime Report', ['admin', 'hr supervisor', 'department head']],
            ['reports.leave_status_overview', 'view leave report', 'Leave Requests by Status', ['admin', 'hr supervisor', 'department head']],
            ['reports.outbase_summary', 'view outbase report', 'Outbase Request Summary', ['admin', 'hr supervisor', 'department head']],
            ['reports.offset_tracker', 'view offset report', 'Offset Usage and Expiry Tracker', ['admin', 'hr supervisor', 'employee']],
            ['reports.leave_summary', 'view leave report', 'Leave Summary Report', ['admin', 'hr supervisor', 'employee']],
            ['reports.overtime_history', 'view overtime report', 'Filed Overtime Report', ['admin', 'hr supervisor', 'employee']],
            ['reports.leave_timeline', 'view leave report', 'Approved Leaves Timeline', ['admin', 'hr supervisor', 'employee']],
            ['reports.outbase_history', 'view outbase report', 'Outbase Request Report', ['admin', 'hr supervisor', 'employee']],
            ['reports.offset_summary', 'view offset report', 'Offset Request Summary', ['admin', 'hr supervisor', 'employee']],
            // example for account manager (optional, if you have client-related reports)
            ['reports.client_summary', 'view client report', 'Client Summary Report', ['admin', 'account manager']],
        ];

        foreach ($reportPermissions as [$route, $permName, $desc, $roleKeys]) {
            $perm = $this->getOrCreatePermission($permName, $desc, $company->id);
            foreach ($roleKeys as $roleKey) {
                if (isset($roles[$roleKey])) {
                    $roles[$roleKey]->permissions()->syncWithoutDetaching([
                        $perm->id => ['company_id' => $company->id],
                    ]);
                }
            }
        }
    }

    private function setUserPreferences(Company $company): void
    {
        session(['active_company_id' => $company->id]);

        auth()->user()->preference()->updateOrCreate(
            ['user_id' => auth()->id()],
            ['company_id' => $company->id]
        );
    }

    private function getOrCreatePermission(string $name, string $description, int $companyId): Permission
    {
        return Permission::firstOrCreate(
            ['name' => $name, 'company_id' => $companyId],
            ['description' => $description]
        );
    }

    /**
     * Display the specified company (must belong to the user).
     */
    public function show(Company $company)
    {
        $this->authorizeCompany($company);

        return view('companies.show', compact('company'));
    }

    /**
     * Show the form for editing the specified company.
     */
    public function edit(Company $company)
    {
        $this->authorizeCompany($company, true); // ✅ Only admin can update

        return view('companies.edit', compact('company'));
    }

    /**
     * Update the specified company in storage.
     */
    public function update(Request $request, Company $company)
    {
        $this->authorizeCompany($company, true); // ✅ Only admin can update

        $validated = $request->validate([
            'name'                     => 'required|string|max:255',
            'industry'                 => 'nullable|string|max:255',
            'address'                  => 'nullable|string|max:255',
            'phone'                    => 'nullable|string|max:20',
            'offset_valid_after_days'  => 'nullable|integer|min:0|max:365',
            'offset_valid_before_days' => 'nullable|integer|min:0|max:365',
        ]);

        DB::beginTransaction();

        try {
            $company->update($validated);

            DB::commit();

            Log::info('Company updated', ['company_id' => $company->id, 'user_id' => auth()->id()]);

            return redirect()->route('companies.index')->with('success', 'Company updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update company', [
                'error'      => $e->getMessage(),
                'company_id' => $company->id,
                'user_id'    => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while updating the company.');
        }
    }

    /**
     * Remove the specified company from storage (detach current user).
     */
    public function destroy(Company $company)
    {
        $this->authorizeCompany($company, true); // ✅ Only admin can update

        DB::beginTransaction();

        try {
            // Optional: detach users if you want relationships cleaned up
            $company->users()->detach();

            // Soft delete the company
            $company->delete();

            if (session('active_company_id') == $company->id) {
                session()->forget('active_company_id');
            }

            DB::commit();

            Log::info('Company deleted', ['company_id' => $company->id, 'user_id' => auth()->id()]);

            return redirect()->route('companies.index')->with('success', 'Company removed from your account.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to remove company from user', [
                'error'      => $e->getMessage(),
                'company_id' => $company->id,
                'user_id'    => auth()->id(),
            ]);

            return back()->withErrors($e->getMessage());
        }
    }

    /**
     * Handle switching between companies (already defined).
     */
    /*
    public function switch(Request $request)
    {
        $request->validate(['company_id' => 'required|exists:companies,id']);

        if (!auth()->user()->companies->contains('id', $request->company_id)) {
            abort(403, 'You do not belong to this company.');
        }

        session(['active_company_id' => $request->company_id]);

        return redirect()->back()->with('success', 'Switched to selected company.');
    }*/

    /**
     * Private helper to ensure user belongs to the company.
     */
    protected function authorizeCompany(Company $company, bool $adminOnly = false)
    {
        $user = auth()->user();

        if (!$user->companies->contains($company->id)) {
            abort(403, 'Unauthorized: not your company.');
        }
        /*
        if ($adminOnly) {
            // Check if the user has the 'Admin' role in this company
            $hasAdminRole = $user->roles()
                ->wherePivot('company_id', $company->id)
                ->whereRaw('LOWER(name) = ?', ['admin'])
                ->exists();

            if (!$hasAdminRole) {
                abort(403, 'Unauthorized: admin access required.');
            }
        }*/
    }
}
