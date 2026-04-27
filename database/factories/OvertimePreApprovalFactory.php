<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\OvertimePreApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OvertimePreApproval>
 */
class OvertimePreApprovalFactory extends Factory
{
    protected $model = OvertimePreApproval::class;

    public function definition(): array
    {
        $startHour = $this->faker->numberBetween(18, 21);
        $hours = $this->faker->numberBetween(1, 3);

        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),

            'date' => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'planned_time_start' => sprintf('%02d:00', $startHour),
            'planned_time_end' => sprintf('%02d:00', $startHour + $hours),
            'estimated_number_of_hours' => $hours,

            'reason' => $this->faker->sentence,
            'planned_tasks' => $this->faker->paragraph,

            'status' => 'pending',
            'approver_id' => null,
            'approval_date' => null,
            'rejection_reason' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approver_id' => User::factory(),
            'approval_date' => now()->toDateString(),
            'rejection_reason' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'approver_id' => User::factory(),
            'approval_date' => null,
            'rejection_reason' => $this->faker->sentence,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'approval_date' => null,
            'rejection_reason' => null,
        ]);
    }
}