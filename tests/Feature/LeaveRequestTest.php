<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
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
        $this->user    = User::factory()->create();
        $this->user->companies()->attach($this->company->id);

        UserPreference::factory()->create([
            'user_id'    => $this->user->id,
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
    public function it_displays_leave_request_index()
    {
        LeaveRequest::factory()->count(2)->create([
            'company_id'  => $this->company->id,
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
    public function it_stores_a_leave_request()
    {
        Storage::fake('local');

        $this->actingAs($this->user);

        $data = [
            'start_date'     => now()->toDateString(),
            'end_date'       => now()->toDateString(),
            'number_of_days' => 1,
            'reason'         => 'Personal reason',
            'files'          => [UploadedFile::fake()->create('file.pdf')],
        ];

        $response = $this->post(route('leave_requests.store'), $data);

        $response->assertRedirect(route('leave_requests.index'));
        $this->assertDatabaseHas('leave_requests', [
            'reason'      => 'Personal reason',
            'employee_id' => $this->employee->id,
            'company_id'  => $this->company->id,
        ]);
    }

    /** @test */
    public function it_shows_a_leave_request()
    {
        $leaveRequest = LeaveRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'company_id'  => $this->company->id,
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
            'company_id'  => $this->company->id,
            'status'      => 'pending',
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
            'company_id'  => $this->company->id,
            'status'      => 'pending',
        ]);

        $this->actingAs($this->user);

        $data = [
            'start_date'     => now()->toDateString(),
            'end_date'       => now()->toDateString(),
            'number_of_days' => 1,
            'reason'         => 'Updated reason',
            'files'          => [UploadedFile::fake()->create('update.pdf')],
        ];

        $response = $this->put(route('leave_requests.update', $leaveRequest), $data);

        $response->assertRedirect(route('leave_requests.index'));

        $this->assertDatabaseHas('leave_requests', [
            'id'     => $leaveRequest->id,
            'reason' => 'Updated reason',
        ]);
    }

    /** @test */
    public function it_deletes_a_leave_request()
    {
        $leaveRequest = LeaveRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'company_id'  => $this->company->id,
            'status'      => 'pending',
        ]);

        $this->actingAs($this->user);

        $response = $this->delete(route('leave_requests.destroy', $leaveRequest));

        $response->assertRedirect(route('leave_requests.index'));
        $this->assertDatabaseMissing('leave_requests', ['id' => $leaveRequest->id]);
    }
}
