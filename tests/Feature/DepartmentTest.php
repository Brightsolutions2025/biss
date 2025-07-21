<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentTest extends TestCase
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

        // Assume 'admin' role has all permissions
        $permissions = collect([
            'department.browse',
            'department.create',
            'department.read',
            'department.update',
            'department.delete',
        ])->map(function ($name) {
            return \App\Models\Permission::create([
                'name'       => $name,
                'company_id' => $this->company->id,
            ]);
        });

        $role->permissions()->attach($permissions->pluck('id'), ['company_id' => $this->company->id]);
    }

    /** @test */
    public function it_displays_department_index()
    {
        Department::factory()->count(3)->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('departments.index'))
            ->assertOk()
            ->assertViewIs('departments.index');
    }

    /** @test */
    public function it_displays_create_form()
    {
        $this->actingAs($this->user)
            ->get(route('departments.create'))
            ->assertOk()
            ->assertViewIs('departments.create');
    }

    /** @test */
    public function it_stores_a_department()
    {
        $this->actingAs($this->user);

        $data = [
            'name'        => 'HR Department',
            'description' => 'Handles HR tasks',
        ];

        $response = $this->post(route('departments.store'), $data);

        $response->assertRedirect(route('departments.index'));
        $this->assertDatabaseHas('departments', [
            'name'       => 'HR Department',
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function it_displays_a_specific_department()
    {
        $department = Department::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('departments.show', $department))
            ->assertOk()
            ->assertViewIs('departments.show');
    }

    /** @test */
    public function it_displays_edit_form()
    {
        $department = Department::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('departments.edit', $department))
            ->assertOk()
            ->assertViewIs('departments.edit');
    }

    /** @test */
    public function it_updates_a_department()
    {
        $department = Department::factory()->create([
            'name'       => 'Old Name',
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->put(route('departments.update', $department), [
            'name'        => 'Updated Name',
            'description' => 'Updated description',
        ]);

        $response->assertRedirect(route('departments.index'));
        $this->assertDatabaseHas('departments', [
            'id'   => $department->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function it_deletes_a_department()
    {
        $department = Department::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user);

        $response = $this->delete(route('departments.destroy', $department));

        $response->assertRedirect(route('departments.index'));
        $this->assertDatabaseMissing('departments', [
            'id' => $department->id,
        ]);
    }
}
