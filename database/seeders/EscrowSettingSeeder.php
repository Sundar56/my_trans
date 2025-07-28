<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EscrowSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            DB::table('escrowsettings')->insert([
                'name'        => 'Transpact',
                'description' => 'This is a transpact gateway for integration.',
                'username'    => env('TRANSPACT_USERNAME'),
                'password'    => env('TRANSPACT_PASSWORD'),
                'soapurl'     => env('TRANSPACT_URL'),
                'webhookurl'  => env('WEBHOOK_URL'),
                'deductionfee'=> json_encode([
                    'fee' => [
                        '<25000'        => 49.99,
                        '25000-100000'  => 74.99,
                        '>100000'       => 99.99,
                    ]
                ]),
                'status'      => 1,
                // 'webhookurl'  => env('APP_URL') . env('WEBHOOK_URL'),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            DB::commit();
            echo "EscrowSettingSeeder ran successfully.\n";
        } catch (\Exception $e) {
            DB::rollback();
            echo "Failed to seed escrowsettings table.\n";
        }
    }
}

// php artisan db:seed --class=EscrowSettingSeeder
