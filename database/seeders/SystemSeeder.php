<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Chat\Models\System;

/**
 * Ensures the Chat system actor (id=1) exists for support conversations / presence.
 */
class SystemSeeder extends Seeder
{
    public function run(): void
    {
        System::query()->firstOrCreate(
            ['id' => 1],
            [
                'name' => 'System',
                'online' => true,
            ],
        );
    }
}
