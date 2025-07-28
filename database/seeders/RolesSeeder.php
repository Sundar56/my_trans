<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name'         => 'superadmin',
                'display_name' => 'Super Admin',
                'guard_name'   => 'web',
                'type'         => 0,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ],
            [
                'name'         => 'customer',
                'display_name' => 'Customer',
                'guard_name'   => 'web',
                'type'         => 1,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ],
            [
                'name'         => 'contractor',
                'display_name' => 'Contractor',
                'guard_name'   => 'web',
                'type'         => 1,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ],
            [
                'name'         => 'admin',
                'display_name' => 'Admin',
                'guard_name'   => 'web',
                'type'         => 0,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ],
        ];
        foreach ($roles as $role) {
            Role::updateOrInsert(
                ['name' => $role['name']],
                $role 
            );
        }
        $this->command->info('Roles created successfully.');
    }
}
//php artisan db:seed --class=RolesSeeder