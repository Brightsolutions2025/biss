<?php

namespace App\Exports;

use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Http\Request;

class OvertimeOffsetExport implements FromView
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function view(): View
    {
        $controller = new \App\Http\Controllers\ReportController();
        $data = $controller->generateOvertimeOffsetData($this->request);

        return view('reports.overtime_offset_excel', $data);
    }
}
