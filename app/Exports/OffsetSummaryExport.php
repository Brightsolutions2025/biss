<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class OffsetSummaryExport implements FromView
{
    public $offsetRequests;
    public $startDate;
    public $endDate;

    public function __construct($offsetRequests, $startDate, $endDate)
    {
        $this->offsetRequests = $offsetRequests;
        $this->startDate      = $startDate;
        $this->endDate        = $endDate;
    }

    public function view(): View
    {
        return view('reports.offset_summary_excel', [
            'offsetRequests' => $this->offsetRequests,
            'startDate'      => $this->startDate,
            'endDate'        => $this->endDate,
        ]);
    }
}
