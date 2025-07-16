<?php

namespace App\Exports;

use App\Models\TimeRecord;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class TimeRecordExport implements FromView
{
    protected $timeRecord;

    public function __construct(TimeRecord $timeRecord)
    {
        $this->timeRecord = $timeRecord;
    }

    public function view(): View
    {
        return view('exports.time_record_excel', [
            'timeRecord' => $this->timeRecord,
        ]);
    }
}
