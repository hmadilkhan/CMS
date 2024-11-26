<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\UserType::create([
        //     "name" => "User"
        // ]);
        // \App\Models\UserType::create([
        //     "name" => "Employee"
        // ]);
        // \App\Models\UserType::create([
        //     "name" => "Sales Person"
        // ]);

        // \App\Models\User::factory()->create([
        //     'name' => 'Super Admin ',
        //     'email' => 'admin@example.com',
        //     'username' => 'hmadilkhan',
        //     'user_type_id' => 1,
        //     'password' => Hash::make("1234"),
        // ]);

        // // ROLES

        // \Spatie\Permission\Models\Role::create([
        //     'name' => 'Super Admin',
        // ]);

        // \Spatie\Permission\Models\Role::create([
        //     'name' => 'Admin',
        // ]);

        // \Spatie\Permission\Models\Role::create([
        //     'name' => 'Sales Person',
        // ]);

        // \Spatie\Permission\Models\Role::create([
        //     'name' => 'Manager',
        // ]);

        // \Spatie\Permission\Models\Role::create([
        //     'name' => 'Employee',
        // ]);

        // // DEPARTMENT SEEDER

        // \App\Models\Department::create([
        //     "name" => "Deal Review",
        //     "document_length" => 5
        // ]);
        // \App\Models\Department::create([
        //     "name" => "Site Survey",
        //     "document_length" => 1
        // ]);
        // \App\Models\Department::create([
        //     "name" => "Engineering",
        //     "document_length" => 3
        // ]);
        // \App\Models\Department::create([
        //     "name" => "Permitting",
        //     "document_length" => 2
        // ]);
        // \App\Models\Department::create([
        //     "name" => "Installation",
        //     "document_length" => 1
        // ]);
        // \App\Models\Department::create([
        //     "name" => "Inspection",
        //     "document_length" => 1
        // ]);
        // \App\Models\Department::create([
        //     "name" => "PTO",
        //     "document_length" => 1
        // ]);
        // \App\Models\Department::create([
        //     "name" => "Certificate of Completion",
        //     "document_length" => 0
        // ]);

        // // SUB DEPARTMENT SEEDER

        // \App\Models\SubDepartment::create([
        //     "name" => "New Deals",
        //     "department_id" => 1,
        // ]);
        // \App\Models\SubDepartment::create([
        //     "name" => "Sales Holds From Engineering",
        //     "department_id" => 1,
        // ]);

        // \App\Models\SubDepartment::create([
        //     "name" => "Site Survey New",
        //     "department_id" => 2,
        // ]);
        // \App\Models\SubDepartment::create([
        //     "name" => "Site Survey Rework",
        //     "department_id" => 2,
        // ]);


        // \App\Models\SubDepartment::create([
        //     "name" => "Engineering New",
        //     "department_id" => 3,
        // ]);
        // \App\Models\SubDepartment::create([
        //     "name" => "Engineering Rework",
        //     "department_id" => 3,
        // ]);
        // \App\Models\SubDepartment::create([
        //     "name" => "Engineering Holds",
        //     "department_id" => 3,
        // ]);
        // \App\Models\SubDepartment::create([
        //     "name" => "Engineering Sales Holds",
        //     "department_id" => 3,
        // ]);

        // \App\Models\SubDepartment::create([
        //     "name" => "Permitting New",
        //     "department_id" => 4,
        // ]);
        // \App\Models\SubDepartment::create([
        //     "name" => "Permitting Rework",
        //     "department_id" => 4,
        // ]);
        // \App\Models\SubDepartment::create([
        //     "name" => "Permitting Holds",
        //     "department_id" => 4,
        // ]);


        // \App\Models\SubDepartment::create([
        //     "name" => "Installation New",
        //     "department_id" => 5,
        // ]);
        // \App\Models\SubDepartment::create([
        //     "name" => "Installation Pending MPU",
        //     "department_id" => 5,
        // ]);
        // \App\Models\SubDepartment::create([
        //     "name" => "Installation Pending Roof",
        //     "department_id" => 5,
        // ]);
        // \App\Models\SubDepartment::create([
        //     "name" => "Installation Rework",
        //     "department_id" => 5,
        // ]);

        // \App\Models\SubDepartment::create([
        //     "name" => "Inspection New",
        //     "department_id" => 6,
        // ]);
        // \App\Models\SubDepartment::create([
        //     "name" => "Inspection Rework",
        //     "department_id" => 6,
        // ]);

        // \App\Models\FinanceOption::create([
        //     "name" => "Cash",
        // ]);
        // \App\Models\FinanceOption::create([
        //     "name" => "Mosaic Financing",
        // ]);
        // \App\Models\FinanceOption::create([
        //     "name" => "Goodleap Financing",
        // ]);

        // \App\Models\SalesPartner::create([
        //     "name" => "Sales Partner 1",
        // ]);

        // \App\Models\SalesPartner::create([
        //     "name" => "Sales Partner 2",
        // ]);

        // \App\Models\ModuleType::create([
        //     "name" => "CS6R-400MS",
        //     "value" => "400"
        // ]);
        // \App\Models\ModuleType::create([
        //     "name" => "Aptos 440W",
        //     "value" => "440"
        // ]);
        // \App\Models\ModuleType::create([
        //     "name" => "Hanwha Q Cell G10 400",
        //     "value" => "400"
        // ]);

        \App\Models\ProjectDepartmentField::create([
            "department_id" => 1,
            "field_name" => 'utility_company',
        ]);
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 1,
            "field_name" => 'ntp_approval_date',
        ]);
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 1,
            "field_name" => 'hoa',
        ]);
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 1,
            "field_name" => 'hoa_phone_number',
        ]);
        // 2
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 2,
            "field_name" => 'site_survey_link',
        ]);
        // 3
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 3,
            "field_name" => 'adders_approve_checkbox',
        ]);
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 3,
            "field_name" => 'mpu_required',
        ]);
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 3,
            "field_name" => 'meter_spot_request_date',
        ]);
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 3,
            "field_name" => 'meter_spot_request_number',
        ]);
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 3,
            "field_name" => 'meter_spot_result',
        ]);
        // 4
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 4,
            "field_name" => 'permitting_submittion_date',
        ]);
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 4,
            "field_name" => 'actual_permit_fee',
        ]);
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 4,
            "field_name" => 'permitting_approval_date',
        ]);
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 4,
            "field_name" => 'hoa_approval_request_date',
        ]);
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 4,
            "field_name" => 'hoa_approval_date',
        ]);
        //5
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 5,
            "field_name" => 'solar_install_date',
        ]);
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 5,
            "field_name" => 'battery_install_date',
        ]);
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 5,
            "field_name" => 'actual_labor_cost',
        ]);
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 5,
            "field_name" => 'actual_material_cost',
        ]);
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 5,
            "field_name" => 'mpu_install_date',
        ]);
        // 6
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 6,
            "field_name" => 'rough_inspection_date',
        ]);
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 6,
            "field_name" => 'final_inspection_date',
        ]);
        // 7
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 7,
            "field_name" => 'pto_submission_date',
        ]);
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 7,
            "field_name" => 'pto_approval_date',
        ]);
        // 8
        \App\Models\ProjectDepartmentField::create([
            "department_id" => 8,
            "field_name" => 'coc_packet_mailed_out_date',
        ]);
    }
}
