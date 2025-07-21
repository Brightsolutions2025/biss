<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user    = User::factory()->create();
        $this->company = Company::factory()->create();

        // Attach user to company
        $this->user->companies()->attach($this->company->id);

        // Set user preference
        UserPreference::factory()->create([
            'user_id'    => $this->user->id,
            'company_id' => $this->company->id,
        ]);

        // Create and assign 'admin' role to user
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $this->user->roles()->attach($adminRole->id, ['company_id' => $this->company->id]);
    }

    /** @test */
    public function it_displays_permission_index()
    {
        Permission::factory()->count(3)->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('permissions.index'))
            ->assertOk()
            ->assertViewIs('permissions.index');
    }

    /** @test */
    public function it_displays_create_form()
    {
        $this->actingAs($this->user)
            ->get(route('permissions.create'))
            ->assertOk()
            ->assertViewIs('permissions.create');
    }

    /** @test */
    public function it_stores_a_permission()
    {
        $this->actingAs($this->user);

        $data = [
            'name'        => 'View Reports',
            'description' => 'Can view reports',
        ];

        $response = $this->post(route('permissions.store'), $data);

        $response->assertRedirect(route('permissions.index'));
        $this->assertDatabaseHas('permissions', [
            'name'       => 'View Reports',
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function it_displays_a_specific_permission()
    {
        $permission = Permission::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('permissions.show', $permission))
            ->assertOk()
            ->assertViewIs('permissions.show');
    }

    /** @test */
    public function it_displays_edit_form()
    {
        $permission = Permission::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('permissions.edit', $permission))
            ->assertOk()
            ->assertViewIs('permissions.edit');
    }

    /** @test */
    public function it_updates_a_permission()
    {
        $permission = Permission::factory()->create([
            'name'       => 'Old Name',
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->put(route('permissions.update', $permission), [
            'name'        => 'Updated Name',
            'description' => 'Updated description',
        ]);

        $response->assertRedirect(route('permissions.index'));
        $this->assertDatabaseHas('permissions', [
            'id'   => $permission->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function it_deletes_a_permission()
    {
        $permission = Permission::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user);

        $response = $this->delete(route('permissions.destroy', $permission));

        $response->assertRedirect(route('permissions.index'));
        $this->assertDatabaseMissing('permissions', [
            'id' => $permission->id,
        ]);
    }

    /** @test */
    public function it_does_not_delete_permission_with_roles()
    {
        $permission = Permission::factory()->create(['company_id' => $this->company->id]);

        // Create a role in the same company and attach it to the permission
        $role = \App\Models\Role::factory()->create(['company_id' => $this->company->id]);

        // Attach with required pivot data
        $permission->roles()->attach($role->id, ['company_id' => $this->company->id]);

        $this->actingAs($this->user);

        $response = $this->delete(route('permissions.destroy', $permission));

        $response->assertSessionHasErrors(); // or whatever your app returns on blocked deletion
        $this->assertDatabaseHas('permissions', ['id' => $permission->id]);
    }
}
