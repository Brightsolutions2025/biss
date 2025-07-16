<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use App\Http\Controllers\OffsetTrackerController;

class OffsetTrackerExport implements FromView
{
    protected $request;
    protected $companyName;
    protected $periodText;

    public function __construct($request, $companyName, $periodText)
    {
        $this->request = $request;
        $this->companyName = $companyName;
        $this->periodText = $periodText;
    }

    public function view(): View
    {
        $offsetData = app(OffsetTrackerController::class)->getOffsetData($this->request);

        return view('reports.offset_tracker_excel', [
            'offsetData' => $offsetData,
            'companyName' => $this->companyName,
            'periodText' => $this->periodText,
        ]);
    }
}
