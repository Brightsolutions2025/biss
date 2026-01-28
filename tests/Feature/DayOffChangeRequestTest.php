<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DayOffChangeRequest;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DayOffChangeRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Employee $employee;

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
            'day_off_change_request.browse',
            'day_off_change_request.browse_all',
            'day_off_change_request.create',
            'day_off_change_request.read',
            'day_off_change_request.update',
            'day_off_change_request.delete',
        ])->map(fn ($name) => Permission::create([
            'name'       => $name,
            'company_id' => $this->company->id,
        ]));

        $role->permissions()->attach($permissions->pluck('id'), ['company_id' => $this->company->id]);

        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'user_id'    => $this->user->id,
        ]);
    }

    /** @test */
    public function it_displays_day_off_change_request_index()
    {
        DayOffChangeRequest::factory()->count(2)->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('day_off_change_requests.index'))
            ->assertOk()
            ->assertViewIs('day_off_change_requests.index');
    }

    /** @test */
    public function it_displays_day_off_change_request_create_form()
    {
        $this->actingAs($this->user)
            ->get(route('day_off_change_requests.create'))
            ->assertOk()
            ->assertViewIs('day_off_change_requests.create');
    }

    /** @test */
    public function it_stores_a_day_off_change_request()
    {
        $this->actingAs($this->user);

        $data = [
            'reason_type'     => 'Schedule / Day Off Adjustment',
            'extension_date'  => now()->toDateString(),
            'time_start'      => '09:00',
            'time_end'        => '12:00',
            'number_of_hours' => 3,
            'old_date'        => now()->addDay()->toDateString(),
            'new_date'        => now()->addDays(2)->toDateString(),
            'reason'          => 'Personal errand',
        ];

        $response = $this->post(route('day_off_change_requests.store'), $data);

        $response->assertRedirect(route('day_off_change_requests.index'));

        $this->assertDatabaseHas('day_off_change_requests', [
            'employee_id' => $this->employee->id,
            'reason'      => 'Personal errand',
            'company_id'  => $this->company->id,
        ]);
    }

    /** @test */
    public function it_shows_a_day_off_change_request()
    {
        $request = DayOffChangeRequest::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('day_off_change_requests.show', $request))
            ->assertOk()
            ->assertViewIs('day_off_change_requests.show');
    }

    /** @test */
    public function it_displays_day_off_change_request_edit_form()
    {
        $this->employee->update(['approver_id' => $this->user->id]);

        $request = DayOffChangeRequest::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
            'status'      => 'pending',
        ]);

        $this->actingAs($this->user)
            ->get(route('day_off_change_requests.edit', $request))
            ->assertOk()
            ->assertViewIs('day_off_change_requests.edit');
    }

    /** @test */
    public function it_updates_a_day_off_change_request()
    {
        $this->employee->update(['approver_id' => $this->user->id]);

        $request = DayOffChangeRequest::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
            'status'      => 'pending',
        ]);

        $this->actingAs($this->user);

        $data = [
            'reason_type'     => 'Offset due to Extension of Work',
            'extension_date'  => now()->toDateString(),
            'time_start'      => '13:00',
            'time_end'        => '16:00',
            'number_of_hours' => 3,
            'old_date'        => now()->addDay()->toDateString(),
            'new_date'        => now()->addDays(2)->toDateString(),
            'reason'          => 'Updated reason',
        ];

        $response = $this->put(route('day_off_change_requests.update', $request), $data);

        $response->assertRedirect(route('day_off_change_requests.show', $request));

        $this->assertDatabaseHas('day_off_change_requests', [
            'id'           => $request->id,
            'number_of_hours' => 3,
            'reason'       => 'Updated reason',
        ]);
    }

    /** @test */
    public function it_deletes_a_day_off_change_request()
    {
        $request = DayOffChangeRequest::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
            'status'      => 'pending',
        ]);

        $this->actingAs($this->user);

        $response = $this->delete(route('day_off_change_requests.destroy', $request));

        $response->assertRedirect(route('day_off_change_requests.index'));
        $this->assertDatabaseMissing('day_off_change_requests', ['id' => $request->id]);
    }
}
