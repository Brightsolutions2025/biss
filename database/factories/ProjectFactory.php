<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Project;
use App\Models\Company;
use App\Models\Client;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'client_id' => Client::factory(),
            'project_type' => $this->faker->randomElement(['internal', 'external', 'client-based']),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'location' => $this->faker->address,
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->dateTimeBetween('+1 month', '+1 year')->format('Y-m-d'),
            'status' => $this->faker->randomElement(['planning', 'in-progress', 'completed', 'on-hold']),
            'budget' => $this->faker->randomFloat(2, 10000, 1000000),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            'project_manager_id' => User::factory(),
            'locked_budget' => $this->faker->boolean,
            'budget_buffer' => $this->faker->randomFloat(2, 1000, 50000),
            'created_by' => User::factory(),
            'completion_date_actual' => $this->faker->optional()->date(),
            'risk_level' => $this->faker->randomElement(['low', 'medium', 'high']),
            'tags' => json_encode(['tag1', 'tag2']),
        ];
    }
}
