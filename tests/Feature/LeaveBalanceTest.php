<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveBalanceTest extends TestCase
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
            'leave_balance.browse',
            'leave_balance.create',
            'leave_balance.read',
            'leave_balance.update',
            'leave_balance.delete',
        ])->map(fn ($name) => Permission::create([
            'name' => $name,
            'company_id' => $this->company->id,
        ]));

        $role->permissions()->attach($permissions->pluck('id'), ['company_id' => $this->company->id]);
    }

    /** @test */
    public function it_displays_leave_balance_index()
    {
        LeaveBalance::factory()->count(2)->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('leave_balances.index'))
            ->assertOk()
            ->assertViewIs('leave_balances.index');
    }

    /** @test */
    public function it_displays_leave_balance_create_form()
    {
        $this->actingAs($this->user)
            ->get(route('leave_balances.create'))
            ->assertOk()
            ->assertViewIs('leave_balances.create');
    }

    /** @test */
    public function it_stores_a_leave_balance()
    {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user);

        $data = [
            'employee_id' => $employee->id,
            'year' => 2025,
            'beginning_balance' => 10,
        ];

        $response = $this->post(route('leave_balances.store'), $data);

        $response->assertRedirect(route('leave_balances.index'));
        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $data['employee_id'],
            'year' => $data['year'],
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function it_shows_a_leave_balance()
    {
        $leaveBalance = LeaveBalance::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('leave_balances.show', $leaveBalance))
            ->assertOk()
            ->assertViewIs('leave_balances.show');
    }

    /** @test */
    public function it_displays_leave_balance_edit_form()
    {
        $leaveBalance = LeaveBalance::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('leave_balances.edit', $leaveBalance))
            ->assertOk()
            ->assertViewIs('leave_balances.edit');
    }

    /** @test */
    public function it_updates_a_leave_balance()
    {
        $leaveBalance = LeaveBalance::factory()->create(['company_id' => $this->company->id]);
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user);

        $data = [
            'employee_id' => $employee->id,
            'year' => 2026,
            'beginning_balance' => 15,
        ];

        $response = $this->put(route('leave_balances.update', $leaveBalance), $data);

        $response->assertRedirect(route('leave_balances.index'));
        $this->assertDatabaseHas('leave_balances', [
            'id' => $leaveBalance->id,
            'employee_id' => $data['employee_id'],
            'year' => $data['year'],
            'beginning_balance' => $data['beginning_balance'],
        ]);
    }

    /** @test */
    public function it_deletes_a_leave_balance()
    {
        $leaveBalance = LeaveBalance::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user);

        $response = $this->delete(route('leave_balances.destroy', $leaveBalance));

        $response->assertRedirect(route('leave_balances.index'));
        $this->assertDatabaseMissing('leave_balances', ['id' => $leaveBalance->id]);
    }
}
