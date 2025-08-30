<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketTypeFactory extends Factory
{
    protected $model = TicketType::class;

    public function definition(): array
    {
        return [
            'company_id'   => Company::factory(),
            'name'         => $this->faker->word,
            'description'  => $this->faker->optional()->sentence,
            'sort_order'   => $this->faker->numberBetween(0, 100),
            'is_active'    => $this->faker->boolean(90), // mostly active
            'created_at'   => now(),
            'updated_at'   => now(),
        ];
    }
}
