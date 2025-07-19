<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 month', 'now');
        $endDate = (clone $startDate)->modify('+'.rand(0, 2).' days');

        return [
            'company_id'      => Company::factory(),
            'employee_id'     => Employee::factory(),
            'start_date'      => $startDate->format('Y-m-d'),
            'end_date'        => $endDate->format('Y-m-d'),
            'number_of_days'  => (new \Carbon\Carbon($startDate))->diffInDays($endDate) + 1,
            'reason'          => $this->faker->sentence,
            'status'          => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'approver_id'     => User::factory(),
            'approval_date'   => $this->faker->optional()->date(),
            'rejection_reason'=> $this->faker->optional()->sentence,
        ];
    }
}
