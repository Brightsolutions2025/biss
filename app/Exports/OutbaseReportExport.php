<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class OutbaseReportExport implements FromView
{
    protected $outbaseRequests;
    protected $startDate;
    protected $endDate;

    public function __construct($outbaseRequests, $startDate, $endDate)
    {
        $this->outbaseRequests = $outbaseRequests;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function view(): View
    {
        return view('reports.outbase_history_excel', [
            'outbaseRequests' => $this->outbaseRequests,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);
    }
}
