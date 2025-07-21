<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'company_id'      => Company::factory(),
            'name'            => $this->faker->company,
            'contact_person'  => $this->faker->name,
            'email'           => $this->faker->unique()->safeEmail,
            'phone'           => $this->faker->phoneNumber,
            'address'         => $this->faker->address,
            'billing_address' => $this->faker->address,
            'industry'        => $this->faker->word,
            'tin'             => $this->faker->numerify('###-###-###'),
            'category'        => $this->faker->randomElement(['A', 'B', 'C']),
            'client_type'     => $this->faker->randomElement(['corporate', 'government', 'individual']),
            'website'         => $this->faker->url,
            'notes'           => $this->faker->paragraph,
            'rating'          => $this->faker->numberBetween(1, 5),
            'is_active'       => $this->faker->boolean(90),
            'payment_terms'   => $this->faker->randomElement(['30 days', '60 days', '90 days']),
            'credit_limit'    => $this->faker->randomFloat(2, 10000, 500000),
        ];
    }
}
