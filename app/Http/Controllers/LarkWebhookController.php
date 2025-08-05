<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\TimeLog;
use App\Models\Company;
use App\Models\PayrollPeriod;
use Carbon\Carbon;

class LarkWebhookController extends Controller
{
    public function receive(Request $request)
    {
        // Handle challenge verification (required by Lark on webhook setup)
        if ($request->has('challenge')) {
            return response()->json(['challenge' => $request->challenge]);
        }

        // Log payload only in local or staging
        if (app()->environment('local', 'staging')) {
            Log::info('Lark Webhook Payload:', $request->all());
        }

        $eventType = $request->input('header.event_type');
        $eventData = $request->input('event');

        // Ensure event data is not null or empty
        if (empty($eventData)) {
            Log::warning('Lark Webhook: Empty event data', $request->all());
            return response()->json(['status' => 'ignored'], 400);
        }

        if (in_array($eventType, ['attendance.checkin', 'attendance.checkout'])) {
            try {
                // 1. Find the company_id
                $company = Company::where('name', 'Bright Solutions Marketing Services, Inc.')->first();
                $companyId = $company ? $company->id : 1; // fallback to 1 if not found

                // 2. Parse attendance time with fallback to now, and set timezone
                $attendanceTime = isset($eventData['time'])
                    ? Carbon::parse($eventData['time'])->timezone('Asia/Manila')
                    : now('Asia/Manila');

                // 3. Determine payroll period range
                $day = $attendanceTime->day;
                $year = $attendanceTime->year;
                $month = $attendanceTime->month;

                if ($day <= 15) {
                    $periodStart = Carbon::create($year, $month, 1);
                    $periodEnd = Carbon::create($year, $month, 15);
                } else {
                    $periodStart = Carbon::create($year, $month, 16);
                    $periodEnd = Carbon::create($year, $month)->endOfMonth();
                }

                // 4. Find or create payroll period
                $payrollPeriod = PayrollPeriod::firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'start_date' => $periodStart->toDateString(),
                        'end_date'   => $periodEnd->toDateString(),
                    ],
                    [
                        'name' => $periodStart->format('M d') . ' - ' . $periodEnd->format('d, Y'),
                    ]
                );

                // 5. Create time log
                TimeLog::create([
                    'company_id'         => $companyId,
                    'payroll_period_id'  => $payrollPeriod->id,
                    'employee_name'      => $eventData['employee_name'] ?? 'Unknown',
                    'department_name'    => $eventData['department_name'] ?? 'Unknown',
                    'employee_id'        => $eventData['employee_id'] ?? $eventData['user_id'] ?? 'Unknown',
                    'employee_type'      => $eventData['employee_type'] ?? 'Regular',
                    'attendance_group'   => $eventData['attendance_group'] ?? 'Default',
                    'date'               => $attendanceTime->toDateString(),
                    'weekday'            => $attendanceTime->format('l'),
                    'shift'              => $eventData['shift'] ?? 'Default',
                    'attendance_time'    => $attendanceTime->toDateTimeString(),
                    'about_the_record'   => $eventType === 'attendance.checkin' ? 'Check-in' : 'Check-out',
                    'attendance_result'  => $eventData['result'] ?? 'Present',
                    'attendance_address' => $eventData['address'] ?? 'Unknown',
                    'note'               => $eventData['note'] ?? '',
                    'attendance_method'  => $eventData['method'] ?? 'Lark',
                    'attendance_photo'   => $eventData['photo_url'] ?? '',
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to store time log from Lark:', [
                    'error' => $e->getMessage(),
                    'data' => $eventData,
                ]);
            }
        }

        return response()->json(['status' => 'received']);
    }
}
