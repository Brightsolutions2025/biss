<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TimeRecord;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TimeRecordTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Employee $employee;
    protected PayrollPeriod $payrollPeriod;

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
            'time_record.browse',
            'time_record.browse_all',
            'time_record.create',
            'time_record.read',
            'time_record.update',
            'time_record.delete',
        ])->map(fn ($name) => Permission::create([
            'name'       => $name,
            'company_id' => $this->company->id,
        ]));

        $role->permissions()->attach($permissions->pluck('id'), ['company_id' => $this->company->id]);

        $this->employee = Employee::factory()->create([
            'user_id'    => $this->user->id,
            'company_id' => $this->company->id,
        ]);

        $this->payrollPeriod = PayrollPeriod::factory()->create([
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function it_displays_time_record_index()
    {
        TimeRecord::factory()->create([
            'employee_id'       => $this->employee->id,
            'company_id'        => $this->company->id,
            'payroll_period_id' => $this->payrollPeriod->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('time_records.index'))
            ->assertOk()
            ->assertViewIs('time_records.index');
    }

    /** @test */
    public function it_displays_time_record_create_form()
    {
        $this->actingAs($this->user)
            ->get(route('time_records.create'))
            ->assertOk()
            ->assertViewIs('time_records.create');
    }

    /** @test */
    public function it_stores_a_time_record()
    {
        Storage::fake();

        $this->actingAs($this->user);

        $data = [
            'employee_id'       => $this->employee->id,
            'payroll_period_id' => $this->payrollPeriod->id,
            'time_record_lines' => [
                [
                    'date'              => now()->toDateString(),
                    'clock_in'          => '08:00',
                    'clock_out'         => '17:00',
                    'late_minutes'      => 0,
                    'undertime_minutes' => 0,
                    'remarks'           => 'On time',
                ],
            ],
            'files' => [UploadedFile::fake()->create('attachment.pdf')],
        ];

        $response = $this->post(route('time_records.store'), $data);

        $response->assertRedirect(route('time_records.index'));

        $this->assertDatabaseHas('time_records', [
            'employee_id' => $this->employee->id,
            'company_id'  => $this->company->id,
        ]);
    }

    /** @test */
    public function it_shows_a_time_record()
    {
        $timeRecord = TimeRecord::factory()->create([
            'employee_id'       => $this->employee->id,
            'company_id'        => $this->company->id,
            'payroll_period_id' => $this->payrollPeriod->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('time_records.show', $timeRecord))
            ->assertOk()
            ->assertViewIs('time_records.show');
    }

    /** @test */
    public function it_displays_time_record_edit_form()
    {
        $timeRecord = TimeRecord::factory()->create([
            'employee_id'       => $this->employee->id,
            'company_id'        => $this->company->id,
            'payroll_period_id' => $this->payrollPeriod->id,
            'status'            => 'draft',
        ]);

        $this->actingAs($this->user)
            ->get(route('time_records.edit', $timeRecord))
            ->assertOk()
            ->assertViewIs('time_records.edit');
    }

    /** @test */
    public function it_updates_a_time_record()
    {
        $timeRecord = TimeRecord::factory()->create([
            'employee_id'       => $this->employee->id,
            'company_id'        => $this->company->id,
            'payroll_period_id' => $this->payrollPeriod->id,
            'status'            => 'draft',
        ]);

        $timeRecordLine = \App\Models\TimeRecordLine::factory()->create([
            'time_record_id'    => $timeRecord->id,
            'company_id'        => $this->company->id, // <-- Add this line
            'date'              => now()->toDateString(),
            'clock_in'          => '08:00',
            'clock_out'         => '17:00',
            'late_minutes'      => 0,
            'undertime_minutes' => 0,
            'remarks'           => 'Initial',
        ]);

        $updatedData = [
            'employee_id'       => $this->employee->id,
            'payroll_period_id' => $this->payrollPeriod->id,
            'time_record_lines' => [
                [
                    'id'                => $timeRecordLine->id,
                    'company_id'        => $this->company->id, // Add this
                    'date'              => now()->toDateString(),
                    'clock_in'          => '09:00',
                    'clock_out'         => '18:00',
                    'late_minutes'      => 15,
                    'undertime_minutes' => 0,
                    'remarks'           => 'Late clock-in',
                ],
            ],
        ];

        $this->actingAs($this->user)
            ->put(route('time_records.update', $timeRecord), $updatedData)
            ->assertRedirect(route('time_records.index'));

        $this->assertDatabaseHas('time_records', [
            'id'          => $timeRecord->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->assertDatabaseHas('time_record_lines', [
            'id'             => $timeRecordLine->id,
            'time_record_id' => $timeRecord->id,
            'remarks'        => 'Late clock-in',
        ]);
    }

    /** @test */
    public function it_deletes_a_time_record()
    {
        $timeRecord = TimeRecord::factory()->create([
            'employee_id'       => $this->employee->id,
            'company_id'        => $this->company->id,
            'payroll_period_id' => $this->payrollPeriod->id,
            'status'            => 'draft',
        ]);

        $this->actingAs($this->user)
            ->delete(route('time_records.destroy', $timeRecord))
            ->assertRedirect(route('time_records.index'));

        $this->assertDatabaseMissing('time_records', [
            'id' => $timeRecord->id,
        ]);
    }
}
