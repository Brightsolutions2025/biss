<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\LeaveCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
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

        $role = Role::factory()->create(['name' => 'admin']);
        $this->user->roles()->attach($role->id, ['company_id' => $this->company->id]);

        $permissions = collect([
            'leave_request.browse',
            'leave_request.browse_all',
            'leave_request.create',
            'leave_request.read',
            'leave_request.update',
            'leave_request.delete',
        ])->map(fn ($name) => Permission::create([
            'name' => $name,
            'company_id' => $this->company->id,
        ]));

        $role->permissions()->attach($permissions->pluck('id'), ['company_id' => $this->company->id]);

        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'employment_type' => 'Regular',
        ]);

        LeaveBalance::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'year' => now()->year,
            'leave_type' => LeaveCreditService::VACATION,
            'beginning_balance' => 12,
        ]);

        LeaveBalance::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'year' => now()->year,
            'leave_type' => LeaveCreditService::EMERGENCY,
            'beginning_balance' => 3,
        ]);
    }

    /** @test */
    public function it_displays_leave_request_index()
    {
        LeaveRequest::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('leave_requests.index'))
            ->assertOk()
            ->assertViewIs('leave_requests.index');
    }

    /** @test */
    public function it_displays_leave_request_create_form()
    {
        $this->actingAs($this->user)
            ->get(route('leave_requests.create'))
            ->assertOk()
            ->assertViewIs('leave_requests.create');
    }

    /** @test */
    public function it_stores_a_vacation_leave_request()
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        $data = [
            'leave_type' => LeaveCreditService::VACATION,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'number_of_days' => 1,
            'reason' => 'Personal reason',
            'leave_with_pay' => true,
            'files' => [UploadedFile::fake()->create('file.pdf')],
        ];

        $response = $this->post(route('leave_requests.store'), $data);

        $response->assertRedirect(route('leave_requests.index'));
        $this->assertDatabaseHas('leave_requests', [
            'reason' => 'Personal reason',
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'leave_type' => LeaveCreditService::VACATION,
            'leave_with_pay' => true,
        ]);
    }

    /** @test */
    public function emergency_leave_requires_a_supporting_document()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('leave_requests.store'), [
            'leave_type' => LeaveCreditService::EMERGENCY,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'number_of_days' => 1,
            'reason' => 'Emergency reason',
            'leave_with_pay' => true,
        ]);

        $response->assertSessionHasErrors('files');
        $this->assertDatabaseMissing('leave_requests', [
            'employee_id' => $this->employee->id,
            'leave_type' => LeaveCreditService::EMERGENCY,
            'reason' => 'Emergency reason',
        ]);
    }

    /** @test */
    public function emergency_leave_can_be_submitted_with_a_supporting_document()
    {
        Storage::fake('local');
        $this->actingAs($this->user);

        $response = $this->post(route('leave_requests.store'), [
            'leave_type' => LeaveCreditService::EMERGENCY,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'number_of_days' => 1,
            'reason' => 'Emergency reason',
            'leave_with_pay' => true,
            'files' => [UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf')],
        ]);

        $response->assertRedirect(route('leave_requests.index'));
        $leave = LeaveRequest::where('employee_id', $this->employee->id)
            ->where('leave_type', LeaveCreditService::EMERGENCY)
            ->firstOrFail();

        $this->assertSame(1, $leave->files()->count());
    }

    /** @test */
    public function vl_and_el_use_separate_credit_buckets()
    {
        LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type' => LeaveCreditService::VACATION,
            'status' => 'approved',
            'leave_with_pay' => true,
            'start_date' => now()->startOfYear()->addDays(10)->toDateString(),
            'end_date' => now()->startOfYear()->addDays(11)->toDateString(),
            'number_of_days' => 2,
        ]);

        $credits = app(LeaveCreditService::class);
        $vl = $credits->summary($this->employee, now()->year, LeaveCreditService::VACATION);
        $el = $credits->summary($this->employee, now()->year, LeaveCreditService::EMERGENCY);

        $this->assertSame(10.0, $vl['remaining']);
        $this->assertSame(3.0, $el['remaining']);
    }

    /** @test */
    public function it_shows_a_leave_request()
    {
        $leaveRequest = LeaveRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'leave_type' => LeaveCreditService::VACATION,
            'leave_with_pay' => true,
        ]);

        $this->actingAs($this->user)
            ->get(route('leave_requests.show', $leaveRequest))
            ->assertOk()
            ->assertViewIs('leave_requests.show');
    }

    /** @test */
    public function it_displays_leave_request_edit_form()
    {
        $this->employee->update(['approver_id' => $this->user->id]);

        $leaveRequest = LeaveRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'leave_type' => LeaveCreditService::VACATION,
            'status' => 'pending',
            'leave_with_pay' => true,
        ]);

        $this->actingAs($this->user)
            ->get(route('leave_requests.edit', $leaveRequest))
            ->assertOk()
            ->assertViewIs('leave_requests.edit');
    }

    /** @test */
    public function it_updates_a_leave_request()
    {
        $this->employee->update(['approver_id' => $this->user->id]);

        $leaveRequest = LeaveRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'leave_type' => LeaveCreditService::VACATION,
            'status' => 'pending',
            'leave_with_pay' => true,
        ]);

        $this->actingAs($this->user);

        $data = [
            'leave_type' => LeaveCreditService::VACATION,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'number_of_days' => 1,
            'reason' => 'Updated reason',
            'leave_with_pay' => false,
            'files' => [UploadedFile::fake()->create('update.pdf')],
        ];

        $response = $this->put(route('leave_requests.update', $leaveRequest), $data);

        $response->assertRedirect(route('leave_requests.index'));
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'reason' => 'Updated reason',
            'leave_type' => LeaveCreditService::VACATION,
            'leave_with_pay' => false,
        ]);
    }

    /** @test */
    public function it_deletes_a_leave_request()
    {
        $leaveRequest = LeaveRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'leave_type' => LeaveCreditService::VACATION,
            'status' => 'pending',
            'leave_with_pay' => true,
        ]);

        $this->actingAs($this->user);

        $response = $this->delete(route('leave_requests.destroy', $leaveRequest));

        $response->assertRedirect(route('leave_requests.index'));
        $this->assertDatabaseMissing('leave_requests', ['id' => $leaveRequest->id]);
    }
}
