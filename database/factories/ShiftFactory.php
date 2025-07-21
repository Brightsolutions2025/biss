<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shift>
 */
class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        return [
            'name'           => $this->faker->word . ' Shift',
            'time_in'        => $this->faker->time('H:i'),
            'time_out'       => $this->faker->time('H:i'),
            'is_night_shift' => $this->faker->boolean,
            'company_id'     => Company::factory(),
        ];
    }
}
