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

        \App\Models\UserType::create([
            "name" => "User"
        ]);
        \App\Models\UserType::create([
            "name" => "Employee"
        ]);
        \App\Models\UserType::create([
            "name" => "Sales Person"
        ]);

        \App\Models\User::factory()->create([
            'name' => 'Super Admin ',
            'email' => 'admin@example.com',
            'username' => 'hmadilkhan',
            'user_type_id' => 1,
            'password' => Hash::make("1234"),
        ]);

        // ROLES

        \Spatie\Permission\Models\Role::create([
            'name' => 'Super Admin',
        ]);

        \Spatie\Permission\Models\Role::create([
            'name' => 'Admin',
        ]);

        \Spatie\Permission\Models\Role::create([
            'name' => 'Sales Person',
        ]);

        \Spatie\Permission\Models\Role::create([
            'name' => 'Manager',
        ]);

        \Spatie\Permission\Models\Role::create([
            'name' => 'Employee',
        ]);

        // DEPARTMENT SEEDER

        \App\Models\Department::create([
            "name" => "Deal Review",
            "document_length" => 5
        ]);
        \App\Models\Department::create([
            "name" => "Site Survey",
            "document_length" => 1
        ]);
        \App\Models\Department::create([
            "name" => "Engineering",
            "document_length" => 3
        ]);
        \App\Models\Department::create([
            "name" => "Permitting",
            "document_length" => 2
        ]);
        \App\Models\Department::create([
            "name" => "Installation",
            "document_length" => 1
        ]);
        \App\Models\Department::create([
            "name" => "Inspection",
            "document_length" => 1
        ]);
        \App\Models\Department::create([
            "name" => "PTO",
            "document_length" => 1
        ]);
        \App\Models\Department::create([
            "name" => "Certificate of Completion",
            "document_length" => 0
        ]);

        // SUB DEPARTMENT SEEDER

        \App\Models\SubDepartment::create([
            "name" => "New Deals",
            "department_id" => 1,
        ]);
        \App\Models\SubDepartment::create([
            "name" => "Sales Holds From Engineering",
            "department_id" => 1,
        ]);

        \App\Models\SubDepartment::create([
            "name" => "Site Survey News",
            "department_id" => 2,
        ]);
        \App\Models\SubDepartment::create([
            "name" => "Site Survey Rework",
            "department_id" => 2,
        ]);


        \App\Models\SubDepartment::create([
            "name" => "Engineering New",
            "department_id" => 3,
        ]);
        \App\Models\SubDepartment::create([
            "name" => "Engineering Rework",
            "department_id" => 3,
        ]);
        \App\Models\SubDepartment::create([
            "name" => "Engineering Holds",
            "department_id" => 3,
        ]);
        \App\Models\SubDepartment::create([
            "name" => "Engineering Sales Holds",
            "department_id" => 3,
        ]);

        \App\Models\SubDepartment::create([
            "name" => "Permitting New",
            "department_id" => 4,
        ]);
        \App\Models\SubDepartment::create([
            "name" => "Permitting Rework",
            "department_id" => 4,
        ]);
        \App\Models\SubDepartment::create([
            "name" => "Permitting Holds",
            "department_id" => 4,
        ]);


        \App\Models\SubDepartment::create([
            "name" => "Installation New",
            "department_id" => 5,
        ]);
        \App\Models\SubDepartment::create([
            "name" => "Installation Pending MPU",
            "department_id" => 5,
        ]);
        \App\Models\SubDepartment::create([
            "name" => "Installation Pending Roof",
            "department_id" => 5,
        ]);
        \App\Models\SubDepartment::create([
            "name" => "Installation Rework",
            "department_id" => 5,
        ]);

        \App\Models\SubDepartment::create([
            "name" => "Inspection New",
            "department_id" => 6,
        ]);
        \App\Models\SubDepartment::create([
            "name" => "Inspection Rework",
            "department_id" => 6,
        ]);

        \App\Models\FinanceOption::create([
            "name" => "Cash",
        ]);
        \App\Models\FinanceOption::create([
            "name" => "Mosaic Financing",
        ]);
        \App\Models\FinanceOption::create([
            "name" => "Goodleap Financing",
        ]);

        \App\Models\SalesPartner::create([
            "name" => "Sales Partner 1",
        ]);

        \App\Models\SalesPartner::create([
            "name" => "Sales Partner 2",
        ]);

        \App\Models\ModuleType::create([
            "name" => "CS6R-400MS",
            "value" => "400"
        ]);
        \App\Models\ModuleType::create([
            "name" => "Aptos 440W",
            "value" => "440"
        ]);
        \App\Models\ModuleType::create([
            "name" => "Hanwha Q Cell G10 400",
            "value" => "400"
        ]);
    }
}
