<?php

namespace App\Http\Controllers;

use App\Models\PayrollPeriod;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PayrollPeriodController extends Controller
{
    /**
     * Display a listing of the payroll periods for the active company.
     */
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('payroll_period.browse')) {
            abort(403, 'Unauthorized to browse payroll periods.');
        }

        $company      = auth()->user()->preference->company;

        $query = PayrollPeriod::where('company_id', $company->id);

        if ($request->filled('start_date')) {
            $query->where('start_date', '>=', $request->input('start_date'));
        }

        $payrollPeriods = $query->paginate(10)->appends($request->query());

        return view('payroll_periods.index', compact('payrollPeriods'));
    }

    /**
     * Show the form for creating a new payroll period.
     */
    public function create()
    {
        if (!auth()->user()->hasPermission('payroll_period.create')) {
            abort(403, 'Unauthorized to create payroll periods.');
        }

        return view('payroll_periods.create');
    }

    /**
     * Store a newly created payroll period in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('payroll_period.create')) {
            abort(403, 'Unauthorized to create payroll periods.');
        }

        $companyId = auth()->user()->preference->company_id;

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'dtr_submission_due_at' => 'nullable|date',
            'timezone' => 'required|timezone',
        ]);

        $dtrDueAt = null;

        if ($request->filled('dtr_submission_due_at')) {
            // Convert from user's timezone to UTC
            $dtrDueAt = Carbon::parse($request->dtr_submission_due_at, $request->timezone)
                ->timezone('UTC');
        }

        // Check for exact duplicate
        $duplicateExists = PayrollPeriod::where('company_id', $companyId)
            ->where('start_date', $validated['start_date'])
            ->where('end_date', $validated['end_date'])
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'start_date' => ['A payroll period with the same start and end date already exists.'],
                'end_date'   => ['A payroll period with the same start and end date already exists.'],
            ]);
        }

        DB::beginTransaction();

        try {
            $payrollPeriod = PayrollPeriod::create([
                'company_id' => $companyId,
                'start_date' => $validated['start_date'],
                'end_date'   => $validated['end_date'],
                'dtr_submission_due_at' => $dtrDueAt ?? null,
            ]);

            DB::commit();

            Log::info('Payroll period created', ['payroll_period_id' => $payrollPeriod->id, 'user_id' => auth()->id()]);

            return redirect()->route('payroll_periods.index')->with('success', 'Payroll period created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create payroll period', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while creating the payroll period.');
        }
    }

    /**
     * Display the specified payroll period.
     */
    public function show(PayrollPeriod $payrollPeriod)
    {
        $this->authorizePayrollPeriod($payrollPeriod);

        if (!auth()->user()->hasPermission('payroll_period.read')) {
            abort(403, 'Unauthorized to view payroll period.');
        }

        return view('payroll_periods.show', compact('payrollPeriod'));
    }

    /**
     * Show the form for editing the specified payroll period.
     */
    public function edit(PayrollPeriod $payrollPeriod)
    {
        $this->authorizePayrollPeriod($payrollPeriod);

        if (!auth()->user()->hasPermission('payroll_period.update')) {
            abort(403, 'Unauthorized to edit payroll period.');
        }

        return view('payroll_periods.edit', compact('payrollPeriod'));
    }

    /**
     * Update the specified payroll period in storage.
     */
    public function update(Request $request, PayrollPeriod $payrollPeriod)
    {
        $this->authorizePayrollPeriod($payrollPeriod);

        if (!auth()->user()->hasPermission('payroll_period.update')) {
            abort(403, 'Unauthorized to edit payroll period.');
        }

        $companyId = auth()->user()->preference->company_id;

        if ($payrollPeriod->company_id !== $companyId) {
            abort(403, 'You are not authorized to update this payroll period.');
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'dtr_submission_due_at' => 'nullable|date',
            'timezone' => 'required|timezone',
        ]);

        // Convert datetime-local input from selected timezone to UTC
        $dtrDueAt = null;
        if ($request->filled('dtr_submission_due_at')) {
            $dtrDueAt = \Carbon\Carbon::parse($request->dtr_submission_due_at, $validated['timezone'])
                ->timezone('UTC');
        }

        // Check for exact duplicate excluding current record
        $duplicateExists = PayrollPeriod::where('company_id', $companyId)
            ->where('start_date', $validated['start_date'])
            ->where('end_date', $validated['end_date'])
            ->where('id', '!=', $payrollPeriod->id)
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'start_date' => ['A payroll period with the same start and end date already exists.'],
                'end_date'   => ['A payroll period with the same start and end date already exists.'],
            ]);
        }

        DB::beginTransaction();

        try {
            $payrollPeriod->update([
                'start_date' => $validated['start_date'],
                'end_date'   => $validated['end_date'],
                'dtr_submission_due_at' => $dtrDueAt,
            ]);

            // Reset reminder timestamp on update
            $payrollPeriod->reminder_sent_at = null;
            $payrollPeriod->save();

            DB::commit();

            Log::info('Payroll period updated', ['payroll_period_id' => $payrollPeriod->id, 'user_id' => auth()->id()]);

            return redirect()->route('payroll_periods.index')->with('success', 'Payroll period updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update payroll period', [
                'error'             => $e->getMessage(),
                'payroll_period_id' => $payrollPeriod->id,
                'user_id'           => auth()->id(),
            ]);

            return back()->withErrors($e->getMessage());
        }
    }

    /**
     * Remove the specified payroll period from storage.
     */
    public function destroy(PayrollPeriod $payrollPeriod)
    {
        $this->authorizePayrollPeriod($payrollPeriod);

        if (!auth()->user()->hasPermission('payroll_period.delete')) {
            abort(403, 'Unauthorized to delete payroll period.');
        }

        DB::beginTransaction();

        try {
            $payrollPeriod->delete();

            DB::commit();

            Log::info('Payroll period deleted', ['payroll_period_id' => $payrollPeriod->id, 'user_id' => auth()->id()]);

            return redirect()->route('payroll_periods.index')->with('success', 'Payroll period deleted.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete payroll period', [
                'error'             => $e->getMessage(),
                'payroll_period_id' => $payrollPeriod->id,
                'user_id'           => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while deleting the payroll period.');
        }
    }

    /**
     * Helper method to ensure the payroll period belongs to the active company.
     */
    protected function authorizePayrollPeriod(PayrollPeriod $payrollPeriod)
    {
        $companyId = $payrollPeriod->company_id;
        $user = auth()->user();

        // Ensure the user is assigned to the company
        if (!$user->companies->contains($companyId)) {
            abort(403, 'Unauthorized: Company access denied.');
        }

        // Ensure the user's *active* preference matches the given company (enforcing scope)
        if ($user->preference->company_id != $companyId) {
            abort(403, 'Unauthorized: Company mismatch.');
        }
    }
}
