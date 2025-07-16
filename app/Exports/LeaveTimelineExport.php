<?php

namespace App\Exports;

use App\Models\LeaveRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeaveTimelineExport implements FromArray, WithTitle, WithStyles
{
    protected $leaveRequests;
    protected $startDate;
    protected $endDate;
    protected $employeeName;

    public function __construct(Collection $leaveRequests, $startDate, $endDate, $employeeName)
    {
        $this->leaveRequests = $leaveRequests;
        $this->startDate = Carbon::parse($startDate)->startOfMonth();
        $this->endDate = Carbon::parse($startDate)->endOfMonth();
        $this->employeeName = $employeeName;
    }

    public function title(): string
    {
        return 'Leave Calendar';
    }

    public function array(): array
    {
        $calendar = [];

        // Add report title and period
        $calendar[] = ["Approved Leaves Timeline"];
        $calendar[] = ["Period: " . $this->startDate->toFormattedDateString() . " to " . $this->endDate->toFormattedDateString()];
        $calendar[] = []; // Blank row for spacing

        // Add weekday headers
        $calendar[] = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        $start = $this->startDate->copy()->startOfWeek();
        $end = $this->endDate->copy()->endOfWeek();

        $week = [];

        for ($date = $start->copy(); $date <= $end; $date->addDay()) {
            $cell = $date->day;

            $entries = $this->leaveRequests->filter(function ($leave) use ($date) {
                return $date->between(
                    \Carbon\Carbon::parse($leave->start_date),
                    \Carbon\Carbon::parse($leave->end_date)
                );
            });

            if ($entries->isNotEmpty()) {
                foreach ($entries as $entry) {
                    $cell .= "\n" . $entry->type . ': ' . $entry->reason;
                }
            }

            $week[] = $cell;

            if ($date->dayOfWeek == 6) {
                $calendar[] = $week;
                $week = [];
            }
        }

        return $calendar;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(12);

        $sheet->getStyle('A3:G3')->getFont()->setBold(true); // Header row
        $sheet->getStyle('A:G')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A:G')->getAlignment()->setVertical('top');

        return [];
    }
}
