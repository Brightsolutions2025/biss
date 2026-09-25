<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Services\LeaveCreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class LeaveBalanceController extends Controller
{
    public function __construct(private LeaveCreditService $leaveCredits)
    {
    }

    /**
     * Display a listing of leave balances for the active company.
     */
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('leave_balance.browse')) {
            abort(403, 'Unauthorized to browse leave balances.');
        }

        $companyId = auth()->user()->preference->company_id;

        $query = LeaveBalance::with('employee')
            ->where('company_id', $companyId);

        if ($request->filled('employee_name')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->employee_name . '%')
                    ->orWhere('last_name', 'like', '%' . $request->employee_name . '%');
            });
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        if ($request->filled('leave_type')) {
            $query->where('leave_type', $request->leave_type);
        }

        $leaveBalances = $query
            ->orderByDesc('year')
            ->orderBy('employee_id')
            ->orderBy('leave_type')
            ->paginate(10)
            ->appends($request->query());

        $leaveTypes = $this->leaveCredits->labels();

        return view('leave_balances.index', compact('leaveBalances', 'leaveTypes'));
    }

    /**
     * Show the form for creating a new leave balance.
     */
    public function create()
    {
        if (!auth()->user()->hasPermission('leave_balance.create')) {
            abort(403, 'Unauthorized to create leave balances.');
        }

        $companyId = auth()->user()->preference->company_id;
        $employees = Employee::where('company_id', $companyId)
            ->whereIn(DB::raw('LOWER(TRIM(employment_type))'), ['regular', 'regular employee'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $leaveTypes = $this->leaveCredits->labels();
        $defaultCredits = collect($this->leaveCredits->types())
            ->mapWithKeys(fn (string $type) => [$type => $this->leaveCredits->defaultAnnualCredits($type)])
            ->all();

        return view('leave_balances.create', compact('employees', 'leaveTypes', 'defaultCredits'));
    }

    /**
     * Store a newly created leave balance in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('leave_balance.create')) {
            abort(403, 'Unauthorized to create leave balances.');
        }

        $companyId = auth()->user()->preference->company_id;
        $year = $request->input('year');
        $leaveType = $request->input('leave_type');

        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
                function ($attribute, $value, $fail) use ($companyId) {
                    $employee = Employee::where('company_id', $companyId)->find($value);

                    if (!$employee?->isRegular()) {
                        $fail('VL and EL credits may only be assigned to regular employees.');
                    }
                },
                Rule::unique('leave_balances', 'employee_id')->where(function ($query) use ($companyId, $year, $leaveType) {
                    return $query->where('company_id', $companyId)
                        ->where('year', $year)
                        ->where('leave_type', $leaveType);
                }),
            ],
            'year' => 'required|integer|min:2000|max:2100',
            'leave_type' => ['required', Rule::in($this->leaveCredits->types())],
            'beginning_balance' => 'nullable|numeric|min:0',
        ], [
            'employee_id.unique' => 'There is already a balance for this employee, year, and leave type.',
        ]);

        DB::beginTransaction();

        try {
            $leaveBalance = LeaveBalance::create([
                'company_id' => $companyId,
                'employee_id' => $validated['employee_id'],
                'year' => $validated['year'],
                'leave_type' => $validated['leave_type'],
                'beginning_balance' => $validated['beginning_balance']
                    ?? $this->leaveCredits->defaultAnnualCredits($validated['leave_type']),
            ]);

            DB::commit();

            Log::info('Leave balance created', [
                'leave_balance_id' => $leaveBalance->id,
                'leave_type' => $leaveBalance->leave_type,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('leave_balances.index')->with('success', 'Leave balance created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create leave balance', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors($e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified leave balance.
     */
    public function show(LeaveBalance $leaveBalance)
    {
        $this->authorizeCompany($leaveBalance->company_id);

        if (!auth()->user()->hasPermission('leave_balance.read')) {
            abort(403, 'Unauthorized to read leave balance.');
        }

        return view('leave_balances.show', compact('leaveBalance'));
    }

    /**
     * Show the form for editing the specified leave balance.
     */
    public function edit(LeaveBalance $leaveBalance)
    {
        $this->authorizeCompany($leaveBalance->company_id);

        if (!auth()->user()->hasPermission('leave_balance.update')) {
            abort(403, 'Unauthorized to edit leave balance.');
        }

        $employees = Employee::where('company_id', $leaveBalance->company_id)
            ->where(function ($query) use ($leaveBalance) {
                $query->whereIn(DB::raw('LOWER(TRIM(employment_type))'), ['regular', 'regular employee'])
                    ->orWhereKey($leaveBalance->employee_id);
            })
            ->get();

        $leaveTypes = $this->leaveCredits->labels();

        return view('leave_balances.edit', compact('leaveBalance', 'employees', 'leaveTypes'));
    }

    /**
     * Update the specified leave balance in storage.
     */
    public function update(Request $request, LeaveBalance $leaveBalance)
    {
        $this->authorizeCompany($leaveBalance->company_id);

        if (!auth()->user()->hasPermission('leave_balance.update')) {
            abort(403, 'Unauthorized to edit leave balance.');
        }

        $companyId = auth()->user()->preference->company_id;

        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
                function ($attribute, $value, $fail) use ($companyId) {
                    $employee = Employee::where('company_id', $companyId)->find($value);

                    if (!$employee?->isRegular()) {
                        $fail('VL and EL credits may only be assigned to regular employees.');
                    }
                },
                Rule::unique('leave_balances', 'employee_id')
                    ->where(function ($query) use ($companyId, $request) {
                        return $query->where('company_id', $companyId)
                            ->where('year', $request->input('year'))
                            ->where('leave_type', $request->input('leave_type'));
                    })
                    ->ignore($leaveBalance->id),
            ],
            'year' => 'required|integer|min:2000|max:2100',
            'leave_type' => ['required', Rule::in($this->leaveCredits->types())],
            'beginning_balance' => 'required|numeric|min:0',
        ], [
            'employee_id.unique' => 'There is already a balance for this employee, year, and leave type.',
        ]);

        DB::beginTransaction();

        try {
            $leaveBalance->update([
                'employee_id' => $validated['employee_id'],
                'year' => $validated['year'],
                'leave_type' => $validated['leave_type'],
                'beginning_balance' => $validated['beginning_balance'],
            ]);

            DB::commit();

            Log::info('Leave balance updated', [
                'leave_balance_id' => $leaveBalance->id,
                'leave_type' => $leaveBalance->leave_type,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('leave_balances.index')->with('success', 'Leave balance updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update leave balance', [
                'error' => $e->getMessage(),
                'leave_balance_id' => $leaveBalance->id,
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors($e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified leave balance from storage.
     */
    public function destroy(LeaveBalance $leaveBalance)
    {
        $this->authorizeCompany($leaveBalance->company_id);

        if (!auth()->user()->hasPermission('leave_balance.delete')) {
            abort(403, 'Unauthorized to delete leave balance.');
        }

        DB::beginTransaction();

        try {
            $leaveBalance->delete();

            DB::commit();

            Log::info('Leave balance deleted', [
                'leave_balance_id' => $leaveBalance->id,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('leave_balances.index')->with('success', 'Leave balance deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete leave balance', [
                'error' => $e->getMessage(),
                'leave_balance_id' => $leaveBalance->id,
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while deleting the leave balance.');
        }
    }

    /**
     * Ensure the leave balance belongs to the user's active company.
     */
    protected function authorizeCompany($companyId)
    {
        $user = auth()->user();

        if (!$user->companies->contains($companyId)) {
            abort(403, 'Unauthorized: Company access denied.');
        }

        if ($user->preference->company_id != $companyId) {
            abort(403, 'Unauthorized: Company mismatch.');
        }
    }
}
