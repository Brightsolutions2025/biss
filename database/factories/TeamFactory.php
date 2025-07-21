<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'name'          => $this->faker->company,
            'description'   => $this->faker->sentence,
            'company_id'    => Company::factory(), // or null if set in test
            'department_id' => Department::factory(), // or null if set in test
        ];
    }
}
