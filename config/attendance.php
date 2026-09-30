<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Attendance policy effective October 1, 2026
    |--------------------------------------------------------------------------
    |
    | Policy values are centralized here and may be overridden through .env.
    | Attendance dates before the effective date keep the legacy behavior so
    | historical September-and-earlier DTRs are not recalculated under the new
    | October policy.
    |
    */
    'policy_effective_date' => env('ATTENDANCE_POLICY_EFFECTIVE_DATE', '2026-10-01'),

    'flexible_cutoff_time' => env('ATTENDANCE_FLEXIBLE_CUTOFF_TIME', '11:00'),
    'required_work_minutes' => (int) env('ATTENDANCE_REQUIRED_WORK_MINUTES', 480),

    /*
    | New flexible-break policy (October 1, 2026 onward): the break may be
    | taken at any time. BISS deducts this standard duration from the elapsed
    | Time In to Time Out span; no Break Out / Break In logs are required.
    */
    'break_minutes' => (int) env('ATTENDANCE_BREAK_MINUTES', 60),

    /*
    | Legacy-only break window used for attendance dates before October 1,
    | 2026. Keeping it separate prevents the new flexible-break policy from
    | changing historical DTR computations when an older record is edited.
    */
    'legacy_break_start' => env('ATTENDANCE_LEGACY_BREAK_START', '12:00'),
    'legacy_break_end' => env('ATTENDANCE_LEGACY_BREAK_END', '13:00'),

    /*
    | Position matching is case-insensitive and checks whether the employee's
    | position contains any configured keyword.
    */
    'fixed_position_keywords' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ATTENDANCE_FIXED_POSITION_KEYWORDS', 'warehouseman,warehouse man'))
    ))),

    'unrestricted_position_keywords' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ATTENDANCE_UNRESTRICTED_POSITION_KEYWORDS', 'director'))
    ))),
];
