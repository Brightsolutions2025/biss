<?php

namespace Database\Factories;

use App\Models\ClientContact;
use App\Models\Client;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientContact>
 */
class ClientContactFactory extends Factory
{
    protected $model = ClientContact::class;

    public function definition(): array
    {
        return [
            'company_id'   => Company::factory(),
            'client_id'    => Client::factory(),
            'name'         => $this->faker->name,
            'email'        => $this->faker->unique()->safeEmail,
            'phone'        => $this->faker->phoneNumber,
            'position'     => $this->faker->jobTitle,
            'is_primary'   => $this->faker->boolean(30), // ~30% of the time true
            'linkedin_url' => $this->faker->optional()->url,
        ];
    }
}
