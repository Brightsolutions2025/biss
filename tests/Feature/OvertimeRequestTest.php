<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OvertimeRequestTest extends TestCase
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
            'overtime_request.browse',
            'overtime_request.browse_all',
            'overtime_request.create',
            'overtime_request.read',
            'overtime_request.update',
            'overtime_request.delete',
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
    public function it_displays_overtime_request_index()
    {
        OvertimeRequest::factory()->count(2)->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('overtime_requests.index'))
            ->assertOk()
            ->assertViewIs('overtime_requests.index');
    }

    /** @test */
    public function it_displays_overtime_request_create_form()
    {
        $this->actingAs($this->user)
            ->get(route('overtime_requests.create'))
            ->assertOk()
            ->assertViewIs('overtime_requests.create');
    }

    /** @test */
    public function it_stores_an_overtime_request()
    {
        Storage::fake('local');

        $this->actingAs($this->user);

        $data = [
            'employee_id'     => $this->employee->id,
            'date'            => now()->toDateString(),
            'time_start'      => '18:00',
            'time_end'        => '20:00',
            'number_of_hours' => 2,
            'reason'          => 'Project deadline',
            'files'           => [UploadedFile::fake()->create('document.pdf', 100)],
        ];

        $response = $this->post(route('overtime_requests.store'), $data);

        $response->assertRedirect(route('overtime_requests.index'));
        $this->assertDatabaseHas('overtime_requests', [
            'employee_id' => $data['employee_id'],
            'reason'      => 'Project deadline',
            'company_id'  => $this->company->id,
        ]);
    }

    /** @test */
    public function it_shows_an_overtime_request()
    {
        $overtime = OvertimeRequest::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('overtime_requests.show', $overtime))
            ->assertOk()
            ->assertViewIs('overtime_requests.show');
    }

    /** @test */
    public function it_displays_overtime_request_edit_form()
    {
        // Set current user as approver of the employee
        $this->employee->update(['approver_id' => $this->user->id]);

        $overtime = OvertimeRequest::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
            'status'      => 'pending', // Make sure it's editable
        ]);

        $this->actingAs($this->user)
            ->get(route('overtime_requests.edit', $overtime))
            ->assertOk()
            ->assertViewIs('overtime_requests.edit');
    }

    /** @test */
    public function it_updates_an_overtime_request()
    {
        $this->employee->update(['approver_id' => $this->user->id]);

        $overtime = OvertimeRequest::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
            'status'      => 'pending',
        ]);

        $this->actingAs($this->user);

        $data = [
            'employee_id'     => $this->employee->id,
            'date'            => now()->toDateString(),
            'time_start'      => '18:00',
            'time_end'        => '21:00',
            'number_of_hours' => 3,
            'reason'          => 'Revised task',
            'files'           => [UploadedFile::fake()->create('update.pdf', 100)],
        ];

        $response = $this->put(route('overtime_requests.update', $overtime), $data);

        $response->assertRedirect(route('overtime_requests.index'));

        $this->assertDatabaseHas('overtime_requests', [
            'id'              => $overtime->id,
            'number_of_hours' => 3,
            'reason'          => 'Revised task',
        ]);
    }

    /** @test */
    public function it_deletes_an_overtime_request()
    {
        $overtime = OvertimeRequest::factory()->create([
            'company_id'  => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->delete(route('overtime_requests.destroy', $overtime));

        $response->assertRedirect(route('overtime_requests.index'));
        $this->assertDatabaseMissing('overtime_requests', ['id' => $overtime->id]);
    }
}
