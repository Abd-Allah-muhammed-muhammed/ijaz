<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\System;
use Illuminate\Database\Seeder;
use Str;

class AdminRootSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        System::firstOrCreate(['id' => 1]);

        //    $password = Str::random(10);
        $password = 'kolB20Euzx';
        Admin::firstOrCreate(['root' => true], [
            'name' => 'Root',
            'password' => $password,
            'address' => 'Root Address',
            'email_verified_at' => now(),
            'email' => 'root@nagaz.com',
            'phone' => '96600000000',
            'job' => 'Root Job',
        ]);
        echo "\n\tRootPassword = $password\n\n";

    }
}
