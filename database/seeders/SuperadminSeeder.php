<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperadminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user         = User::where('email', env('SUPERADMIN_EMAIL'))->first();
        $password     = env('SUPERADMIN_PASSWORD');
        $hashPassword = Hash::make($password);
        if (empty($user)) {
            $user = User::create([
                'name'             => env('SUPERADMIN_NAME'),
                'email'            => env('SUPERADMIN_EMAIL'),
                'password'         =>  $hashPassword,
                'activestatus'     =>  1,
            ]);
            $this->command->info('Superadmin created successfully.');
            $superAdminRole = Role::create(['name' => 'superadmin', 'display_name' => 'SuperAdmin']);
            $role = Role::findByName('superadmin');
            $user->assignRole($role);
        }
    }
}

//php artisan db:seed --class=SuperadminSeeder