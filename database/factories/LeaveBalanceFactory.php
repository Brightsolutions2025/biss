<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaveBalance>
 */
class LeaveBalanceFactory extends Factory
{
    protected $model = LeaveBalance::class;

    public function definition(): array
    {
        return [
            'company_id'        => Company::factory(),
            'employee_id'       => Employee::factory(),
            'year'              => $this->faker->numberBetween(2020, 2030),
            'beginning_balance' => $this->faker->numberBetween(0, 30),
        ];
    }
}
