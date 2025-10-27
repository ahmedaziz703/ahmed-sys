<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing roles and permissions
        Schema::disableForeignKeyConstraints();
        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('roles')->truncate();
        Schema::enableForeignKeyConstraints();

        // Create Super Admin role
        $superAdminRole = Role::create([
            'name' => 'admin',
            'guard_name' => 'web'
        ]);
        $superAdminRole->syncPermissions(Permission::all());

        // Create Employee role
        $employeeRole = Role::create([
            'name' => 'employee',
            'guard_name' => 'web'
        ]);
        $employeeRole->syncPermissions([
            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.detail',
            'leads.view',
            'leads.create',
            'leads.edit',
            'leads.convert_customer',
            'projects.view',
            'projects.create',
            'projects.edit',
            'projects.details',
        ]);

        // Assign Super Admin role to Admin user
        $admin = User::where('email', 'admin@admin.com')->first();
        $employee = User::where('email', 'test@test.com')->first();
        if ($admin) {
            $admin->assignRole($superAdminRole);
        }
        if ($employee) {
            $employee->assignRole($employeeRole);
        }
    }
} 