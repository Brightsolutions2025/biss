<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OvertimeRequest>
 */
class OvertimeRequestFactory extends Factory
{
    protected $model = OvertimeRequest::class;

    public function definition(): array
    {
        $date = $this->faker->date();
        $startTime = $this->faker->dateTimeBetween("$date 17:00:00", "$date 20:00:00");
        $endTime = (clone $startTime)->modify('+2 hours');

        return [
            'company_id'        => Company::factory(),
            'employee_id'       => Employee::factory(),
            'date'              => $date,
            'time_start'        => $startTime->format('H:i'),
            'time_end'          => $endTime->format('H:i'),
            'number_of_hours'   => round(($endTime->getTimestamp() - $startTime->getTimestamp()) / 3600, 2),
            'reason'            => $this->faker->sentence,
            'status'            => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'approver_id'       => User::factory(),
            'approval_date'     => $this->faker->optional()->date(),
            'rejection_reason'  => $this->faker->optional()->sentence,
            'expires_at'        => $this->faker->optional()->date(),
        ];
    }
}
