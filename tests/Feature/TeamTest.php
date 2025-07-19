<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
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
            'team.browse',
            'team.create',
            'team.read',
            'team.update',
            'team.delete',
        ])->map(function ($name) {
            return \App\Models\Permission::create([
                'name' => $name,
                'company_id' => $this->company->id,
            ]);
        });

        $role->permissions()->attach($permissions->pluck('id'), ['company_id' => $this->company->id]);
    }

    /** @test */
    public function it_displays_team_index()
    {
        $department = Department::factory()->create(['company_id' => $this->company->id]);
        Team::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'department_id' => $department->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('teams.index'))
            ->assertOk()
            ->assertViewIs('teams.index');
    }

    /** @test */
    public function it_displays_create_form()
    {
        $this->actingAs($this->user)
            ->get(route('teams.create'))
            ->assertOk()
            ->assertViewIs('teams.create');
    }

    /** @test */
    public function it_stores_a_team()
    {
        $this->actingAs($this->user);
        $department = Department::factory()->create(['company_id' => $this->company->id]);

        $response = $this->post(route('teams.store'), [
            'department_id' => $department->id,
            'name' => 'Finance Team',
            'description' => 'Handles finances',
        ]);

        $response->assertRedirect(route('teams.index'));
        $this->assertDatabaseHas('teams', [
            'name' => 'Finance Team',
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function it_displays_a_specific_team()
    {
        $department = Department::factory()->create(['company_id' => $this->company->id]);
        $team = Team::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $department->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('teams.show', $team))
            ->assertOk()
            ->assertViewIs('teams.show');
    }

    /** @test */
    public function it_displays_edit_form()
    {
        $department = Department::factory()->create(['company_id' => $this->company->id]);
        $team = Team::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $department->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('teams.edit', $team))
            ->assertOk()
            ->assertViewIs('teams.edit');
    }

    /** @test */
    public function it_updates_a_team()
    {
        $department = Department::factory()->create(['company_id' => $this->company->id]);
        $team = Team::factory()->create([
            'name' => 'Old Name',
            'company_id' => $this->company->id,
            'department_id' => $department->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->put(route('teams.update', $team), [
            'department_id' => $department->id,
            'name' => 'Updated Team',
            'description' => 'Updated description',
        ]);

        $response->assertRedirect(route('teams.index'));
        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => 'Updated Team',
        ]);
    }

    /** @test */
    public function it_deletes_a_team()
    {
        $department = Department::factory()->create(['company_id' => $this->company->id]);
        $team = Team::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $department->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->delete(route('teams.destroy', $team));

        $response->assertRedirect(route('teams.index'));
        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    }
}
