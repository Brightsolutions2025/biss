<?php

namespace App\Exports;

use App\Models\OutbaseRequest;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class OutbaseSummaryExport implements FromView
{
    protected $filters;
    protected $company;

    public function __construct(array $filters, $company)
    {
        $this->filters = $filters;
        $this->company = $company;
    }

    public function view(): View
    {
        $query = OutbaseRequest::query()
            ->where('company_id', $this->company->id)
            ->where('status', 'approved')
            ->with(['employee.user', 'employee.department']);

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('date', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('date', '<=', $this->filters['date_to']);
        }

        if (!empty($this->filters['department_id'])) {
            $query->whereHas('employee', function ($q) {
                $q->where('department_id', $this->filters['department_id']);
            });
        }

        if (!empty($this->filters['employee_id'])) {
            $query->where('employee_id', $this->filters['employee_id']);
        }

        if (!empty($this->filters['location'])) {
            $query->where('location', $this->filters['location']);
        }

        // Group and summarize
        $summary = $query->get()
            ->groupBy(function ($item) {
                return $item->employee_id;
            })
            ->map(function ($group) {
                $employee = $group->first()->employee;
                return [
                    'department'     => $employee->department->name ?? 'N/A',
                    'employee'       => $employee->user->name       ?? 'N/A',
                    'outbase_count'  => $group->count(),
                ];
            })
            ->values();

        // Optional: Sort
        if (!empty($this->filters['sort'])) {
            switch ($this->filters['sort']) {
                case 'employee_asc':
                    $summary = $summary->sortBy('employee')->values();
                    break;
                case 'employee_desc':
                    $summary = $summary->sortByDesc('employee')->values();
                    break;
                case 'count_asc':
                    $summary = $summary->sortBy('outbase_count')->values();
                    break;
                case 'count_desc':
                    $summary = $summary->sortByDesc('outbase_count')->values();
                    break;
            }
        }

        return view('reports.outbase_summary_excel', [
            'data'    => $summary,
            'company' => $this->company->name,
            'period'  => $this->getPeriod(),
        ]);
    }
    protected function getPeriod(): string
    {
        $dateFrom = $this->filters['date_from'] ?? null;
        $dateTo   = $this->filters['date_to']   ?? null;

        if ($dateFrom && $dateTo) {
            try {
                return 'Period Covered: ' .
                    \Carbon\Carbon::parse($dateFrom)->format('F d, Y') .
                    ' to ' .
                    \Carbon\Carbon::parse($dateTo)->format('F d, Y');
            } catch (\Exception $e) {
                return 'Period Covered: Invalid date range';
            }
        }

        return 'Period Covered: All Time';
    }
}
