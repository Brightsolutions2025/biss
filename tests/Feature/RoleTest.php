<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
        $this->user->companies()->attach($this->company->id);

        UserPreference::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
        ]);

        $adminRole = Role::factory()->create(['name' => 'admin', 'company_id' => $this->company->id]);
        $this->user->roles()->attach($adminRole->id, ['company_id' => $this->company->id]);
    }

    /** @test */
    public function it_displays_role_index()
    {
        Role::factory()->count(3)->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('roles.index'))
            ->assertOk()
            ->assertViewIs('roles.index');
    }

    /** @test */
    public function it_displays_create_form()
    {
        $this->actingAs($this->user)
            ->get(route('roles.create'))
            ->assertOk()
            ->assertViewIs('roles.create');
    }

    /** @test */
    public function it_stores_a_role()
    {
        $this->actingAs($this->user);

        $permissions = Permission::factory()->count(2)->create(['company_id' => $this->company->id]);

        $data = [
            'name' => 'Manager',
            'description' => 'Manages things',
            'permissionsInput' => $permissions->pluck('id')->implode(','),
        ];

        $response = $this->post(route('roles.store'), $data);

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseHas('roles', [
            'name' => 'Manager',
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function it_displays_a_specific_role()
    {
        $role = Role::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('roles.show', $role))
            ->assertOk()
            ->assertViewIs('roles.show');
    }

    /** @test */
    public function it_displays_edit_form()
    {
        $role = Role::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('roles.edit', $role))
            ->assertOk()
            ->assertViewIs('roles.edit');
    }

    /** @test */
    public function it_updates_a_role()
    {
        $role = Role::factory()->create(['company_id' => $this->company->id]);
        $permissions = Permission::factory()->count(2)->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user);

        $data = [
            'name' => 'Updated Role',
            'description' => 'Updated description',
            'permissionsInput' => $permissions->pluck('id')->implode(','),
        ];

        $response = $this->put(route('roles.update', $role), $data);

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Updated Role',
        ]);
    }

    /** @test */
    public function it_deletes_a_role()
    {
        $role = Role::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user);

        $response = $this->delete(route('roles.destroy', $role));

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }
}
