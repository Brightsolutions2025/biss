<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class LateUndertimeExport implements FromView
{
    protected $grouped, $date_from, $date_to, $company;

    public function __construct($grouped, $date_from, $date_to, $company)
    {
        $this->grouped = $grouped;
        $this->date_from = $date_from;
        $this->date_to = $date_to;
        $this->company = $company;
    }

    public function view(): View
    {
        return view('reports.late_undertime_excel', [
            'grouped' => $this->grouped,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'company' => $this->company,
        ]);
    }

}