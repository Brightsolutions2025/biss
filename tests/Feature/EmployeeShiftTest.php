<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeShiftTest extends TestCase
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
            'employee_shift.browse',
            'employee_shift.create',
            'employee_shift.read',
            'employee_shift.update',
            'employee_shift.delete',
        ])->map(fn ($name) => Permission::create([
            'name'       => $name,
            'company_id' => $this->company->id,
        ]));

        $role->permissions()->attach($permissions->pluck('id'), ['company_id' => $this->company->id]);
    }

    /** @test */
    public function it_displays_employee_shift_index()
    {
        EmployeeShift::factory()->count(2)->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('employee_shifts.index'))
            ->assertOk()
            ->assertViewIs('employee_shifts.index');
    }

    /** @test */
    public function it_displays_employee_shift_create_form()
    {
        $this->actingAs($this->user)
            ->get(route('employee_shifts.create'))
            ->assertOk()
            ->assertViewIs('employee_shifts.create');
    }

    /** @test */
    public function it_stores_an_employee_shift()
    {
        $this->actingAs($this->user);

        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $shift    = Shift::factory()->create(['company_id' => $this->company->id]);

        $response = $this->post(route('employee_shifts.store'), [
            'employee_id' => $employee->id,
            'shift_id'    => $shift->id,
        ]);

        $response->assertRedirect(route('employee_shifts.index'));
        $this->assertDatabaseHas('employee_shifts', [
            'employee_id' => $employee->id,
            'shift_id'    => $shift->id,
        ]);
    }

    /** @test */
    public function it_shows_an_employee_shift()
    {
        $employeeShift = EmployeeShift::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('employee_shifts.show', $employeeShift))
            ->assertOk()
            ->assertViewIs('employee_shifts.show');
    }

    /** @test */
    public function it_displays_employee_shift_edit_form()
    {
        $employeeShift = EmployeeShift::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('employee_shifts.edit', $employeeShift))
            ->assertOk()
            ->assertViewIs('employee_shifts.edit');
    }

    /** @test */
    public function it_updates_an_employee_shift()
    {
        $employeeShift = EmployeeShift::factory()->create(['company_id' => $this->company->id]);
        $newShift      = Shift::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user);

        $response = $this->put(route('employee_shifts.update', $employeeShift), [
            'employee_id' => $employeeShift->employee_id,
            'shift_id'    => $newShift->id,
        ]);

        $response->assertRedirect(route('employee_shifts.index'));
        $this->assertDatabaseHas('employee_shifts', [
            'id'       => $employeeShift->id,
            'shift_id' => $newShift->id,
        ]);
    }

    /** @test */
    public function it_deletes_an_employee_shift()
    {
        $employeeShift = EmployeeShift::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user);

        $response = $this->delete(route('employee_shifts.destroy', $employeeShift));

        $response->assertRedirect(route('employee_shifts.index'));
        $this->assertDatabaseMissing('employee_shifts', ['id' => $employeeShift->id]);
    }
}
