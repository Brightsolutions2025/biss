<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyUserTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user    = User::factory()->create();
        $this->user->companies()->attach($this->company->id);

        UserPreference::factory()->create([
            'user_id'    => $this->user->id,
            'company_id' => $this->company->id,
        ]);

        $role = Role::factory()->create(['name' => 'admin']);
        $this->user->roles()->attach($role->id, ['company_id' => $this->company->id]);

        $permissions = collect([
            'company_user.browse',
            'company_user.create',
            'company_user.read',
            'company_user.update',
            'company_user.delete',
        ])->map(function ($name) {
            return Permission::create([
                'name'       => $name,
                'company_id' => $this->company->id,
            ]);
        });

        $role->permissions()->attach($permissions->pluck('id'), ['company_id' => $this->company->id]);
    }

    /** @test */
    public function it_displays_company_user_index()
    {
        CompanyUser::create([
            'company_id' => $this->company->id,
            'user_id'    => User::factory()->create()->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('company_users.index'))
            ->assertOk()
            ->assertViewIs('company_users.index');
    }

    /** @test */
    public function it_displays_create_form()
    {
        $this->actingAs($this->user)
            ->get(route('company_users.create'))
            ->assertOk()
            ->assertViewIs('company_users.create');
    }

    /** @test */
    public function it_stores_a_company_user()
    {
        $this->actingAs($this->user);

        $newUser = User::factory()->create();

        $response = $this->post(route('company_users.store'), [
            'user_id'    => $newUser->id,
            'company_id' => $this->company->id,
        ]);

        $response->assertRedirect(route('company_users.index'));

        $this->assertDatabaseHas('company_user', [
            'company_id' => $this->company->id,
            'user_id'    => $newUser->id,
        ]);
    }

    /** @test */
    public function it_displays_a_specific_company_user()
    {
        $newUser = User::factory()->create();
        CompanyUser::create([
            'company_id' => $this->company->id,
            'user_id'    => $newUser->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('company_users.show', [$this->company->id, $newUser->id]))
            ->assertOk()
            ->assertViewIs('company_users.show');
    }

    /** @test */
    public function it_displays_edit_form()
    {
        $newUser = User::factory()->create();
        CompanyUser::create([
            'company_id' => $this->company->id,
            'user_id'    => $newUser->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('company_users.edit', [$this->company->id, $newUser->id]))
            ->assertOk()
            ->assertViewIs('company_users.edit');
    }

    /** @test */
    public function it_updates_a_company_user()
    {
        $newUser = User::factory()->create();
        CompanyUser::create([
            'company_id' => $this->company->id,
            'user_id'    => $newUser->id,
        ]);

        $updatedUser = User::factory()->create();

        $this->actingAs($this->user);

        $response = $this->put(route('company_users.update', [$this->company->id, $newUser->id]), [
            'user_id'    => $updatedUser->id,
            'company_id' => $this->company->id,
        ]);

        $response->assertRedirect(route('company_users.index'));

        $this->assertDatabaseHas('company_user', [
            'company_id' => $this->company->id,
            'user_id'    => $updatedUser->id,
        ]);
    }

    /** @test */
    public function it_deletes_a_company_user()
    {
        // Create a new user
        $newUser = User::factory()->create();

        // Attach the user to the company using the relationship
        $this->company->users()->attach($newUser->id);

        // Retrieve the pivot record for route model binding
        $companyUser = CompanyUser::where('company_id', $this->company->id)
            ->where('user_id', $newUser->id)
            ->firstOrFail();

        // Act as an authenticated user
        $this->actingAs($this->user);

        // Send DELETE request to the destroy route
        $response = $this->delete(route('company_users.destroy', $companyUser));

        // Assert redirection
        $response->assertRedirect(route('company_users.index'));

        // Assert pivot record is deleted
        $this->assertDatabaseMissing('company_user', [
            'company_id' => $this->company->id,
            'user_id'    => $newUser->id,
        ]);
    }

}
