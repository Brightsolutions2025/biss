<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PayrollPeriod;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollPeriodTest extends TestCase
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
            'payroll_period.browse',
            'payroll_period.create',
            'payroll_period.read',
            'payroll_period.update',
            'payroll_period.delete',
        ])->map(fn ($name) => Permission::create([
            'name'       => $name,
            'company_id' => $this->company->id,
        ]));

        $role->permissions()->attach($permissions->pluck('id'), ['company_id' => $this->company->id]);
    }

    /** @test */
    public function it_displays_payroll_period_index()
    {
        PayrollPeriod::factory()->count(2)->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('payroll_periods.index'))
            ->assertOk()
            ->assertViewIs('payroll_periods.index');
    }

    /** @test */
    public function it_displays_payroll_period_create_form()
    {
        $this->actingAs($this->user)
            ->get(route('payroll_periods.create'))
            ->assertOk()
            ->assertViewIs('payroll_periods.create');
    }

    /** @test */
    public function it_stores_a_payroll_period()
    {
        $this->actingAs($this->user);

        $data = [
            'start_date'            => '2025-07-01',
            'end_date'              => '2025-07-15',
            'timezone'              => 'UTC',
            'dtr_submission_due_at' => '2025-07-16 12:00:00',
        ];

        $response = $this->post(route('payroll_periods.store'), $data);

        $response->assertRedirect(route('payroll_periods.index'));
        $this->assertDatabaseHas('payroll_periods', [
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function it_shows_a_payroll_period()
    {
        $payrollPeriod = PayrollPeriod::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('payroll_periods.show', $payrollPeriod))
            ->assertOk()
            ->assertViewIs('payroll_periods.show');
    }

    /** @test */
    public function it_displays_payroll_period_edit_form()
    {
        $payrollPeriod = PayrollPeriod::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('payroll_periods.edit', $payrollPeriod))
            ->assertOk()
            ->assertViewIs('payroll_periods.edit');
    }

    /** @test */
    public function it_updates_a_payroll_period()
    {
        $payrollPeriod = PayrollPeriod::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user);

        $data = [
            'start_date'            => '2025-07-16',
            'end_date'              => '2025-07-31',
            'timezone'              => 'UTC',
            'dtr_submission_due_at' => '2025-08-01 10:00:00',
        ];

        $response = $this->put(route('payroll_periods.update', $payrollPeriod), $data);

        $response->assertRedirect(route('payroll_periods.index'));
        $this->assertDatabaseHas('payroll_periods', [
            'id'         => $payrollPeriod->id,
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
        ]);
    }

    /** @test */
    public function it_deletes_a_payroll_period()
    {
        $payrollPeriod = PayrollPeriod::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user);

        $response = $this->delete(route('payroll_periods.destroy', $payrollPeriod));

        $response->assertRedirect(route('payroll_periods.index'));
        $this->assertDatabaseMissing('payroll_periods', ['id' => $payrollPeriod->id]);
    }
}
