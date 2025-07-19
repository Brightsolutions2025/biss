<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\OutbaseRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutbaseRequest>
 */
class OutbaseRequestFactory extends Factory
{
    protected $model = OutbaseRequest::class;

    public function definition(): array
    {
        $startTime = $this->faker->time('H:i');
        $endTime = $this->faker->dateTimeBetween("+1 hour", "+3 hours")->format('H:i');

        return [
            'company_id'       => Company::factory(),
            'employee_id'      => Employee::factory(),
            'date'             => $this->faker->date(),
            'time_start'       => $startTime,
            'time_end'         => $endTime,
            'location'         => $this->faker->address,
            'reason'           => $this->faker->sentence,
            'status'           => 'pending',
            'approver_id'      => User::factory(),
            'approval_date'    => null,
            'rejection_reason' => null,
        ];
    }
}
