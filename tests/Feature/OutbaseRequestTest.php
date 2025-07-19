<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\OutbaseRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OutbaseRequestTest extends TestCase
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
            'outbase_request.browse',
            'outbase_request.browse_all',
            'outbase_request.create',
            'outbase_request.read',
            'outbase_request.update',
            'outbase_request.delete',
        ])->map(fn ($name) => Permission::create([
            'name' => $name,
            'company_id' => $this->company->id,
        ]));

        $role->permissions()->attach($permissions->pluck('id'), ['company_id' => $this->company->id]);

        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'approver_id' => $this->user->id, // Self-approver for testing
        ]);
    }

    /** @test */
    public function it_displays_outbase_request_index()
    {
        OutbaseRequest::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('outbase_requests.index'))
            ->assertOk()
            ->assertViewIs('outbase_requests.index');
    }

    /** @test */
    public function it_displays_outbase_request_create_form()
    {
        $this->actingAs($this->user)
            ->get(route('outbase_requests.create'))
            ->assertOk()
            ->assertViewIs('outbase_requests.create');
    }

    /** @test */
    public function it_stores_an_outbase_request()
    {
        Storage::fake('local');

        $this->actingAs($this->user);

        $data = [
            'employee_id' => $this->employee->id,
            'date' => now()->toDateString(),
            'time_start' => '08:00',
            'time_end' => '17:00',
            'location' => 'Client Site',
            'reason' => 'Client visit',
            'files' => [UploadedFile::fake()->create('doc.pdf')],
        ];

        $response = $this->post(route('outbase_requests.store'), $data);

        $response->assertRedirect(route('outbase_requests.index'));
        $this->assertDatabaseHas('outbase_requests', [
            'employee_id' => $this->employee->id,
            'reason' => 'Client visit',
        ]);
    }

    /** @test */
    public function it_shows_an_outbase_request()
    {
        $outbaseRequest = OutbaseRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('outbase_requests.show', $outbaseRequest))
            ->assertOk()
            ->assertViewIs('outbase_requests.show');
    }

    /** @test */
    public function it_displays_outbase_request_edit_form()
    {
        $outbaseRequest = OutbaseRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->user)
            ->get(route('outbase_requests.edit', $outbaseRequest))
            ->assertOk()
            ->assertViewIs('outbase_requests.edit');
    }

    /** @test */
    public function it_updates_an_outbase_request()
    {
        Storage::fake('local');

        $outbaseRequest = OutbaseRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->user);

        $data = [
            'employee_id' => $this->employee->id,
            'date' => now()->toDateString(),
            'time_start' => '09:00',
            'time_end' => '18:00',
            'location' => 'Updated Location',
            'reason' => 'Updated Reason',
            'files' => [UploadedFile::fake()->create('newfile.pdf')],
        ];

        $response = $this->put(route('outbase_requests.update', $outbaseRequest), $data);

        $response->assertRedirect(route('outbase_requests.index'));
        $this->assertDatabaseHas('outbase_requests', [
            'id' => $outbaseRequest->id,
            'location' => 'Updated Location',
        ]);
    }

    /** @test */
    public function it_deletes_an_outbase_request()
    {
        $outbaseRequest = OutbaseRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->user);

        $response = $this->delete(route('outbase_requests.destroy', $outbaseRequest));

        $response->assertRedirect(route('outbase_requests.index'));
        $this->assertDatabaseMissing('outbase_requests', ['id' => $outbaseRequest->id]);
    }
}
