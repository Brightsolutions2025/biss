<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PayrollPeriod>
 */
class PayrollPeriodFactory extends Factory
{
    protected $model = PayrollPeriod::class;

    public function definition(): array
    {
        $startDate = Carbon::instance($this->faker->dateTimeBetween('-2 months', 'now'));
        $endDate = (clone $startDate)->addDays(15);

        return [
            'company_id' => Company::factory(),
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'dtr_submission_due_at' => $endDate->copy()->addDay()->toDateTimeString(),
            'reminder_sent_at' => null, // Or use: $this->faker->optional()->dateTimeBetween($startDate, $endDate)
        ];
    }
}
