<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class DtrStatusExport implements FromView
{
    public $reportData;
    public $payrollPeriod;

    public function __construct($reportData, $payrollPeriod)
    {
        $this->reportData    = $reportData;
        $this->payrollPeriod = $payrollPeriod;
    }

    public function view(): View
    {
        return view('reports.dtr_status_by_team_excel', [
            'reportData'    => $this->reportData,
            'payrollPeriod' => $this->payrollPeriod,
        ]);
    }
}
