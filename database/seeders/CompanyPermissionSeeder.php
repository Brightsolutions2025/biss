<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CompanyPermissionSeeder extends Seeder
{
    protected array $resources = [
        'department',
        'team',
        'employee',
        'shift',
        'employee_shift',
        'payroll_period',
        'time_log',
        'overtime_request',
        'leave_balance',
        'leave_request',
        'outbase_request',
        'offset_request',
        'offset_overtime',
        'time_record',
        'time_record_line',
    ];

    protected array $actions = ['browse', 'create', 'read', 'update', 'delete'];

    /**
     * Seed permissions for a specific company.
     *
     * @param  int  $companyId
     */
    public function runForCompany(int $companyId): void
    {
        foreach ($this->resources as $resource) {
            foreach ($this->actions as $action) {
                Permission::firstOrCreate([
                    'name'       => "{$resource}.{$action}.{$companyId}",
                    'guard_name' => 'web',
                ]);
            }
        }
    }
}
