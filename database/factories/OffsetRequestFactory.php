<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\OffsetRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OffsetRequest>
 */
class OffsetRequestFactory extends Factory
{
    protected $model = OffsetRequest::class;

    public function definition(): array
    {
        $startTime = $this->faker->time('H:i:s');
        $endTime   = date('H:i:s', strtotime($startTime) + 2 * 3600); // +2 hours

        return [
            'company_id'                   => Company::factory(),
            'employee_id'                  => Employee::factory(),
            'date'                         => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'project_or_event_description' => $this->faker->sentence(6),
            'time_start'                   => $startTime,
            'time_end'                     => $endTime,
            'number_of_hours'              => 2.00,
            'reason'                       => $this->faker->optional()->sentence(),
            'status'                       => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'approver_id'                  => User::factory(),
            'approval_date'                => $this->faker->optional()->date(),
            'rejection_reason'             => $this->faker->optional()->sentence(),
        ];
    }
}
