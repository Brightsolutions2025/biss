<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\TimeRecord;
use App\Models\TimeRecordLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeRecordLine>
 */
class TimeRecordLineFactory extends Factory
{
    protected $model = TimeRecordLine::class;

    public function definition(): array
    {
        return [
            'time_record_id'    => TimeRecord::factory(),
            'company_id'        => Company::factory(), // Correct use after importing Company
            'date'              => now()->toDateString(),
            'clock_in'          => '08:00:00',
            'clock_out'         => '17:00:00',
            'late_minutes'      => 0,
            'undertime_minutes' => 0,
            'remarks'           => $this->faker->sentence,
        ];
    }
}
