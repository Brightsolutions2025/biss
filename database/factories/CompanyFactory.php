<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'                     => $this->faker->company,
            'industry'                 => $this->faker->word,
            'address'                  => $this->faker->address,
            'phone'                    => $this->faker->phoneNumber,
            'offset_valid_after_days'  => 90,
            'offset_valid_before_days' => 0,
        ];
    }
}
