<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id);

        UserPreference::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
        ]);

        $role = Role::factory()->create(['name' => 'admin']);
        $this->user->roles()->attach($role->id, ['company_id' => $this->company->id]);

        $permissions = collect([
            'project.browse',
            'project.create',
            'project.read',
            'project.update',
            'project.delete',
        ])->map(function ($name) {
            return Permission::factory()->create([
                'name' => $name,
                'company_id' => $this->company->id,
            ]);
        });

        $role->permissions()->attach($permissions->pluck('id'), ['company_id' => $this->company->id]);
    }

    /** @test */
    public function it_displays_project_index()
    {
        Project::factory()->count(3)->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertViewIs('projects.index');
    }

    /** @test */
    public function it_displays_create_form()
    {
        $this->actingAs($this->user)
            ->get(route('projects.create'))
            ->assertOk()
            ->assertViewIs('projects.create');
    }

    /** @test */
    public function it_stores_a_project()
    {
        $this->actingAs($this->user);

        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $data = [
            'client_id' => $client->id,
            'project_type' => 'internal',
            'name' => 'New Project',
            'description' => 'Test project description',
            'status' => 'active',
            'budget' => 50000,
            'priority' => 'high',
            'locked_budget' => true,
        ];

        $response = $this->post(route('projects.store'), $data);

        $response->assertRedirect(route('projects.index'));

        $this->assertDatabaseHas('projects', [
            'name' => 'New Project',
            'client_id' => $client->id,
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function it_displays_a_specific_project()
    {
        $project = Project::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertViewIs('projects.show');
    }

    /** @test */
    public function it_displays_edit_form()
    {
        $project = Project::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('projects.edit', $project))
            ->assertOk()
            ->assertViewIs('projects.edit');
    }

    /** @test */
    public function it_updates_a_project()
    {
        $project = Project::factory()->create(['company_id' => $this->company->id]);

        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user);

        $data = [
            'client_id' => $client->id,
            'project_type' => 'external',
            'name' => 'Updated Project',
            'description' => 'Updated description',
            'status' => 'completed',
            'budget' => 100000,
            'priority' => 'medium',
            'locked_budget' => false,
        ];

        $response = $this->put(route('projects.update', $project), $data);

        $response->assertRedirect(route('projects.index'));

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Project',
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_deletes_a_project()
    {
        $project = Project::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user);

        $response = $this->delete(route('projects.destroy', $project));

        $response->assertRedirect(route('projects.index'));

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
    }
}
