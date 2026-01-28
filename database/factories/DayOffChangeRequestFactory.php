<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\DayOffChangeRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DayOffChangeRequest>
 */
class DayOffChangeRequestFactory extends Factory
{
    protected $model = DayOffChangeRequest::class;

    public function definition(): array
    {
        // Create an employee with company if not provided
        $employee = Employee::factory()->create();

        $startTime = $this->faker->time('H:i');
        $endTime   = Carbon::createFromFormat('H:i', $startTime)->addHours($this->faker->numberBetween(1, 8))->format('H:i');

        $extensionDate = $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d');
        $oldDate = $this->faker->dateTimeBetween('-2 months', '-1 month')->format('Y-m-d');
        $newDate = $this->faker->dateTimeBetween('now', '+1 month')->format('Y-m-d');

        return [
            'company_id'       => $employee->company_id,
            'employee_id'      => $employee->id,
            'reason_type'      => $this->faker->randomElement([
                'Offset due to Extension of Work',
                'Schedule / Day Off Adjustment'
            ]),
            'extension_date'   => $extensionDate,
            'time_start'       => $startTime,
            'time_end'         => $endTime,
            'number_of_hours'  => Carbon::createFromFormat('H:i', $endTime)->diffInHours(Carbon::createFromFormat('H:i', $startTime)),
            'old_date'         => $oldDate,
            'new_date'         => $newDate,
            'reason'           => $this->faker->sentence,
            'status'           => 'pending',
            'approver_id'      => User::factory(), // nullable; will create a user if needed
            'approval_date'    => null,
            'rejection_reason' => null,
        ];
    }
}
