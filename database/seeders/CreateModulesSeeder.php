<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Modules;

class CreateModulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            [
                'name'          => 'Dashboard',
                'order'         => '1',
                'slug'          => 'dashboard',
                'type'          => 0,
                'icon'          => 'assets/img/dashboard-icon.svg',
                'frontend_slug' => 'dashboard',
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'name'          => 'Projects',
                'order'         => '2',
                'slug'          => 'projects',
                'type'          => 0,
                'icon'          => 'assets/img/project-icons.svg',
                'frontend_slug' => 'projects',
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'name'          => 'Disputes',
                'order'         => '3',
                'slug'          => 'disputes',
                'type'          => 0,
                'icon'          => 'assets/img/disputes.svg',
                'frontend_slug' => 'disputes',
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'name'          => 'How to',
                'order'         => '4',
                'slug'          => 'how-to',
                'type'          => 0,
                'icon'          => 'assets/img/help.svg',
                'frontend_slug' => 'how-to',
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'name'          => 'Account',
                'order'         => '5',
                'slug'          => 'account',
                'type'          => 0,
                'icon'          => 'assets/img/users.svg',
                'frontend_slug' => 'account',
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'name'          => 'Dashboard',
                'order'         => '6',
                'slug'          => 'admindashboard',
                'type'          => 1,
                'icon'          => 'assets/img/dashboard-icon.svg',
                'frontend_slug' => 'admin/dashboard',
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'name'          => 'Users',
                'order'         => '7',
                'slug'          => 'users',
                'type'          => 1,
                'icon'          => 'assets/img/users.svg',
                'frontend_slug'  => 'admin/users',
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'name'          => 'Projects',
                'order'         => '8',
                'slug'          => 'totalprojects',
                'type'          => 1,
                'icon'          => 'assets/img/project-icons.svg',
                'frontend_slug' => 'admin/projects',
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'name'          => 'Payment History',
                'order'         => '9',
                'slug'          => 'payment',
                'type'          => 1,
                'icon'          => 'assets/img/money-pound-01.svg',
                'frontend_slug' => 'admin/payment',
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'name'          => 'Disputes',
                'order'         => '10',
                'slug'          => 'admindispute',
                'type'          => 1,
                'icon'          => 'assets/img/disputes.svg',
                'frontend_slug' => 'admin/disputes',
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
        ];
        foreach ($modules as $module) {
            Modules::updateOrInsert(
                ['slug' => $module['slug']],
                $module
            );
        }
        $this->command->info('Modules created successfully.');
    }
}
//php artisan db:seed --class=CreateModulesSeeder