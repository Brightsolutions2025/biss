<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Carbon;

class AttendancePolicyService
{
    public const POLICY_FIXED = 'fixed';
    public const POLICY_LEGACY_FLEXIBLE = 'legacy_flexible';
    public const POLICY_FLEXIBLE_CUTOFF = 'flexible_cutoff';
    public const POLICY_FLEXIBLE_UNRESTRICTED = 'flexible_unrestricted';

    public function policyFor(Employee $employee, string|Carbon $workDate): string
    {
        $date = $this->dateStart($workDate);

        if ($date->lt($this->effectiveDate())) {
            return $employee->flexible_time
                ? self::POLICY_LEGACY_FLEXIBLE
                : self::POLICY_FIXED;
        }

        return $this->policyAfterEffectiveDate($employee);
    }

    public function policyAfterEffectiveDate(Employee $employee): string
    {
        if ($this->positionMatches($employee->position, config('attendance.fixed_position_keywords', []))) {
            return self::POLICY_FIXED;
        }

        if ($this->positionMatches($employee->position, config('attendance.unrestricted_position_keywords', []))) {
            return self::POLICY_FLEXIBLE_UNRESTRICTED;
        }

        return self::POLICY_FLEXIBLE_CUTOFF;
    }

    /**
     * Calculate authoritative late and undertime values for a DTR line.
     */
    public function calculate(
        Employee $employee,
        string|Carbon $workDate,
        ?string $clockIn,
        ?string $clockOut
    ): array {
        $date = $this->dateStart($workDate);
        $policy = $this->policyFor($employee, $date);
        $isLegacyDate = $date->lt($this->effectiveDate());

        if ($policy === self::POLICY_FIXED) {
            $result = $isLegacyDate
                ? $this->calculateLegacyFixed($employee, $date, $clockIn, $clockOut)
                : $this->calculateCurrentFixed($employee, $date, $clockIn, $clockOut);

            return $result + ['policy' => $policy];
        }

        if ($policy === self::POLICY_LEGACY_FLEXIBLE) {
            return [
                'policy' => $policy,
                'late_minutes' => 0,
                'undertime_minutes' => $this->calculateLegacyFlexibleUndertime($date, $clockIn, $clockOut),
            ];
        }

        $lateMinutes = 0;

        if ($policy === self::POLICY_FLEXIBLE_CUTOFF && $clockIn) {
            $lateMinutes = $this->minutesLateAgainstCutoff($date, $clockIn);
        }

        return [
            'policy' => $policy,
            'late_minutes' => $lateMinutes,
            'undertime_minutes' => $this->calculateFlexibleUndertime($date, $clockIn, $clockOut),
        ];
    }

    /**
     * Calculate the total credited work time for one date.
     *
     * Credited time is the union of the employee's office attendance interval
     * and all approved Outbase intervals for the same date. Overlapping
     * intervals are merged so time is never double-counted.
     *
     * For dates on/after the attendance-policy effective date, break timing is
     * flexible and not explicitly logged. Any gaps between credited intervals
     * satisfy the configured break first; only the remaining standard break is
     * deducted from credited minutes. This makes, for example,
     * 08:00-12:00 office + 13:00-17:00 Outbase equal 480 credited minutes.
     *
     * Pre-effective-date records preserve the legacy fixed 12:00-13:00 break
     * overlap behavior.
     */
    public function totalCreditedWorkMinutes(
        Employee $employee,
        string|Carbon $workDate,
        ?string $clockIn,
        ?string $clockOut,
        iterable $outbaseIntervals = []
    ): int {
        $date = $this->dateStart($workDate);
        $intervals = [];

        if ($clockIn && $clockOut) {
            $intervals[] = $this->normalizedInterval($date, $clockIn, $clockOut);
        }

        foreach ($outbaseIntervals as $outbase) {
            $start = is_array($outbase)
                ? ($outbase['time_start'] ?? $outbase['start'] ?? null)
                : ($outbase->time_start ?? null);

            $end = is_array($outbase)
                ? ($outbase['time_end'] ?? $outbase['end'] ?? null)
                : ($outbase->time_end ?? null);

            if (!$start || !$end) {
                continue;
            }

            $intervals[] = $this->normalizedInterval($date, $start, $end);
        }

        if ($intervals === []) {
            return 0;
        }

        $merged = $this->mergeIntervals($intervals);
        $creditedMinutes = 0;

        foreach ($merged as [$start, $end]) {
            $creditedMinutes += $this->diffMinutes($start, $end);
        }

        if ($date->lt($this->effectiveDate())) {
            $legacyBreakMinutes = 0;

            foreach ($merged as [$start, $end]) {
                $legacyBreakMinutes += $this->legacyBreakOverlapMinutes($date, $start, $end);
            }

            return max(0, $creditedMinutes - $legacyBreakMinutes);
        }

        $breakMinutes = max(0, (int) config('attendance.break_minutes', 60));

        if ($breakMinutes === 0) {
            return $creditedMinutes;
        }

        $gapMinutes = 0;
        for ($i = 1, $count = count($merged); $i < $count; $i++) {
            $previousEnd = $merged[$i - 1][1];
            $currentStart = $merged[$i][0];

            if ($currentStart->gt($previousEnd)) {
                $gapMinutes += $this->diffMinutes($previousEnd, $currentStart);
            }
        }

        $remainingBreakToDeduct = max(0, $breakMinutes - $gapMinutes);

        return max(0, $creditedMinutes - $remainingBreakToDeduct);
    }

    public function frontendConfig(Employee $employee): array
    {
        return [
            'effective_date' => $this->effectiveDate()->toDateString(),
            'legacy_policy' => $employee->flexible_time
                ? self::POLICY_LEGACY_FLEXIBLE
                : self::POLICY_FIXED,
            'policy_after_effective_date' => $this->policyAfterEffectiveDate($employee),
            'flexible_cutoff_time' => (string) config('attendance.flexible_cutoff_time', '11:00'),
            'required_work_minutes' => (int) config('attendance.required_work_minutes', 480),
            'break_minutes' => (int) config('attendance.break_minutes', 60),
            'legacy_break_start' => (string) config('attendance.legacy_break_start', '12:00'),
            'legacy_break_end' => (string) config('attendance.legacy_break_end', '13:00'),
        ];
    }

    /**
     * Pre-October-1 fixed-shift calculation. This intentionally preserves the
     * old lunch-overlap behavior for historical DTRs.
     */
    protected function calculateLegacyFixed(
        Employee $employee,
        string|Carbon $workDate,
        ?string $clockIn,
        ?string $clockOut
    ): array {
        $shiftTimes = $this->shiftTimes($employee, $workDate);

        if (!$shiftTimes) {
            return ['late_minutes' => 0, 'undertime_minutes' => 0];
        }

        [$date, $scheduledIn, $scheduledOut] = $shiftTimes;

        $lateMinutes = 0;
        if ($clockIn) {
            $actualIn = $this->dateTimeFor($date, $clockIn);
            if ($actualIn->gt($scheduledIn)) {
                $lateMinutes = max(
                    0,
                    $this->diffMinutes($scheduledIn, $actualIn)
                    - $this->legacyBreakOverlapMinutes($date, $scheduledIn, $actualIn)
                );
            }
        }

        $undertimeMinutes = 0;
        if ($clockOut) {
            $actualOut = $this->normalizeClockOut($date, $scheduledIn, $scheduledOut, $clockOut);
            if ($actualOut->lt($scheduledOut)) {
                $undertimeMinutes = max(
                    0,
                    $this->diffMinutes($actualOut, $scheduledOut)
                    - $this->legacyBreakOverlapMinutes($date, $actualOut, $scheduledOut)
                );
            }
        }

        return [
            'late_minutes' => $lateMinutes,
            'undertime_minutes' => $undertimeMinutes,
        ];
    }

    /**
     * October-1-and-later fixed-shift calculation (Warehouseman). Break time
     * is flexible, so late/undertime are measured directly against shift start
     * and end without assuming a 12:00-1:00 break window.
     */
    protected function calculateCurrentFixed(
        Employee $employee,
        string|Carbon $workDate,
        ?string $clockIn,
        ?string $clockOut
    ): array {
        $shiftTimes = $this->shiftTimes($employee, $workDate);

        if (!$shiftTimes) {
            return ['late_minutes' => 0, 'undertime_minutes' => 0];
        }

        [$date, $scheduledIn, $scheduledOut] = $shiftTimes;

        $lateMinutes = 0;
        if ($clockIn) {
            $actualIn = $this->dateTimeFor($date, $clockIn);
            if ($actualIn->gt($scheduledIn)) {
                $lateMinutes = $this->diffMinutes($scheduledIn, $actualIn);
            }
        }

        $undertimeMinutes = 0;
        if ($clockOut) {
            $actualOut = $this->normalizeClockOut($date, $scheduledIn, $scheduledOut, $clockOut);
            if ($actualOut->lt($scheduledOut)) {
                $undertimeMinutes = $this->diffMinutes($actualOut, $scheduledOut);
            }
        }

        return [
            'late_minutes' => max(0, $lateMinutes),
            'undertime_minutes' => max(0, $undertimeMinutes),
        ];
    }

    protected function shiftTimes(Employee $employee, string|Carbon $workDate): ?array
    {
        $employee->loadMissing('employeeShift.shift');
        $shift = $employee->employeeShift?->shift;

        if (!$shift) {
            return null;
        }

        $date = $this->dateStart($workDate);
        $scheduledIn = $this->dateTimeFor($date, $shift->time_in);
        $scheduledOut = $this->dateTimeFor($date, $shift->time_out);

        if ($scheduledOut->lte($scheduledIn)) {
            $scheduledOut->addDay();
        }

        return [$date, $scheduledIn, $scheduledOut];
    }

    protected function normalizeClockOut(
        Carbon $date,
        Carbon $scheduledIn,
        Carbon $scheduledOut,
        string $clockOut
    ): Carbon {
        $actualOut = $this->dateTimeFor($date, $clockOut);

        if ($scheduledOut->toDateString() !== $date->toDateString() && $actualOut->lt($scheduledIn)) {
            $actualOut->addDay();
        }

        return $actualOut;
    }

    protected function minutesLateAgainstCutoff(string|Carbon $workDate, string $clockIn): int
    {
        $date = $this->dateStart($workDate);
        $cutoff = $this->dateTimeFor($date, (string) config('attendance.flexible_cutoff_time', '11:00'));
        $actualIn = $this->dateTimeFor($date, $clockIn);

        if ($actualIn->lte($cutoff)) {
            return 0;
        }

        // Flexible break may happen at any time; it never reduces lateness.
        return max(0, $this->diffMinutes($cutoff, $actualIn));
    }

    /**
     * New October policy: break timing is not tracked. Deduct the standard
     * configured break duration from the total Time In -> Time Out span.
     */
    protected function calculateFlexibleUndertime(
        string|Carbon $workDate,
        ?string $clockIn,
        ?string $clockOut
    ): int {
        if (!$clockIn || !$clockOut) {
            return 0;
        }

        $date = $this->dateStart($workDate);
        $actualIn = $this->dateTimeFor($date, $clockIn);
        $actualOut = $this->dateTimeFor($date, $clockOut);

        if ($actualOut->lte($actualIn)) {
            $actualOut->addDay();
        }

        $elapsedMinutes = $this->diffMinutes($actualIn, $actualOut);
        $breakMinutes = max(0, (int) config('attendance.break_minutes', 60));
        $workedMinutes = max(0, $elapsedMinutes - $breakMinutes);
        $requiredMinutes = max(0, (int) config('attendance.required_work_minutes', 480));

        return max(0, $requiredMinutes - $workedMinutes);
    }

    /**
     * Pre-October-1 flexible computation retained for historical DTR behavior.
     */
    protected function calculateLegacyFlexibleUndertime(
        string|Carbon $workDate,
        ?string $clockIn,
        ?string $clockOut
    ): int {
        if (!$clockIn || !$clockOut) {
            return 0;
        }

        $date = $this->dateStart($workDate);
        $actualIn = $this->dateTimeFor($date, $clockIn);
        $actualOut = $this->dateTimeFor($date, $clockOut);

        if ($actualOut->lte($actualIn)) {
            $actualOut->addDay();
        }

        $workedMinutes = max(
            0,
            $this->diffMinutes($actualIn, $actualOut)
            - $this->legacyBreakOverlapMinutes($date, $actualIn, $actualOut)
        );

        $requiredMinutes = max(0, (int) config('attendance.required_work_minutes', 480));

        return max(0, $requiredMinutes - $workedMinutes);
    }

    protected function effectiveDate(): Carbon
    {
        return Carbon::parse((string) config('attendance.policy_effective_date', '2026-10-01'))->startOfDay();
    }

    protected function dateStart(string|Carbon $workDate): Carbon
    {
        return $workDate instanceof Carbon
            ? $workDate->copy()->startOfDay()
            : Carbon::parse($workDate)->startOfDay();
    }

    protected function dateTimeFor(Carbon $date, mixed $time): Carbon
    {
        $timeString = is_object($time) && method_exists($time, 'format')
            ? $time->format('H:i:s')
            : (string) $time;

        return Carbon::parse($date->toDateString() . ' ' . $timeString);
    }

    protected function diffMinutes(Carbon $start, Carbon $end): int
    {
        return (int) round($start->diffInMinutes($end));
    }

    protected function legacyBreakOverlapMinutes(Carbon $date, Carbon $start, Carbon $end): int
    {
        if ($end->lte($start)) {
            return 0;
        }

        $breakStart = $this->dateTimeFor(
            $date,
            (string) config('attendance.legacy_break_start', '12:00')
        );
        $breakEnd = $this->dateTimeFor(
            $date,
            (string) config('attendance.legacy_break_end', '13:00')
        );

        if ($breakEnd->lte($breakStart)) {
            $breakEnd->addDay();
        }

        $overlapStart = $start->gt($breakStart) ? $start->copy() : $breakStart->copy();
        $overlapEnd = $end->lt($breakEnd) ? $end->copy() : $breakEnd->copy();

        if ($overlapEnd->lte($overlapStart)) {
            return 0;
        }

        return $this->diffMinutes($overlapStart, $overlapEnd);
    }

    protected function normalizedInterval(Carbon $date, mixed $start, mixed $end): array
    {
        $startAt = $this->dateTimeFor($date, $start);
        $endAt = $this->dateTimeFor($date, $end);

        if ($endAt->lte($startAt)) {
            $endAt->addDay();
        }

        return [$startAt, $endAt];
    }

    /**
     * Merge overlapping or directly adjacent work intervals.
     *
     * @param array<int, array{0: Carbon, 1: Carbon}> $intervals
     * @return array<int, array{0: Carbon, 1: Carbon}>
     */
    protected function mergeIntervals(array $intervals): array
    {
        usort($intervals, fn (array $a, array $b) => $a[0]->getTimestamp() <=> $b[0]->getTimestamp());

        $merged = [];

        foreach ($intervals as [$start, $end]) {
            if ($merged === []) {
                $merged[] = [$start->copy(), $end->copy()];
                continue;
            }

            $lastIndex = count($merged) - 1;
            [$lastStart, $lastEnd] = $merged[$lastIndex];

            if ($start->lte($lastEnd)) {
                if ($end->gt($lastEnd)) {
                    $merged[$lastIndex][1] = $end->copy();
                }
                continue;
            }

            $merged[] = [$start->copy(), $end->copy()];
        }

        return $merged;
    }

    protected function positionMatches(?string $position, array $keywords): bool
    {
        $position = mb_strtolower(trim((string) $position));

        if ($position === '') {
            return false;
        }

        foreach ($keywords as $keyword) {
            $keyword = mb_strtolower(trim((string) $keyword));

            if ($keyword !== '' && str_contains($position, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
