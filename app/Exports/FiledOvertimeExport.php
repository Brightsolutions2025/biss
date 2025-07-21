<?php

namespace App\Exports;

use App\Models\OvertimeRequest;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class FiledOvertimeExport implements FromView
{
    public $user;
    public $company;
    public $request;

    public function __construct($user, $company, $request)
    {
        $this->user    = $user;
        $this->company = $company;
        $this->request = $request;
    }

    public function view(): View
    {
        $query = OvertimeRequest::with('employee.user')
            ->where('company_id', $this->company->id)
            ->when($this->user->employee, fn ($q) => $q->where('employee_id', $this->user->employee->id))
            ->when($this->request->filled('start_date'), fn ($q) => $q->where('date', '>=', $this->request->start_date))
            ->when($this->request->filled('end_date'), fn ($q) => $q->where('date', '<=', $this->request->end_date))
            ->when($this->request->filled('status'), fn ($q) => $q->where('status', $this->request->status))
            ->orderByDesc('date');

        $overtimeRequests = $query->get();

        return view('reports.overtime_history_excel', [
            'overtimeRequests' => $overtimeRequests,
            'companyName'      => $this->company->name,
            'filters'          => $this->request->only(['start_date', 'end_date', 'status']), // ✅ Add this
        ]);
    }
}
