<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\OffsetRequest;
use App\Models\OvertimeRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OffsetRequestTest extends TestCase
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
            'offset_request.browse',
            'offset_request.browse_all',
            'offset_request.create',
            'offset_request.read',
            'offset_request.update',
            'offset_request.delete',
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
    public function it_displays_offset_request_index()
    {
        OffsetRequest::factory()->count(2)->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('offset_requests.index'))
            ->assertOk()
            ->assertViewIs('offset_requests.index');
    }

    /** @test */
    public function it_displays_offset_request_create_form()
    {
        $this->actingAs($this->user)
            ->get(route('offset_requests.create'))
            ->assertOk()
            ->assertViewIs('offset_requests.create');
    }

    /** @test */
    public function it_stores_an_offset_request()
    {
        Storage::fake('local');

        $this->actingAs($this->user);

        $overtime = OvertimeRequest::factory()->create([
            'employee_id'     => $this->employee->id,
            'company_id'      => $this->company->id,
            'date'            => now()->subDays(5)->toDateString(),
            'number_of_hours' => 2,
            'status'          => 'approved',
        ]);

        $data = [
            'employee_id'                  => $this->employee->id,
            'date'                         => now()->toDateString(),
            'project_or_event_description' => 'Project ABC',
            'time_start'                   => '09:00',
            'time_end'                     => '11:00',
            'number_of_hours'              => 2,
            'reason'                       => 'Make-up work',
            'overtime_requests'            => [
                ['id' => $overtime->id, 'used_hours' => 2],
            ],
            'files' => [UploadedFile::fake()->create('offset.pdf')],
        ];

        $response = $this->post(route('offset_requests.store'), $data);

        $response->assertRedirect(route('offset_requests.index'));

        $this->assertDatabaseHas('offset_requests', [
            'employee_id'                  => $this->employee->id,
            'date'                         => now()->toDateString(),
            'number_of_hours'              => 2,
            'project_or_event_description' => 'Project ABC',
        ]);
    }

    /** @test */
    public function it_shows_an_offset_request()
    {
        $offset = OffsetRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'company_id'  => $this->company->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('offset_requests.show', $offset))
            ->assertOk()
            ->assertViewIs('offset_requests.show');
    }

    /** @test */
    public function it_displays_offset_request_edit_form()
    {
        $this->employee->update(['approver_id' => $this->user->id]);

        $offset = OffsetRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'company_id'  => $this->company->id,
            'status'      => 'pending',
        ]);

        $this->actingAs($this->user)
            ->get(route('offset_requests.edit', $offset))
            ->assertOk()
            ->assertViewIs('offset_requests.edit');
    }

    /** @test */
    public function it_updates_an_offset_request()
    {
        $this->employee->update(['approver_id' => $this->user->id]);

        // Original overtime (not to be used in the update)
        $originalOvertime = OvertimeRequest::factory()->create([
            'employee_id'     => $this->employee->id,
            'company_id'      => $this->company->id,
            'date'            => now()->subDays(10)->toDateString(),
            'number_of_hours' => 2.5,
            'status'          => 'approved',
        ]);

        // Create OffsetRequest tied to original overtime (assume handled via controller logic or manually here)
        $offset = OffsetRequest::factory()->create([
            'employee_id'                  => $this->employee->id,
            'company_id'                   => $this->company->id,
            'status'                       => 'pending',
            'project_or_event_description' => 'Original Description',
            'number_of_hours'              => 2.5,
            'date'                         => now()->subDay()->toDateString(),
        ]);

        // New overtime to use in update
        $newOvertime = OvertimeRequest::factory()->create([
            'employee_id'     => $this->employee->id,
            'company_id'      => $this->company->id,
            'date'            => now()->subDays(5)->toDateString(),
            'number_of_hours' => 3,
            'status'          => 'approved',
        ]);

        $this->actingAs($this->user);

        $updatedData = [
            'employee_id'                  => $this->employee->id,
            'date'                         => now()->toDateString(),
            'project_or_event_description' => 'Updated Project',
            'time_start'                   => '08:00',
            'time_end'                     => '10:30',
            'number_of_hours'              => 2.5,
            'reason'                       => 'Updated reason',
            'overtime_requests'            => json_encode([
                ['id' => $newOvertime->id, 'used_hours' => 2.5]
            ]),
        ];

        $response = $this->put(route('offset_requests.update', $offset->id), $updatedData);

        $response->assertRedirect(route('offset_requests.index'));

        $this->assertDatabaseHas('offset_requests', [
            'id'                           => $offset->id,
            'project_or_event_description' => 'Updated Project',
            'number_of_hours'              => 2.5,
        ]);

        // Optional: verify pivot table update
        $this->assertDatabaseHas('offset_overtime', [
            'offset_request_id'   => $offset->id,
            'overtime_request_id' => $newOvertime->id,
            'used_hours'          => 2.5,
        ]);
    }

    /** @test */
    public function it_deletes_an_offset_request()
    {
        $this->employee->update(['approver_id' => $this->user->id]);

        $offset = OffsetRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'company_id'  => $this->company->id,
            'status'      => 'pending',
        ]);

        $this->actingAs($this->user);

        $response = $this->delete(route('offset_requests.destroy', $offset));

        $response->assertRedirect(route('offset_requests.index'));

        $this->assertDatabaseMissing('offset_requests', ['id' => $offset->id]);
    }
}
