<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MarkoAdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /*
             * BISS Company
             *
             * Your current BISS data uses company_id = 1.
             * Change this only if you want the account assigned
             * to another company.
             */
            $company = Company::findOrFail(1);

            /*
             * -------------------------------------------------
             * 1. CREATE / UPDATE USER ACCOUNT
             * -------------------------------------------------
             */
            $user = User::updateOrCreate(
                [
                    'email' => 'mfiedacan@bsm.ph',
                ],
                [
                    'name' => 'Marko Fiedacan',

                    'password' => Hash::make('@bsm2026'),

                    'email_verified_at' => now(),
                ]
            );

            /*
             * -------------------------------------------------
             * 2. ATTACH USER TO COMPANY
             * -------------------------------------------------
             */
            DB::table('company_user')->updateOrInsert(
                [
                    'company_id' => $company->id,
                    'user_id'    => $user->id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            /*
             * -------------------------------------------------
             * 3. FIND OR CREATE ADMIN ROLE
             * -------------------------------------------------
             */
            $adminRole = Role::where(
                'company_id',
                $company->id
            )
                ->whereRaw(
                    'LOWER(name) = ?',
                    ['admin']
                )
                ->first();

            if (!$adminRole) {
                $adminRole = Role::create([
                    'company_id'  => $company->id,
                    'name'        => 'Admin',
                    'description' => 'Administrator role',
                ]);
            }

            /*
             * -------------------------------------------------
             * 4. ASSIGN ADMIN ROLE TO USER
             * -------------------------------------------------
             */
            DB::table('role_user')->updateOrInsert(
                [
                    'company_id' => $company->id,
                    'role_id'    => $adminRole->id,
                    'user_id'    => $user->id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            /*
             * -------------------------------------------------
             * 5. GIVE ADMIN ALL EXISTING COMPANY PERMISSIONS
             * -------------------------------------------------
             *
             * This is important because BISS uses:
             *
             * auth()->user()->hasPermission(...)
             *
             * for many menus and pages.
             */
            $permissions = Permission::where(
                'company_id',
                $company->id
            )->get();

            foreach ($permissions as $permission) {
                DB::table('permission_role')->updateOrInsert(
                    [
                        'company_id'    => $company->id,
                        'permission_id' => $permission->id,
                        'role_id'       => $adminRole->id,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            /*
             * -------------------------------------------------
             * 6. SET ACTIVE COMPANY
             * -------------------------------------------------
             */
            UserPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                ],
                [
                    'company_id'  => $company->id,
                    'preferences' => [],
                ]
            );

            /*
             * -------------------------------------------------
             * 7. CREATE / UPDATE EMPLOYEE RECORD
             * -------------------------------------------------
             *
             * Dummy information is used for fields that are
             * not important for authentication.
             *
             * employment_type is set to Regular so VL/EL
             * functionality can be used with this account.
             */
            Employee::updateOrCreate(
                [
                    'user_id'    => $user->id,
                    'company_id' => $company->id,
                ],
                [
                    'employee_number' =>
                        'ADMIN-' . str_pad(
                            (string) $user->id,
                            5,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'approver_id' => null,

                    /*
                     * Personal Information
                     */
                    'first_name'  => 'Marko',
                    'middle_name' => null,
                    'last_name'   => 'Fiedacan',

                    'gender'       => 'Male',
                    'birth_date'   => '1995-01-01',
                    'civil_status' => 'Single',
                    'nationality'  => 'Filipino',

                    /*
                     * Employment Information
                     */
                    'position' => 'System Administrator',

                    'department_id' => null,
                    'team_id'       => null,

                    'employment_type' => 'Regular',

                    'flexible_time' => true,

                    'ot_not_convertible_to_offset' => false,

                    'hire_date'        => '2026-01-01',
                    'termination_date' => null,

                    'basic_salary' => 0,

                    /*
                     * Government IDs
                     */
                    'sss_number'        => null,
                    'philhealth_number' => null,
                    'pagibig_number'    => null,
                    'tin_number'        => null,

                    /*
                     * Contact Information
                     */
                    'address' =>
                        'Quezon City, Metro Manila',

                    'contact_number' =>
                        '09170000000',

                    'emergency_contact' =>
                        '09180000000',

                    'notes' =>
                        'Administrator account created by MarkoAdminSeeder.',
                ]
            );

            $this->command?->info(
                'Marko Fiedacan Admin account created/updated successfully.'
            );

            $this->command?->info(
                "Company: {$company->name} (ID: {$company->id})"
            );

            $this->command?->info(
                'Email: mfiedacan@bsm.ph'
            );
        });
    }
}