<?php

namespace Database\Factories;

use App\Models\TicketType;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TicketType>
 */
class TicketTypeFactory extends Factory
{
    protected $model = TicketType::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(), // You can override this in your tests
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'sort_order' => $this->faker->numberBetween(0, 100),
            'is_active' => $this->faker->boolean(90), // 90% chance to be active
        ];
    }
}
