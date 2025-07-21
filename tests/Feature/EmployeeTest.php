<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTest extends TestCase
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
            'employee.browse',
            'employee.create',
            'employee.read',
            'employee.update',
            'employee.delete',
        ])->map(fn ($name) => \App\Models\Permission::create([
            'name'       => $name,
            'company_id' => $this->company->id,
        ]));

        $role->permissions()->attach($permissions->pluck('id'), ['company_id' => $this->company->id]);
    }

    /** @test */
    public function it_displays_employee_index()
    {
        Employee::factory()->count(3)->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertViewIs('employees.index');
    }

    /** @test */
    public function it_displays_employee_create_form()
    {
        $this->actingAs($this->user)
            ->get(route('employees.create'))
            ->assertOk()
            ->assertViewIs('employees.create');
    }

    /** @test */
    public function it_stores_an_employee()
    {
        $this->actingAs($this->user);

        $department   = Department::factory()->create(['company_id' => $this->company->id]);
        $team         = Team::factory()->create(['company_id' => $this->company->id, 'department_id' => $department->id]);
        $employeeUser = User::factory()->create();

        $response = $this->post(route('employees.store'), [
            'user_id'         => $employeeUser->id,
            'employee_number' => 'EMP001',
            'first_name'      => 'John',
            'last_name'       => 'Doe',
            'department_id'   => $department->id,
            'team_id'         => $team->id,
        ]);

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('employees', [
            'employee_number' => 'EMP001',
            'user_id'         => $employeeUser->id,
        ]);
    }

    /** @test */
    public function it_shows_an_employee()
    {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('employees.show', $employee))
            ->assertOk()
            ->assertViewIs('employees.show');
    }

    /** @test */
    public function it_displays_edit_form_for_employee()
    {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('employees.edit', $employee))
            ->assertOk()
            ->assertViewIs('employees.edit');
    }

    /** @test */
    public function it_updates_an_employee()
    {
        $employee = Employee::factory()->create([
            'first_name' => 'Old',
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->put(route('employees.update', $employee), [
            'user_id'         => $employee->user_id,
            'employee_number' => $employee->employee_number,
            'first_name'      => 'Updated',
            'last_name'       => $employee->last_name,
        ]);

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('employees', [
            'id'         => $employee->id,
            'first_name' => 'Updated',
        ]);
    }

    /** @test */
    public function it_deletes_an_employee()
    {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user);

        $response = $this->delete(route('employees.destroy', $employee));

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
    }
}
