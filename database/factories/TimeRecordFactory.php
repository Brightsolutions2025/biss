<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\TimeRecord;
use App\Models\TimeRecordLine;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeRecord>
 */
class TimeRecordFactory extends Factory
{
    protected $model = TimeRecord::class;

    public function definition(): array
    {
        return [
            'company_id'        => Company::factory(),
            'employee_id'       => Employee::factory(),
            'payroll_period_id' => PayrollPeriod::factory(),
            'status'            => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'approver_id'       => User::factory(),
            'approval_date'     => $this->faker->optional()->date(),
            'rejection_reason'  => $this->faker->optional()->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'approval_date' => now()->toDateString(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => 'rejected',
            'rejection_reason' => 'Insufficient justification.',
        ]);
    }
}
