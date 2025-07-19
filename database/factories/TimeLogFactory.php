<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PayrollPeriod;
use App\Models\TimeLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeLog>
 */
class TimeLogFactory extends Factory
{
    protected $model = TimeLog::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'payroll_period_id' => PayrollPeriod::factory(),
            'employee_name' => $this->faker->name,
            'department_name' => $this->faker->word,
            'employee_id' => strtoupper($this->faker->bothify('???##')),
            'employee_type' => $this->faker->randomElement(['Regular', 'Probationary']),
            'attendance_group' => $this->faker->randomElement(['Group A', 'Group B']),
            'date' => $this->faker->date(),
            'weekday' => $this->faker->dayOfWeek,
            'shift' => $this->faker->randomElement(['Morning', 'Night']),
            'attendance_time' => $this->faker->dateTime,
            'about_the_record' => $this->faker->randomElement(['Check-in', 'Check-out']),
            'attendance_result' => $this->faker->randomElement(['Present', 'Late', 'Absent']),
            'attendance_address' => $this->faker->address,
            'note' => $this->faker->sentence, // ← REQUIRED
            'attendance_method' => $this->faker->randomElement(['Mobile App', 'Manual']),
            'attendance_photo' => $this->faker->imageUrl(640, 480, 'people'),
        ];
    }
}
