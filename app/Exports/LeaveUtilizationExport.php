<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class LeaveUtilizationExport implements FromView
{
    public function __construct(
        public $company,
        public $leaveBalances,
        public $periodCovered
    ) {
    }

    public function view(): View
    {
        return view('reports.leave_utilization_excel', [
            'company'       => $this->company,
            'leaveBalances' => $this->leaveBalances,
            'periodCovered' => $this->periodCovered,
        ]);
    }
}
