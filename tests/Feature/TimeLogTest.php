<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PayrollPeriod;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TimeLog;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
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
            'time_log.browse',
            'time_log.create',
            'time_log.read',
            'time_log.update',
            'time_log.delete',
        ])->map(fn ($name) => Permission::create([
            'name'       => $name,
            'company_id' => $this->company->id,
        ]));

        $role->permissions()->attach($permissions->pluck('id'), ['company_id' => $this->company->id]);

        $this->payrollPeriod = PayrollPeriod::factory()->create(['company_id' => $this->company->id]);
    }

    /** @test */
    public function it_displays_time_log_index()
    {
        TimeLog::factory()->count(3)->create([
            'company_id'        => $this->company->id,
            'payroll_period_id' => $this->payrollPeriod->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('time_logs.index'))
            ->assertOk()
            ->assertViewIs('time_logs.index');
    }

    /** @test */
    public function it_displays_time_log_create_form()
    {
        $this->actingAs($this->user)
            ->get(route('time_logs.create'))
            ->assertOk()
            ->assertViewIs('time_logs.create');
    }

    /** @test */
    public function it_stores_a_time_log()
    {
        $this->actingAs($this->user);

        $data = [
            'payroll_period_id'  => $this->payrollPeriod->id,
            'employee_name'      => 'John Doe',
            'department_name'    => 'IT',
            'employee_id'        => 'E123',
            'employee_type'      => 'Regular',
            'attendance_group'   => 'Group A',
            'date'               => '2025-07-19',
            'weekday'            => 'Friday',
            'shift'              => 'Morning',
            'attendance_time'    => '2025-07-19T08:00',
            'about_the_record'   => 'Check-in',
            'attendance_result'  => 'On time',
            'attendance_address' => 'Office',
            'note'               => 'N/A',
            'attendance_method'  => 'Biometric',
            'attendance_photo'   => 'photo.jpg',
        ];

        $response = $this->post(route('time_logs.store'), $data);

        $response->assertRedirect(route('time_logs.index'));
        $this->assertDatabaseHas('time_logs', [
            'employee_name' => 'John Doe',
            'company_id'    => $this->company->id,
        ]);
    }

    /** @test */
    public function it_shows_a_time_log()
    {
        $timeLog = TimeLog::factory()->create([
            'company_id'        => $this->company->id,
            'payroll_period_id' => $this->payrollPeriod->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('time_logs.show', $timeLog))
            ->assertOk()
            ->assertViewIs('time_logs.show')
            ->assertSee($timeLog->employee_name);
    }

    /** @test */
    public function it_displays_time_log_edit_form()
    {
        $timeLog = TimeLog::factory()->create([
            'company_id'        => $this->company->id,
            'payroll_period_id' => $this->payrollPeriod->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('time_logs.edit', $timeLog))
            ->assertOk()
            ->assertViewIs('time_logs.edit');
    }

    /** @test */
    public function it_updates_a_time_log()
    {
        $timeLog = TimeLog::factory()->create([
            'company_id'        => $this->company->id,
            'payroll_period_id' => $this->payrollPeriod->id,
        ]);

        $this->actingAs($this->user);

        $data = [
            'payroll_period_id'  => $this->payrollPeriod->id,
            'employee_name'      => 'Jane Smith',
            'department_name'    => 'HR',
            'employee_id'        => 'E456',
            'employee_type'      => 'Probationary',
            'attendance_group'   => 'Group B',
            'date'               => '2025-07-20',
            'weekday'            => 'Saturday',
            'shift'              => 'Night',
            'attendance_time'    => '2025-07-20T22:00',
            'about_the_record'   => 'Check-in',
            'attendance_result'  => 'Late',
            'attendance_address' => 'Remote',
            'note'               => 'Traffic',
            'attendance_method'  => 'Mobile App',
            'attendance_photo'   => 'photo2.jpg',
        ];

        $response = $this->put(route('time_logs.update', $timeLog), $data);

        $response->assertRedirect(route('time_logs.index'));
        $this->assertDatabaseHas('time_logs', [
            'id'            => $timeLog->id,
            'employee_name' => 'Jane Smith',
        ]);
    }

    /** @test */
    public function it_deletes_a_time_log()
    {
        $timeLog = TimeLog::factory()->create([
            'company_id'        => $this->company->id,
            'payroll_period_id' => $this->payrollPeriod->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->delete(route('time_logs.destroy', $timeLog));

        $response->assertRedirect(route('time_logs.index'));
        $this->assertDatabaseMissing('time_logs', ['id' => $timeLog->id]);
    }

    /** @test */
    public function it_uploads_time_logs_from_csv()
    {
        $this->actingAs($this->user);

        // Prepare a fake CSV file
        $csv = <<<CSV
    payroll_period_id,employee_name,department_name,employee_id,employee_type,attendance_group,date,weekday,shift,attendance_time,about_the_record,attendance_result,attendance_address,note,attendance_method,attendance_photo
    {$this->payrollPeriod->id},John Doe,IT,E123,Regular,Group A,2025-07-19,Friday,Morning,2025-07-19T08:00,Check-in,On time,Office,N/A,Biometric,photo.jpg
    CSV;

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('timelogs.csv', $csv);

        $response = $this->post(route('time_logs.import'), [
            'payroll_period_id' => $this->payrollPeriod->id,
            'csv_file'          => $file,
        ]);

        $response->assertRedirect(); // adjust to actual redirect if needed
        $this->assertDatabaseHas('time_logs', [
            'employee_name' => 'John Doe',
            'employee_id'   => 'E123',
            'company_id'    => $this->company->id,
        ]);
    }
}
