<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\OvertimePreApproval;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OvertimePreApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Employee $employee;

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

        $role = Role::factory()->create([
            'name' => 'admin',
            'company_id' => $this->company->id,
        ]);

        $this->user->roles()->attach($role->id, [
            'company_id' => $this->company->id,
        ]);

        $permissions = collect([
            'overtime_pre_approval.browse',
            'overtime_pre_approval.browse_all',
            'overtime_pre_approval.create',
            'overtime_pre_approval.read',
            'overtime_pre_approval.update',
            'overtime_pre_approval.delete',
        ])->map(fn ($name) => Permission::create([
            'name' => $name,
            'company_id' => $this->company->id,
        ]));

        $role->permissions()->attach(
            $permissions->pluck('id')->mapWithKeys(fn ($id) => [
                $id => ['company_id' => $this->company->id],
            ])->toArray()
        );

        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'approver_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_displays_overtime_pre_approval_index()
    {
        OvertimePreApproval::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('overtime_pre_approvals.index'))
            ->assertOk()
            ->assertViewIs('overtime_pre_approvals.index');
    }

    /** @test */
    public function it_displays_overtime_pre_approval_create_form()
    {
        $this->actingAs($this->user)
            ->get(route('overtime_pre_approvals.create'))
            ->assertOk()
            ->assertViewIs('overtime_pre_approvals.create');
    }

    /** @test */
    public function it_stores_an_overtime_pre_approval()
    {
        $this->actingAs($this->user);

        $data = [
            'employee_id' => $this->employee->id,
            'date' => now()->addDay()->toDateString(),
            'planned_time_start' => '18:00',
            'planned_time_end' => '20:00',
            'estimated_number_of_hours' => 2,
            'reason' => 'Urgent client deliverable',
            'planned_tasks' => 'Finish report and submit to client',
        ];

        $response = $this->post(route('overtime_pre_approvals.store'), $data);

        $response->assertRedirect(route('overtime_pre_approvals.index'));

        $this->assertDatabaseHas('overtime_pre_approvals', [
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => $data['date'],
            'planned_time_start' => '18:00',
            'planned_time_end' => '20:00',
            'estimated_number_of_hours' => 2,
            'reason' => 'Urgent client deliverable',
            'planned_tasks' => 'Finish report and submit to client',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_shows_an_overtime_pre_approval()
    {
        $overtimePreApproval = OvertimePreApproval::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->user)
            ->get(route('overtime_pre_approvals.show', $overtimePreApproval))
            ->assertOk()
            ->assertViewIs('overtime_pre_approvals.show');
    }

    /** @test */
    public function it_displays_overtime_pre_approval_edit_form()
    {
        $overtimePreApproval = OvertimePreApproval::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->user)
            ->get(route('overtime_pre_approvals.edit', $overtimePreApproval))
            ->assertOk()
            ->assertViewIs('overtime_pre_approvals.edit');
    }

    /** @test */
    public function it_updates_an_overtime_pre_approval()
    {
        $overtimePreApproval = OvertimePreApproval::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'status' => 'pending',
            'date' => now()->addDay()->toDateString(),
            'planned_time_start' => '18:00',
            'planned_time_end' => '20:00',
            'estimated_number_of_hours' => 2,
            'reason' => 'Original reason',
        ]);

        $this->actingAs($this->user);

        $data = [
            'employee_id' => $this->employee->id,
            'date' => now()->addDays(2)->toDateString(),
            'planned_time_start' => '19:00',
            'planned_time_end' => '22:00',
            'estimated_number_of_hours' => 3,
            'reason' => 'Updated urgent task',
            'planned_tasks' => 'Updated deliverables',
        ];

        $response = $this->put(route('overtime_pre_approvals.update', $overtimePreApproval), $data);

        $response->assertRedirect(route('overtime_pre_approvals.index'));

        $this->assertDatabaseHas('overtime_pre_approvals', [
            'id' => $overtimePreApproval->id,
            'date' => $data['date'],
            'planned_time_start' => '19:00',
            'planned_time_end' => '22:00',
            'estimated_number_of_hours' => 3,
            'reason' => 'Updated urgent task',
            'planned_tasks' => 'Updated deliverables',
        ]);
    }

    /** @test */
    public function it_deletes_a_pending_overtime_pre_approval()
    {
        $overtimePreApproval = OvertimePreApproval::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->user);

        $response = $this->delete(route('overtime_pre_approvals.destroy', $overtimePreApproval));

        $response->assertRedirect(route('overtime_pre_approvals.index'));

        $this->assertDatabaseMissing('overtime_pre_approvals', [
            'id' => $overtimePreApproval->id,
        ]);
    }

    /** @test */
    public function it_approves_an_overtime_pre_approval()
    {
        $overtimePreApproval = OvertimePreApproval::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->user);

        $response = $this->patch(route('overtime_pre_approvals.approve', $overtimePreApproval));

        $response->assertRedirect(route('overtime_pre_approvals.show', $overtimePreApproval->id));

        $this->assertDatabaseHas('overtime_pre_approvals', [
            'id' => $overtimePreApproval->id,
            'status' => 'approved',
            'approver_id' => $this->user->id,
            'rejection_reason' => null,
        ]);

        $this->assertNotNull($overtimePreApproval->fresh()->approval_date);
    }

    /** @test */
    public function it_rejects_an_overtime_pre_approval_with_reason()
    {
        $overtimePreApproval = OvertimePreApproval::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->user);

        $response = $this->patch(route('overtime_pre_approvals.reject', $overtimePreApproval), [
            'reason' => 'Insufficient justification',
        ]);

        $response->assertRedirect(route('overtime_pre_approvals.show', $overtimePreApproval->id));

        $this->assertDatabaseHas('overtime_pre_approvals', [
            'id' => $overtimePreApproval->id,
            'status' => 'rejected',
            'approver_id' => $this->user->id,
            'approval_date' => null,
            'rejection_reason' => 'Insufficient justification',
        ]);
    }

    /** @test */
    public function it_requires_rejection_reason()
    {
        $overtimePreApproval = OvertimePreApproval::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->user);

        $response = $this->from(route('overtime_pre_approvals.show', $overtimePreApproval))
            ->patch(route('overtime_pre_approvals.reject', $overtimePreApproval), [
                'reason' => '',
            ]);

        $response->assertRedirect(route('overtime_pre_approvals.show', $overtimePreApproval));
        $response->assertSessionHasErrors(['reason']);
    }

    /** @test */
    public function it_displays_validation_errors_on_invalid_store_input()
    {
        $this->actingAs($this->user);

        $response = $this->from(route('overtime_pre_approvals.create'))
            ->post(route('overtime_pre_approvals.store'), [
                'employee_id' => '',
                'date' => '',
                'planned_time_start' => '',
                'planned_time_end' => '',
                'estimated_number_of_hours' => '',
                'reason' => '',
            ]);

        $response->assertRedirect(route('overtime_pre_approvals.create'));
        $response->assertSessionHasErrors([
            'employee_id',
            'date',
            'planned_time_start',
            'planned_time_end',
            'estimated_number_of_hours',
            'reason',
        ]);
    }

    /** @test */
    public function it_rejects_estimated_hours_that_exceed_planned_duration()
    {
        $this->actingAs($this->user);

        $response = $this->from(route('overtime_pre_approvals.create'))
            ->post(route('overtime_pre_approvals.store'), [
                'employee_id' => $this->employee->id,
                'date' => now()->addDay()->toDateString(),
                'planned_time_start' => '18:00',
                'planned_time_end' => '20:00',
                'estimated_number_of_hours' => 3,
                'reason' => 'Too many hours',
                'planned_tasks' => 'Testing validation',
            ]);

        $response->assertRedirect(route('overtime_pre_approvals.create'));
        $response->assertSessionHasErrors(['estimated_number_of_hours']);

        $this->assertDatabaseMissing('overtime_pre_approvals', [
            'reason' => 'Too many hours',
        ]);
    }

    /** @test */
    public function it_rejects_overlapping_overtime_pre_approval()
    {
        OvertimePreApproval::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => now()->addDay()->toDateString(),
            'planned_time_start' => '18:00',
            'planned_time_end' => '21:00',
            'estimated_number_of_hours' => 3,
            'status' => 'pending',
        ]);

        $this->actingAs($this->user);

        $response = $this->from(route('overtime_pre_approvals.create'))
            ->post(route('overtime_pre_approvals.store'), [
                'employee_id' => $this->employee->id,
                'date' => now()->addDay()->toDateString(),
                'planned_time_start' => '20:00',
                'planned_time_end' => '22:00',
                'estimated_number_of_hours' => 2,
                'reason' => 'Overlapping overtime',
                'planned_tasks' => 'Testing overlap validation',
            ]);

        $response->assertRedirect(route('overtime_pre_approvals.create'));
        $response->assertSessionHasErrors(['planned_time_start']);

        $this->assertDatabaseMissing('overtime_pre_approvals', [
            'reason' => 'Overlapping overtime',
        ]);
    }
}