<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Role $role;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a company
        $this->company = Company::factory()->create();

        // Create an admin user
        $this->adminUser = User::factory()->create();
        $this->adminUser->companies()->attach($this->company->id);

        // Give admin user a company preference
        UserPreference::factory()->create([
            'user_id'    => $this->adminUser->id,
            'company_id' => $this->company->id,
        ]);

        // Create an admin role for this company
        $this->role = Role::factory()->create([
            'name'       => 'admin',
            'company_id' => $this->company->id,
        ]);

        $this->adminUser->roles()->attach($this->role->id, ['company_id' => $this->company->id]);

        // Attach basic permissions (adjust if you have permission checks)
        $permissions = collect([
            'user.browse',
            'user.create',
            'user.read',
            'user.update',
            'user.delete',
        ])->map(function ($name) {
            return \App\Models\Permission::create([
                'name'       => $name,
                'company_id' => $this->company->id,
            ]);
        });

        $this->role->permissions()->attach($permissions->pluck('id'), ['company_id' => $this->company->id]);
    }

    /** @test */
    public function it_displays_user_index()
    {
        User::factory()->count(3)->create();

        $this->actingAs($this->adminUser)
            ->get(route('users.index'))
            ->assertOk()
            ->assertViewIs('users.index');
    }

    /** @test */
    public function it_displays_create_form()
    {
        $this->actingAs($this->adminUser)
            ->get(route('users.create'))
            ->assertOk()
            ->assertViewIs('users.create');
    }

    /** @test */
    public function it_stores_a_user()
    {
        $this->actingAs($this->adminUser);

        $data = [
            'name'       => 'New User',
            'email'      => 'newuser@example.com',
            'password'   => 'password123',
            'rolesInput' => (string) $this->role->id, // match regex validation
        ];

        $response = $this->post(route('users.store'), $data);

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
        ]);

        $this->assertDatabaseHas('company_user', [
            'company_id' => $this->company->id,
            'user_id'    => User::where('email', 'newuser@example.com')->first()->id,
        ]);
    }

    /** @test */
    public function it_displays_a_specific_user()
    {
        $user = User::factory()->create();
        $user->companies()->attach($this->company->id);
        UserPreference::factory()->create(['user_id' => $user->id, 'company_id' => $this->company->id]);

        $this->actingAs($this->adminUser)
            ->get(route('users.show', $user))
            ->assertOk()
            ->assertViewIs('users.show');
    }

    /** @test */
    public function it_displays_edit_form()
    {
        $user = User::factory()->create();
        $user->companies()->attach($this->company->id);
        UserPreference::factory()->create(['user_id' => $user->id, 'company_id' => $this->company->id]);

        $this->actingAs($this->adminUser)
            ->get(route('users.edit', $user))
            ->assertOk()
            ->assertViewIs('users.edit');
    }

    /** @test */
    public function it_updates_a_user()
    {
        $user = User::factory()->create(['name' => 'Old Name']);
        $user->companies()->attach($this->company->id);
        UserPreference::factory()->create(['user_id' => $user->id, 'company_id' => $this->company->id]);

        $this->actingAs($this->adminUser);

        $response = $this->put(route('users.update', $user), [
            'name'       => 'Updated Name',
            'email'      => 'updated@example.com',
            'rolesInput' => (string) $this->role->id,
        ]);

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id'    => $user->id,
            'name'  => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    /** @test */
    public function it_deletes_a_user()
    {
        $user = User::factory()->create();
        $user->companies()->attach($this->company->id);
        UserPreference::factory()->create(['user_id' => $user->id, 'company_id' => $this->company->id]);

        $this->actingAs($this->adminUser);

        $response = $this->delete(route('users.destroy', $user));

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }
}
